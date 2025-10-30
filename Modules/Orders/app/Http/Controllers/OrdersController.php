<?php

namespace Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

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

        // Persist order items
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
            ]);
        }

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
        $order = Order::with(['user', 'orderItems.product'])->findOrFail($id);
        $this->authorizeView($order);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($order);
        }

        return view('orders::show', compact('order'));
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

        $order->update($validated);

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
                ]);
            }

            $order->update([
                'total_amount' => $totalAmount - $totalDiscount,
                'provider_ids' => array_values(array_unique($providerIds)),
                'discount_code' => $discount ? $discount->code : null,
                'discount_amount' => $totalDiscount,
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Order updated successfully')
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
        $query = Order::query()->with(['user', 'orderItems.product']);

        // Role-based filtering via orders.provider_ids (JSON array)
        if (Auth::user()->hasRole('provider')) {
            $query->whereJsonContains('provider_ids', Auth::id());
        }

        return $dataTables->eloquent($query)
            ->addColumn('customer_name', fn ($row) => $row->user->name)
            ->addColumn('products', function ($row) {
                $items = $row->orderItems;
                if (Auth::user()->hasRole('provider')) {
                    $items = $items->where('provider_id', Auth::id());
                }
                $products = $items->map(function ($item) {
                    if ($item->product) {
                        $imageUrl = $item->product->image_url;
                        $fallback = 'https://placehold.co/60x60?text=%20';
                        
                        return '<div class="d-flex align-items-center mb-1">
                            <img src="' . e($imageUrl) . '" alt="' . e($item->product->title) . '" 
                                 class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;"
                                 referrerpolicy="no-referrer" crossorigin="anonymous" 
                                 onerror="this.onerror=null;this.src=\'' . $fallback . '\';" />
                            <span class="small">' . e($item->product->title) . ' (x' . $item->quantity . ')</span>
                        </div>';
                    }
                    return null;
                })->filter()->implode('');
                
                return $products ?: '<span class="text-muted">No products</span>';
            })
            ->addColumn('total', function ($row) {
                if (Auth::user()->hasRole('provider')) {
                    $providerId = Auth::id();
                    $subtotal = $row->orderItems->where('provider_id', $providerId)->sum(function ($item) {
                        return (float) $item->total;
                    });
                    return '$' . number_format($subtotal, 2);
                }
                return '$' . number_format($row->total_amount, 2);
            })
            ->editColumn('order_status', function ($row) {
                $status = $row->order_status ?? $row->status;
                $badgeClass = match($status) {
                    'pending' => 'badge-warning',
                    'shipped' => 'badge-info',
                    'delivered' => 'badge-success',
                    'cancelled' => 'badge-danger',
                    default => 'badge-secondary'
                };
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('shipping_address', function($row){
                return $row->shipping_address ? e($row->shipping_address) : '-';
            })
            ->addColumn('notes', function($row){
                return $row->notes ? e($row->notes) : '-';
            })
            ->addColumn('discount_code', function($row){
                // For provider, only show if any of his items received a discount (line_discount > 0)
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
                $btns = '<div class="btn-group" role="group">';
                // mark this button for local modal handling (page-local) and use the selector the page script expects
                $btns .= '<button class="btn btn-sm btn-outline-primary edit-order" data-id="'.$row->id.'" data-local-modal="1">';
                $btns .= '<i class="fas fa-pencil-alt"></i></button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger delete-order" data-id="'.$row->id.'" data-delete-url="/'.$prefix.'/orders/'.$row->id.'">';
                $btns .= '<i class="fas fa-trash"></i></button>';
                $btns .= '</div>'; 
                return $btns;
            })
            ->rawColumns(['actions', 'order_status', 'products'])
            ->toJson();
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

    private function authorizeView(Order $order): void
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return;
        }
        abort_unless($order->containsProvider($user->id), 403);
    }

    private function authorizeUpdate(Order $order): void
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return;
        }
        abort_unless($order->containsProvider($user->id), 403);
    }
}