<?php

namespace Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('orders::index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => ['required', 'exists:users,id'],
                'shipping_address' => ['required', 'string'],
                'order_status' => ['nullable', 'in:pending,shipped,delivered,cancelled'],
                'notes' => ['nullable', 'string']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        // Accept items JSON (multi-product)
        $items = [];
        $itemsJson = $request->input('items_json');
        if ($itemsJson) {
            $decoded = json_decode($itemsJson, true);
            if (is_array($decoded)) { $items = $decoded; }
        }

        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'No items provided'], 422);
        }

        $products = \Modules\Products\Models\Product::whereIn('id', collect($items)->pluck('product_id')->all())->get()->keyBy('id');
        $providerIds = [];
        $totalAmount = 0.0;

        $order = Order::create([
            'user_id' => $validated['user_id'],
            'provider_ids' => [],
            'total_amount' => 0,
            'order_status' => $validated['order_status'] ?? 'pending',
            'shipping_address' => $validated['shipping_address'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Prepare discount if provided
        $discount = null;
        $discountCodeInput = $request->input('discount_code');
        if ($discountCodeInput) {
            $discount = DiscountCode::active()->validNow()->notExceededUsage()->where('code', $discountCodeInput)->first();
        }

        // Build items data and determine applicable items for discount
        $itemsData = [];
        foreach ($items as $it) {
            $product = $products[$it['product_id']] ?? null;
            if (!$product) { continue; }
            $qty = max(1, (int) ($it['quantity'] ?? 1));
            $line = $product->price * $qty;
            $totalAmount += $line;
            $providerIds[] = $product->provider_id;
            $itemsData[] = [
                'product' => $product,
                'qty' => $qty,
                'line' => $line,
                'line_discount' => 0,
                'total_after_discount' => $line,
            ];
        }

        $totalDiscount = 0.0;
        if ($discount) {
            // Find applicable items (by category)
            $applicableIndexes = [];
            foreach ($itemsData as $idx => $d) {
                if ($discount->appliesToCategoryIds([(int)$d['product']->category_id])) {
                    $applicableIndexes[] = $idx;
                }
            }
            if (!empty($applicableIndexes)) {
                if (in_array($discount->discount_type, ['percent','percentage'], true)) {
                    foreach ($applicableIndexes as $i) {
                        $itemsData[$i]['line_discount'] = round($itemsData[$i]['line'] * ($discount->discount_value/100), 2);
                        $itemsData[$i]['total_after_discount'] = $itemsData[$i]['line'] - $itemsData[$i]['line_discount'];
                        $totalDiscount += $itemsData[$i]['line_discount'];
                    }
                } else {
                    // fixed amount: distribute proportionally across applicable items
                    $applicableTotal = array_sum(array_map(function($i){ return $i['line']; }, array_intersect_key($itemsData, array_flip($applicableIndexes))));
                    $remaining = (float) $discount->discount_value;
                    foreach ($applicableIndexes as $i) {
                        $share = $itemsData[$i]['line'] / $applicableTotal;
                        $itemsData[$i]['line_discount'] = round($discount->discount_value * $share, 2);
                        $itemsData[$i]['total_after_discount'] = $itemsData[$i]['line'] - $itemsData[$i]['line_discount'];
                        $remaining -= $itemsData[$i]['line_discount'];
                        $totalDiscount += $itemsData[$i]['line_discount'];
                    }
                    // adjust leftover rounding
                    if (abs($remaining) > 0.009) {
                        for ($j = count($itemsData)-1; $j>=0; $j--) {
                            if ($itemsData[$j]['line_discount'] > 0) { $itemsData[$j]['line_discount'] += round($remaining, 2); $itemsData[$j]['total_after_discount'] = $itemsData[$j]['line'] - $itemsData[$j]['line_discount']; break; }
                        }
                    }
                }
            }
        }

        // Persist order items with initial status
        foreach ($itemsData as $d) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $d['product']->id,
                'provider_id' => $d['product']->provider_id,
                'quantity' => $d['qty'],
                'unit_price' => $d['product']->price,
                'line_total' => $d['line'],
                'line_discount' => $d['line_discount'] ?? 0,
                'total' => $d['total_after_discount'] ?? $d['line'],
                'order_status' => OrderItem::STATUS_PENDING,
            ]);
        }
        
        // Recalculate order status after all items are created
        $order->recalculateOrderStatus();

        $order->update([
            'total_amount' => $totalAmount - $totalDiscount,
            'provider_ids' => array_values(array_unique($providerIds)),
            'discount_code' => $discount ? $discount->code : null,
            'discount_amount' => $totalDiscount,
        ]);

        // Create payment records (COD / unpaid) per provider for manual orders
        try {
            $cod = \App\Models\PaymentMethod::where('name', 'cod')->first();
        } catch (\Throwable $e) { $cod = null; }
        $paymentMethodId = $cod ? $cod->id : null;
        $itemsByProvider = $order->orderItems()->get()->groupBy('provider_id');
        foreach ($itemsByProvider as $provId => $provItems) {
            $provSubtotal = $provItems->sum(function($it){ return (float) ($it->line_total ?? $it->total); });
            $provDiscount = $provItems->sum(function($it){ return (float) ($it->line_discount ?? 0); });
            $provTotal = max(0, (float)$provSubtotal - (float)$provDiscount);
            \App\Models\Payment::create([
                'payment_id' => null,
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethodId,
                'amount' => $provTotal,
                'currency' => 'USD',
                'status' => 'unpaid',
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Order created successfully')
            ]);
        }

        $route = auth()->user()->hasRole('admin') ? 'admin.orders.index' : 'provider.orders.index';
        return redirect()->route($route)->with('status', __('Order created'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $order = Order::with(['user', 'orderItems.product', 'orderItems.provider'])->findOrFail($id);
        $this->authorizeView($order);

        if (request()->wantsJson() || request()->ajax()) {
            // Build response with allowed transitions for each item
            $orderArray = $order->toArray();
            
            // Map orderItems to include allowed transitions
            $orderArray['order_items'] = $order->orderItems->map(function ($item) {
                $itemData = $item->toArray();
                $itemData['allowed_transitions'] = $item->getAllowedTransitions();
                return $itemData;
            })->toArray();
            
            return response()->json($orderArray);
        }

        // Redirect to index if not AJAX request (show functionality handled in modal)
        $route = auth()->user()->hasRole('admin') ? 'admin.orders.index' : 'provider.orders.index';
        return redirect()->route($route);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $this->authorizeUpdate($order);

        try {
            $validated = $request->validate([
                'order_status' => ['required', 'in:pending,shipped,delivered,cancelled'],
                'notes' => ['nullable', 'string'],
                'shipping_address' => ['sometimes', 'string']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        // Use transition method for status changes
        $newStatus = $validated['order_status'];
        if ($order->order_status !== $newStatus) {
            // Only admins can change order-level status; providers must use item-scoped endpoints
            if (!Auth::user()->hasRole('admin')) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only admins can change order status at order level.'
                    ], 403);
                }
                abort(403, 'Only admins can change order status at order level.');
            }

            if (!$order->canTransitionTo($newStatus)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status transition not allowed. Current status: ' . $order->order_status
                    ], 403);
                }
                abort(403, 'Status transition not allowed');
            }
            
            // Admin changing order-level status: cascade to all items (force)
            $items = $order->orderItems()->get();
            foreach ($items as $item) {
                $item->order_status = $newStatus;
                $item->save();
            }

            // Transition order itself (fires order-level event/email once)
            $oldStatus = $order->order_status;
            $order->transitionTo($newStatus);

            // Recalculate to ensure aggregate reflects items (and refresh instance)
            $order->recalculateOrderStatus();
            $order->refresh();
        } else {
            // Status unchanged, just update other fields
            $order->update([
                'notes' => $validated['notes'] ?? $order->notes,
                'shipping_address' => $validated['shipping_address'] ?? $order->shipping_address,
            ]);
        }

        // Handle items and discount update if provided
        $items = [];
        $itemsJson = $request->input('items_json');
        if ($itemsJson) {
            $decoded = json_decode($itemsJson, true);
            if (is_array($decoded)) { $items = $decoded; }
        }
        if (!empty($items)) {
            // Only admins can rebuild order items. Providers must never alter other providers' items.
            if (!Auth::user()->hasRole('admin')) {
                // Silently ignore items payload for non-admins to avoid destructive deletes
                $items = [];
            }
        }

        if (!empty($items)) {
            // Rebuild items for simplicity (admin only)
            $order->orderItems()->delete();
            $products = \Modules\Products\Models\Product::whereIn('id', collect($items)->pluck('product_id')->all())->get()->keyBy('id');
            $providerIds = [];
            $totalAmount = 0.0;

            // Prepare discount if provided
            $discount = null;
            $discountCodeInput = $request->input('discount_code');
            if ($discountCodeInput) {
                $discount = DiscountCode::active()->validNow()->notExceededUsage()->where('code', $discountCodeInput)->first();
            }

            $itemsData = [];
            foreach ($items as $it) {
                $product = $products[$it['product_id']] ?? null;
                if (!$product) { continue; }
                $qty = max(1, (int) ($it['quantity'] ?? 1));
                $line = $product->price * $qty;
                $totalAmount += $line;
                $providerIds[] = $product->provider_id;
                $itemsData[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'line' => $line,
                    'line_discount' => 0,
                    'total_after_discount' => $line,
                ];
            }

            $totalDiscount = 0.0;
            if ($discount) {
                $applicableIndexes = [];
                foreach ($itemsData as $idx => $d) {
                    if ($discount->appliesToCategoryIds([(int)$d['product']->category_id])) {
                        $applicableIndexes[] = $idx;
                    }
                }
                if (!empty($applicableIndexes)) {
                    if (in_array($discount->discount_type, ['percent','percentage'], true)) {
                        foreach ($applicableIndexes as $i) {
                            $itemsData[$i]['line_discount'] = round($itemsData[$i]['line'] * ($discount->discount_value/100), 2);
                            $itemsData[$i]['total_after_discount'] = $itemsData[$i]['line'] - $itemsData[$i]['line_discount'];
                            $totalDiscount += $itemsData[$i]['line_discount'];
                        }
                    } else {
                        $applicableTotal = array_sum(array_map(function($i){ return $i['line']; }, array_intersect_key($itemsData, array_flip($applicableIndexes))));
                        $remaining = (float) $discount->discount_value;
                        foreach ($applicableIndexes as $i) {
                            $share = $itemsData[$i]['line'] / $applicableTotal;
                            $itemsData[$i]['line_discount'] = round($discount->discount_value * $share, 2);
                            $itemsData[$i]['total_after_discount'] = $itemsData[$i]['line'] - $itemsData[$i]['line_discount'];
                            $remaining -= $itemsData[$i]['line_discount'];
                            $totalDiscount += $itemsData[$i]['line_discount'];
                        }
                        if (abs($remaining) > 0.009) {
                            for ($j = count($itemsData)-1; $j>=0; $j--) {
                                if ($itemsData[$j]['line_discount'] > 0) { $itemsData[$j]['line_discount'] += round($remaining, 2); $itemsData[$j]['total_after_discount'] = $itemsData[$j]['line'] - $itemsData[$j]['line_discount']; break; }
                            }
                        }
                    }
                }
            }

            foreach ($itemsData as $d) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $d['product']->id,
                    'provider_id' => $d['product']->provider_id,
                    'quantity' => $d['qty'],
                    'unit_price' => $d['product']->price,
                    'line_total' => $d['line'],
                    'line_discount' => $d['line_discount'] ?? 0,
                    'total' => $d['total_after_discount'] ?? $d['line'],
                    'order_status' => OrderItem::STATUS_PENDING,
                ]);
            }
            
            // Recalculate order status after all items are updated
            $order->recalculateOrderStatus();

            $order->update([
                'total_amount' => $totalAmount - $totalDiscount,
                'provider_ids' => array_values(array_unique($providerIds)),
                'discount_code' => $discount ? $discount->code : null,
                'discount_amount' => $totalDiscount,
            ]);
        } else {
            // Update other fields if status didn't change but notes/address did
            if (isset($validated['notes']) || isset($validated['shipping_address'])) {
                $order->update([
                    'notes' => $validated['notes'] ?? $order->notes,
                    'shipping_address' => $validated['shipping_address'] ?? $order->shipping_address,
                ]);
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Order updated successfully'),
                'order' => $order->fresh()
            ]);
        }

        $route = auth()->user()->hasRole('admin') ? 'admin.orders.index' : 'provider.orders.index';
        return redirect()->route($route)->with('status', __('Order updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $this->authorizeUpdate($order);
        $order->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Order deleted successfully')
            ]);
        }

        $route = auth()->user()->hasRole('admin') ? 'admin.orders.index' : 'provider.orders.index';
        return redirect()->route($route)->with('status', __('Order deleted'));
    }

    public function data(DataTables $dataTables)
    {
        // CRITICAL: Eager load relationships to avoid N+1 queries
        // Load orderItems with fresh status data
        $query = Order::query()->with(['user', 'orderItems.product', 'orderItems.provider']);

        // Role-based filtering: for providers, filter by provider_ids JSON array
        // IMPORTANT: This ensures orders remain visible as long as provider_id exists in the array
        // Orders will NOT disappear when status changes because filtering is based on provider_ids only
        if (Auth::user()->hasRole('provider')) {
            $providerId = Auth::id();
            // Use whereJsonContains to check if provider_id exists in the provider_ids JSON array
            $query->whereJsonContains('provider_ids', $providerId);
        }

        return $dataTables->eloquent($query)
            ->addColumn('customer_name', fn ($row) => $row->user->name)
            ->addColumn('products', function ($row) {
                $items = $row->orderItems;
                $isProvider = Auth::user()->hasRole('provider');
                $providerId = Auth::id();
                
                if ($isProvider) {
                    // Provider: only show their items
                    $items = $items->where('provider_id', $providerId);
                }
                
                $products = $items->map(function ($item) use ($isProvider) {
                    if ($item->product) {
                        $imageUrl = $item->product->image_url;
                        $fallback = 'https://placehold.co/60x60?text=%20';
                        
                        $itemHtml = '<div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">';
                        $itemHtml .= '<div class="d-flex align-items-center">';
                        $itemHtml .= '<img src="' . e($imageUrl) . '" alt="' . e($item->product->title) . '" 
                                 class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;"
                                 referrerpolicy="no-referrer" crossorigin="anonymous" 
                                 onerror="this.onerror=null;this.src=\'' . $fallback . '\';" />';
                        $itemHtml .= '<div>';
                        $itemHtml .= '<div class="small fw-semibold">' . e($item->product->title) . ' (x' . $item->quantity . ')</div>';
                        if (!$isProvider) {
                            // Admin: show provider and item-level status badge
                            $itemHtml .= '<div class="small text-muted">Provider: ' . e($item->provider->name ?? 'N/A') . '</div>';
                            $badge = match($item->order_status) {
                                Order::STATUS_PENDING => 'bg-warning',
                                Order::STATUS_SHIPPED => 'bg-primary',
                                Order::STATUS_DELIVERED => 'bg-success',
                                Order::STATUS_CANCELLED => 'bg-danger',
                                default => 'bg-secondary',
                            };
                            $itemHtml .= '<div class="mt-1"><span class="badge rounded-pill ' . $badge . '">' . e(ucfirst($item->order_status)) . '</span></div>';
                        }
                        // Provider: NO status badge in Products column
                        $itemHtml .= '</div>';
                        $itemHtml .= '</div>';
                        $itemHtml .= '</div>';
                        
                        return $itemHtml;
                    }
                    return null;
                })->filter()->implode('');
                
                return $products ?: '<span class="text-muted">No products</span>';
            })
            ->addColumn('total', function ($row) {
                if (Auth::user()->hasRole('provider')) {
                    $providerId = Auth::id();
                    $providerItems = $row->orderItems->where('provider_id', $providerId);
                    // Use original line totals for provider subtotal
                    $subtotal = $providerItems->sum(function ($item) {
                        return (float) ($item->line_total ?? $item->total);
                    });
                    $providerDiscount = $providerItems->sum(function ($item) {
                        return (float) ($item->line_discount ?? 0);
                    });
                    $final = max(0, (float) $subtotal - (float) $providerDiscount);
                    return '$' . number_format($final, 2);
                }
                return '$' . number_format($row->total_amount, 2);
            })
            ->addColumn('payment_status', function ($row) {
                // Aggregate payment statuses for the order
                $payments = $row->payment()->get();
                if ($payments->isEmpty()) {
                return '<span class="badge rounded-pill bg-secondary">N/A</span>';
                }
                $statuses = $payments->pluck('status')->map(function($s){
                    if (in_array($s, ['pending','processing','failed','cancelled'], true)) return 'unpaid';
                    return $s;
                })->values();
                $final = 'unpaid';
                if ($statuses->contains('refunded')) { $final = 'refunded'; }
                elseif ($statuses->every(fn($s) => $s === 'paid')) { $final = 'paid'; }
                $cls = match($final){
                    'paid' => 'bg-success',
                    'unpaid' => 'bg-warning',
                    'refunded' => 'bg-secondary',
                    default => 'bg-secondary',
                };
                return '<span class="badge rounded-pill '.$cls.'">'.ucfirst($final).'</span>';
            })
            ->editColumn('order_status', function ($row) {
                $isProvider = Auth::user()->hasRole('provider');
                if ($isProvider) {
                    $providerId = Auth::id();
                    $providerItems = $row->orderItems->where('provider_id', $providerId);
                    [$status, $badge] = $this->aggregateStatusForItems($providerItems);
                    return '<span class="badge rounded-pill ' . $badge . '">' . e(ucfirst($status)) . '</span>';
                }
                $badge = match($row->order_status) {
                    Order::STATUS_PENDING => 'bg-warning',
                    Order::STATUS_SHIPPED => 'bg-primary',
                    Order::STATUS_DELIVERED => 'bg-success',
                    Order::STATUS_CANCELLED => 'bg-danger',
                    default => 'bg-secondary',
                };
                return '<span class="badge rounded-pill ' . $badge . '">' . e(ucfirst($row->order_status)) . '</span>';
            })
            ->addColumn('shipping_address', function($row){
                return $row->shipping_address ? e($row->shipping_address) : '-';
            })
            ->addColumn('notes', function($row){
                return $row->notes ? e($row->notes) : '-';
            })
            ->addColumn('discount_code', function($row){
                // For provider, only show if any of their items received a discount
                if (Auth::user() && Auth::user()->hasRole('provider')) {
                    $providerId = Auth::id();
                    $providerItems = $row->orderItems->where('provider_id', $providerId);
                    $hasDiscount = $providerItems->sum(function($it){ return (float)($it->line_discount ?? 0); }) > 0;
                    return $hasDiscount ? e($row->discount_code) : '-';
                }
                return $row->discount_code ? e($row->discount_code) : '-';
            })
            ->addColumn('discount_amount', function($row){
                // For provider view, only show discounts that apply to their items
                if (Auth::user() && Auth::user()->hasRole('provider')) {
                    $providerId = Auth::id();
                    $providerDiscount = $row->orderItems->where('provider_id', $providerId)->sum(function($it){ return (float)($it->line_discount ?? 0); });
                    return $providerDiscount > 0 ? '$'.number_format($providerDiscount,2) : '-';
                }
                return $row->discount_amount ? '$'.number_format($row->discount_amount,2) : '-';
            })
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)
                    ? $row->created_at->copy()->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s')
                    : null;
            })
            ->addColumn('actions', function ($row) {
                $isAdmin = Auth::user() && Auth::user()->hasRole('admin');
                $isProvider = Auth::user() && Auth::user()->hasRole('provider');
                $prefix = $isAdmin ? 'admin' : 'provider';
                
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary editBtn" data-module="orders" data-id="'.$row->id.'" title="Edit">';
                $btns .= '<i class="fas fa-pencil-alt"></i></button>';

                if ($isProvider) {
                    // Provider status control that updates ONLY provider's items
                    $providerId = Auth::id();
                    $providerItems = $row->orderItems->where('provider_id', $providerId);
                    [$aggStatus, ] = $this->aggregateStatusForItems($providerItems);
                    $allowed = [];
                    if ($aggStatus === Order::STATUS_PENDING) {
                        $allowed = [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED];
                    } elseif ($aggStatus === Order::STATUS_SHIPPED) {
                        $allowed = [Order::STATUS_DELIVERED];
                    }
                    if (!empty($allowed)) {
                        $btns .= '<button type="button" class="btn btn-sm btn-outline-info provider-status-trigger"'
                            . ' data-order-id="'.$row->id.'"'
                            . ' data-statuses="'.e(json_encode(array_values($allowed))).'"'
                            . ' title="Update my items status">Status</button>';
                    }
                    // Provider delete button like admin
                    $btns .= '<button class="btn btn-sm btn-outline-danger delete-order" data-id="'.$row->id.'" data-delete-url="/'.$prefix.'/orders/'.$row->id.'" title="Delete">';
                    $btns .= '<i class="fas fa-trash"></i></button>';
                }

                if ($isAdmin) {
                    // Admin full order status control (order-level)
                    $all = [Order::STATUS_PENDING, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED];
                    $allowedAdmin = array_values(array_filter($all, function($st) use ($row){ return $st !== $row->order_status; }));
                    if (!empty($allowedAdmin)) {
                        $btns .= '<button type="button" class="btn btn-sm btn-outline-info admin-status-trigger"'
                            . ' data-order-id="'.$row->id.'"'
                            . ' data-statuses="'.e(json_encode($allowedAdmin)).'"'
                            . ' title="Update order status">Status</button>';
                    }

                    $btns .= '<button class="btn btn-sm btn-outline-danger delete-order" data-id="'.$row->id.'" data-delete-url="/'.$prefix.'/orders/'.$row->id.'" title="Delete">';
                    $btns .= '<i class="fas fa-trash"></i></button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions', 'order_status', 'products', 'payment_status'])
            ->toJson();
    }

    /**
     * Aggregate item statuses for a set of items according to earliest-active rule.
     * Returns [status, badgeClass]
     */
    private function aggregateStatusForItems($items): array
    {
        if ($items->isEmpty()) {
            return [Order::STATUS_PENDING, 'bg-warning'];
        }

        $counts = [
            Order::STATUS_PENDING => 0,
            Order::STATUS_SHIPPED => 0,
            Order::STATUS_DELIVERED => 0,
            Order::STATUS_CANCELLED => 0,
        ];
        
        foreach ($items as $it) {
            $st = $it->order_status ?? Order::STATUS_PENDING;
            if (isset($counts[$st])) { 
                $counts[$st]++; 
            }
        }
        
        $total = $items->count();

        // Priority: pending < shipped < delivered, cancelled only if all cancelled
        if ($counts[Order::STATUS_CANCELLED] === $total) {
            return [Order::STATUS_CANCELLED, 'bg-danger'];
        }
        if ($counts[Order::STATUS_DELIVERED] === $total) {
            return [Order::STATUS_DELIVERED, 'bg-success'];
        }
        if ($counts[Order::STATUS_PENDING] > 0) {
            return [Order::STATUS_PENDING, 'bg-warning'];
        }
        if ($counts[Order::STATUS_SHIPPED] > 0) {
            return [Order::STATUS_SHIPPED, 'bg-primary'];
        }
        
        // Fallback
        return [Order::STATUS_PENDING, 'bg-warning'];
    }

    /**
     * Aggregate item statuses for a set of items according to earliest-active rule.
     */
    

    /**
     * Return products and discounts for modal (Admin & Provider).
     */
    public function modalData(Request $request)
    {
        $user = Auth::user();
        
        // Load products - filtered by provider if user is provider
        if ($user->hasRole('provider')) {
            $products = \Modules\Products\Models\Product::where('provider_id', $user->id)
                ->select('id', 'title', 'price', 'category_id', 'provider_id')
                ->get();
        } else {
            $products = \Modules\Products\Models\Product::select('id', 'title', 'price', 'category_id', 'provider_id')
                ->get();
        }
        
        // Load active discounts - admin only
        $discounts = [];
        if ($user->hasRole('admin')) {
            $discounts = DiscountCode::active()->validNow()->notExceededUsage()
                ->get()
                ->map(function ($d) {
                    return [
                        'id' => $d->id,
                        'code' => $d->code,
                        'discount_type' => $d->discount_type,
                        'discount_value' => (float) $d->discount_value,
                        'minimum_order_amount' => $d->minimum_order_amount ? (float) $d->minimum_order_amount : null,
                        'category_ids' => $d->categories()->pluck('categories.id')->all(),
                    ];
                });
        }
        
        return response()->json([
            'success' => true,
            'products' => $products,
            'discounts' => $discounts,
        ]);
    }

    /**
     * Return eligible discount codes for the given products (Admin only).
     */
    public function eligibleDiscounts(Request $request)
    {
        abort_unless(Auth::user() && Auth::user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $items = collect($validated['items']);
        $productIds = $items->pluck('product_id')->all();

        $products = \Modules\Products\Models\Product::whereIn('id', $productIds)->get(['id','price','category_id']);
        $productsById = $products->keyBy('id');

        // Compute original total (for optional min-order checks on server side)
        $originalTotal = 0.0;
        foreach ($items as $it) {
            $product = $productsById->get((int) $it['product_id']);
            if (!$product) { continue; }
            $qty = max(1, (int) ($it['quantity'] ?? 1));
            $originalTotal += (float) $product->price * $qty;
        }

        // Collect distinct category ids among selected products
        $categoryIds = $products->pluck('category_id')->filter()->unique()->values()->all();

        // Fetch active & currently valid discounts
        $discounts = DiscountCode::active()->validNow()->notExceededUsage()->get();

        // Filter to those that apply to at least one of the selected product categories
        $eligible = $discounts->filter(function ($d) use ($categoryIds) {
            return $d->appliesToCategoryIds($categoryIds);
        })->values();

        // Map to lightweight payload, include category_ids for precise client-side calculation
        $payload = $eligible->map(function ($d) {
            $catIds = $d->categories()->pluck('categories.id')->all();
            return [
                'id' => $d->id,
                'code' => $d->code,
                'discount_type' => $d->discount_type,
                'discount_value' => (float) $d->discount_value,
                'minimum_order_amount' => $d->minimum_order_amount ? (float) $d->minimum_order_amount : null,
                'category_ids' => $catIds, // empty => global
            ];
        });

        return response()->json([
            'success' => true,
            'original_total' => round($originalTotal, 2),
            'discounts' => $payload,
        ]);
    }

    /**
     * Cancel order (User endpoint)
     */
    public function cancel(Request $request, $id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        $user = Auth::user();
        abort_unless($user && $user->hasRole('user') && $order->user_id === $user->id, 403);

        if ($order->order_status !== Order::STATUS_PENDING) {
        return response()->json([
            'success' => false,
                'message' => __('You can cancel only pending orders.')
            ], 422);
        }

        // Set all items to cancelled and transition order to cancelled (one email)
        foreach ($order->orderItems as $item) {
            $item->order_status = Order::STATUS_CANCELLED;
            $item->save();
        }
        $order->transitionTo(Order::STATUS_CANCELLED);
        $order->recalculateOrderStatus();

        return response()->json([
            'success' => true,
            'message' => __('Order cancelled successfully')
        ]);
    }

    /**
     * Update order status only (AJAX endpoint)
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        $this->authorizeUpdate($order);

        $validated = $request->validate([
            'order_status' => ['required', 'in:pending,shipped,delivered,cancelled'],
        ]);

        $newStatus = $validated['order_status'];
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            foreach ($order->orderItems as $item) {
                $item->order_status = $newStatus;
                $item->save();
            }
            $order->transitionTo($newStatus);
            $order->recalculateOrderStatus();
        } elseif ($user->hasRole('provider')) {
            $providerId = $user->id;
            foreach ($order->orderItems->where('provider_id', $providerId) as $item) {
                $item->transitionTo($newStatus);
            }
            $order->recalculateOrderStatus();
        } else {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'message' => __('Status updated successfully')
        ]);
    }

    private function authorizeView(Order $order): void
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return;
        }
        if ($user->hasRole('user')) {
            abort_unless($order->user_id === $user->id, 403);
            return;
        }
        abort_unless($order->containsProvider($user->id), 403);
    }

    /**
     * Update order item status (AJAX endpoint)
     */
    public function updateItemStatus(Request $request, $orderId, $itemId): JsonResponse
    {
        $order = Order::findOrFail($orderId);
        $this->authorizeUpdate($order);

        $validated = $request->validate([
            'order_status' => ['required', 'in:pending,shipped,delivered,cancelled'],
        ]);

        $item = OrderItem::where('order_id', $orderId)->where('id', $itemId)->firstOrFail();

        if (Auth::user()->hasRole('provider')) {
            abort_unless($item->provider_id === Auth::id(), 403);
        }

        $item->transitionTo($validated['order_status']);
        $order->recalculateOrderStatus();

        return response()->json([
            'success' => true,
            'message' => __('Item status updated successfully')
        ]);
    }

    /**
     * Cancel order item (User endpoint)
     */
    public function cancelItem(Request $request, $orderId, $itemId): JsonResponse
    {
        // Soft-disabled: do nothing
        return response()->json([
            'success' => false,
            'message' => 'Order tracking is disabled.'
        ]);
    }

    private function authorizeUpdate(Order $order): void
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return;
        }
        if ($user->hasRole('user')) {
            abort_unless($order->user_id === $user->id && $order->order_status === Order::STATUS_PENDING, 403);
            return;
        }
        abort_unless($order->containsProvider($user->id), 403);
    }

    /**
     * Return lightweight statuses for user's orders for polling (user side real-time updates)
     */
    public function userStatuses(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('user'), 403);

        $orders = Order::where('user_id', $user->id)
            ->latest('id')
            ->get(['id','order_number','order_status','updated_at']);

        $payload = $orders->map(function ($o) {
            return [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'order_status' => $o->order_status,
                'progress' => $o->getProgressPercentage(),
                'can_cancel' => $o->order_status === Order::STATUS_PENDING,
                'updated_at' => optional($o->updated_at)?->toAtomString(),
            ];
        });

        return response()->json([
            'success' => true,
            'orders' => $payload,
        ]);
    }
}