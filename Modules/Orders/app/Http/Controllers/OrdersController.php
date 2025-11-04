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
            if (!$order->canTransitionTo($newStatus)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status transition not allowed. Current status: ' . $order->order_status
                    ], 403);
                }
                abort(403, 'Status transition not allowed');
            }
            
            $oldStatus = $order->order_status;
            $order->transitionTo($newStatus);
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
            // Rebuild items for simplicity
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
                            // Admin: show provider name and individual item status badge
                            $itemHtml .= '<div class="small text-muted">Provider: ' . e($item->provider->name ?? 'N/A') . '</div>';
                            $statusBadge = '<span class="badge badge-sm ' . $item->getStatusBadgeClass() . '">' . $item->getStatusDisplayName() . '</span>';
                            $itemHtml .= '<div class="mt-1">' . $statusBadge . '</div>';
                        }
                        // Provider: NO status badge in Products column (shown only in Status column)
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
            ->editColumn('order_status', function ($row) {
                // Get fresh order instance to ensure we have latest status
                $order = Order::find($row->id);
                if (!$order) {
                    $status = $row->order_status ?? 'pending';
                    return '<span class="badge rounded-pill bg-secondary">' . ucfirst($status) . '</span>';
                }

                if (Auth::user() && Auth::user()->hasRole('provider')) {
                    // Provider: calculate aggregated status based ONLY on their items
                    $providerId = Auth::id();
                    $items = $order->orderItems->where('provider_id', $providerId);
                    [$status, $cls] = $this->aggregateStatusForItems($items);
                    return '<span class="badge rounded-pill ' . $cls . '">' . ucfirst($status) . '</span>';
                }

                // Admin: show aggregate order-level status (updates only when all items match)
                $cls = $order->getStatusBadgeClass();
                return '<span class="badge rounded-pill ' . $cls . '">' . $order->getStatusDisplayName() . '</span>';
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
                $prefix = (Auth::user() && Auth::user()->hasRole('admin')) ? 'admin' : 'provider';
                $isProvider = Auth::user()->hasRole('provider');
                $allowedStatuses = [];
                
                // Calculate allowed status transitions based on role
                if ($isProvider) {
                    // Provider: calculate allowed transitions based on their items' aggregated status
                    $providerId = Auth::id();
                    $providerItems = $row->orderItems->where('provider_id', $providerId);
                    
                    if ($providerItems->isNotEmpty()) {
                        // Get the current effective status for provider's items
                        [$currentStatus, $cls] = $this->aggregateStatusForItems($providerItems);
                        
                        // Provider transitions: pending → shipped/delivered/cancelled, shipped → delivered
                        if ($currentStatus === OrderItem::STATUS_PENDING) {
                            $allowedStatuses = [OrderItem::STATUS_SHIPPED, OrderItem::STATUS_DELIVERED, OrderItem::STATUS_CANCELLED];
                        } elseif ($currentStatus === OrderItem::STATUS_SHIPPED) {
                            $allowedStatuses = [OrderItem::STATUS_DELIVERED];
                        }
                    }
                } else {
                    // Admin: use order-level status transitions
                    $order = Order::find($row->id);
                    if ($order) {
                        $allowedStatuses = $order->getAllowedTransitions();
                    }
                }
                
                $btns = '<div class="btn-group" role="group">';
                
                // View items/details button (opens modal with item-level controls)
                $btns .= '<button class="btn btn-sm btn-outline-secondary view-order-items" data-id="'.$row->id.'" title="View Items">';
                $btns .= '<i class="fas fa-list"></i></button>';
                
                // Status update dropdown for order (if allowed transitions exist)
                if (!empty($allowedStatuses)) {
                    $btns .= '<div class="btn-group" role="group">';
                    $btns .= '<button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">';
                    $btns .= '<i class="fas fa-sync-alt"></i> ' . ($isProvider ? 'My Items' : 'Order') . '</button>';
                    $btns .= '<ul class="dropdown-menu">';
                    foreach ($allowedStatuses as $status) {
                        $statusLabel = ucfirst($status);
                        $btns .= '<li><a class="dropdown-item update-status-btn" href="#" data-order-id="'.$row->id.'" data-status="'.$status.'">';
                        $btns .= $statusLabel . '</a></li>';
                    }
                    $btns .= '</ul>';
                    $btns .= '</div>';
                }
                
                // Edit button
                $btns .= '<button class="btn btn-sm btn-outline-primary edit-order" data-id="'.$row->id.'" data-bs-toggle="modal" data-bs-target="#orderModal" title="Edit" onclick="openOrderModal('.$row->id.')">';
                $btns .= '<i class="fas fa-pencil-alt"></i></button>';
                
                // Delete button (admin only)
                if (Auth::user() && Auth::user()->hasRole('admin')) {
                    $btns .= '<button class="btn btn-sm btn-outline-danger delete-order" data-id="'.$row->id.'" data-delete-url="/'.$prefix.'/orders/'.$row->id.'" title="Delete">';
                    $btns .= '<i class="fas fa-trash"></i></button>';
                }
                
                $btns .= '</div>'; 
                // 🧩 TEMP DEBUG: check if data-id and HTML are correct
                dd($row->id, $btns);
                return $btns;
            })
            ->rawColumns(['actions', 'order_status', 'products'])
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
        $order = Order::findOrFail($id);
        
        // Use policy for authorization
        $this->authorize('cancel', $order);

        if ($order->order_status !== Order::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending orders can be cancelled'
            ], 403);
        }

        $success = $order->transitionTo(Order::STATUS_CANCELLED);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'order' => $order->fresh()->load(['user', 'orderItems.product'])
        ]);
    }

    /**
     * Update order status only (AJAX endpoint)
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        // Use policy for authorization
        $this->authorize('update', $order);

        try {
            $validated = $request->validate([
                'order_status' => ['required', 'in:pending,shipped,delivered,cancelled'],
                'notes' => ['nullable', 'string']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $newStatus = $validated['order_status'];

        // Provider: update only their own items in this order (group action)
        if (Auth::user()->hasRole('provider')) {
            $providerId = Auth::id();
            $items = $order->orderItems()->where('provider_id', $providerId)->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items for this provider in the order.'
                ], 404);
            }

            // Validate transitions for all items first
            foreach ($items as $item) {
                if (!$item->canTransitionTo($newStatus)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status transition not allowed for one or more items.'
                    ], 403);
                }
            }

            // Perform transitions
            foreach ($items as $item) {
                $item->transitionTo($newStatus);
            }

            // Optional notes update on order
            if (isset($validated['notes'])) {
                $order->update(['notes' => $validated['notes']]);
            }

            // Recalculate parent order status and, if uniform, transition order (fires single email)
            $order->recalculateOrderStatus();
            $order->refresh();
            if ($uniform = $order->getUniformItemStatus()) {
                if ($uniform !== $order->order_status) {
                    $order->transitionTo($uniform);
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'Provider items updated successfully',
                'order' => $order->load(['user','orderItems.product','orderItems.provider'])
            ]);
        }

        // Admin: update all items to the requested status (cannot revert delivered)
        if (Auth::user()->hasRole('admin')) {
            $items = $order->orderItems()->get();

            foreach ($items as $item) {
                if (!$item->canTransitionTo($newStatus)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status transition not allowed for at least one item (e.g., cannot revert from Delivered).'
                    ], 403);
                }
            }

            foreach ($items as $item) {
                $item->transitionTo($newStatus);
            }

            if (isset($validated['notes'])) {
                $order->update(['notes' => $validated['notes']]);
            }

            // After bulk change, recalc and transition order once (single email)
            $order->recalculateOrderStatus();
            $order->refresh();
            if ($order->order_status !== $newStatus) {
                // If recalculated status differs and matches newStatus, transition
                if ($order->getUniformItemStatus() === $newStatus) {
                    $order->transitionTo($newStatus);
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'Order items updated successfully',
                'order' => $order->load(['user','orderItems.product','orderItems.provider'])
            ]);
        }

        // Fallback: role not supported here
        return response()->json([
            'success' => false,
            'message' => 'Operation not permitted for this role.'
        ], 403);
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
        $orderItem = OrderItem::where('id', $itemId)
                              ->where('order_id', $orderId)
                              ->firstOrFail();

        // Check authorization via policy
        $user = Auth::user();
        if (!$orderItem->canTransitionTo($request->input('order_status'))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this order item'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'order_status' => ['required', 'in:pending,shipped,delivered,cancelled'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $newStatus = $validated['order_status'];
        
        if ($orderItem->order_status === $newStatus) {
            return response()->json([
                'success' => true,
                'message' => 'Order item status unchanged',
                'orderItem' => $orderItem->fresh(),
                'order' => $order->fresh()
            ]);
        }

        if (!$orderItem->canTransitionTo($newStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'Status transition not allowed. Current status: ' . $orderItem->order_status
            ], 403);
        }

        $oldStatus = $orderItem->order_status;
        $success = $orderItem->transitionTo($newStatus);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order item status'
            ], 500);
        }

        // Order status is auto-recalculated via OrderItem boot event
        $order->refresh();
        $orderItem->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Order item status updated successfully',
            'orderItem' => $orderItem->load(['product', 'provider']),
            'order' => $order->load(['user', 'orderItems.product', 'orderItems.provider']),
            'orderItemTransitions' => $orderItem->getAllowedTransitions()
        ]);
    }

    /**
     * Cancel order item (User endpoint)
     */
    public function cancelItem(Request $request, $orderId, $itemId): JsonResponse
    {
        $order = Order::findOrFail($orderId);
        $orderItem = OrderItem::where('id', $itemId)
                              ->where('order_id', $orderId)
                              ->firstOrFail();

        // Use policy for authorization
        try {
            $this->authorize('cancel', $orderItem);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to cancel this order item'
            ], 403);
        }

        if ($orderItem->order_status !== OrderItem::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending items can be cancelled'
            ], 403);
        }

        $success = $orderItem->transitionTo(OrderItem::STATUS_CANCELLED);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order item'
            ], 500);
        }

        // Order status is auto-recalculated via OrderItem boot event
        $order->refresh();
        $orderItem->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Order item cancelled successfully',
            'orderItem' => $orderItem->load(['product', 'provider']),
            'order' => $order->load(['user', 'orderItems.product', 'orderItems.provider'])
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
}