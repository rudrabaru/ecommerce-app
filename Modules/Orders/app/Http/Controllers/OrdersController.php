<?php

namespace Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('orders::create');
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

        // Accept items JSON (multi-product). Fallback to single product fields if provided.
        $items = [];
        $itemsJson = $request->input('items_json');
        if ($itemsJson) {
            $decoded = json_decode($itemsJson, true);
            if (is_array($decoded)) { $items = $decoded; }
        } elseif ($request->filled('product_id')) {
            $items = [[
                'product_id' => (int) $request->input('product_id'),
                'quantity' => (int) $request->input('quantity', 1),
            ]];
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

        foreach ($items as $it) {
            $product = $products[$it['product_id']] ?? null;
            if (!$product) { continue; }
            $qty = max(1, (int) ($it['quantity'] ?? 1));
            $line = $product->price * $qty;
            $totalAmount += $line;
            $providerIds[] = $product->provider_id;
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'provider_id' => $product->provider_id,
                'quantity' => $qty,
                'unit_price' => $product->price,
                'line_total' => $line,
                'line_discount' => 0,
                'total' => $line,
            ]);
        }

        $order->update([
            'total_amount' => $totalAmount,
            'provider_ids' => array_values(array_unique($providerIds)),
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
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $order = Order::with(['user:id,name,email', 'orderItems.product'])->findOrFail($id);
        $this->authorizeUpdate($order);

        if (request()->wantsJson() || request()->ajax()) {
            // reshape for compact JSON
            return response()->json([
                'id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'user' => $order->user,
                'order_status' => $order->order_status,
                'status' => $order->order_status,
                'shipping_address' => $order->shipping_address,
                'notes' => $order->notes,
                'total_amount' => $order->total_amount,
                'order_items' => $order->orderItems->map(function($it){
                    return [
                        'id' => $it->id,
                        'product_id' => $it->product_id,
                        'quantity' => $it->quantity,
                        'unit_price' => $it->unit_price,
                        'total' => $it->total,
                        'product' => $it->product ? [
                            'title' => $it->product->title,
                            'image_url' => $it->product->image_url,
                        ] : null,
                    ];
                }),
            ]);
        }

        return view('orders::edit', compact('order'));
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

        // Handle items update if provided
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
            foreach ($items as $it) {
                $product = $products[$it['product_id']] ?? null;
                if (!$product) { continue; }
                $qty = max(1, (int) ($it['quantity'] ?? 1));
                $line = $product->price * $qty;
                $totalAmount += $line;
                $providerIds[] = $product->provider_id;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'provider_id' => $product->provider_id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'line_total' => $line,
                    'line_discount' => 0,
                    'total' => $line,
                ]);
            }
            $order->update([
                'total_amount' => $totalAmount,
                'provider_ids' => array_values(array_unique($providerIds)),
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
                        $imageUrl = $item->product->image_url; // Use the accessor
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
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)
                    ? $row->created_at->copy()->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s')
                    : null;
            })
            ->addColumn('actions', function ($row) {
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary edit-order" title="Edit" data-id="'.$row->id.'" data-action="edit" data-modal="#orderModal">';
                $btns .= '<i class="fas fa-pencil-alt"></i></button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger delete-order" title="Delete" data-id="'.$row->id.'" data-delete-url="/'.(auth()->user()->hasRole('admin') ? 'admin' : 'provider').'/orders/'.$row->id.'">';
                $btns .= '<i class="fas fa-trash"></i></button>';
                $btns .= '</div>'; 
                return $btns;
            })
            ->rawColumns(['actions', 'order_status', 'products'])
            ->toJson();
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
