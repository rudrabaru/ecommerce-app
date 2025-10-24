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
                'product_id' => ['required', 'exists:products,id'],
                'quantity' => ['required', 'integer', 'min:1'],
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

        // Get product details
        $product = \Modules\Products\Models\Product::findOrFail($validated['product_id']);

        // Create order with provider_id from product
        $order = Order::create([
            'user_id' => $validated['user_id'],
            'provider_id' => $product->provider_id,
            'total_amount' => $product->price * $validated['quantity'],
            'status' => $validated['status'] ?? 'pending',
            'order_status' => $validated['status'] ?? 'pending',
            'shipping_address' => $validated['shipping_address'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create corresponding order item row with provider linkage
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'provider_id' => $product->provider_id,
            'quantity' => (int) $validated['quantity'],
            'unit_price' => $product->price,
            'line_total' => $product->price * (int) $validated['quantity'],
            'line_discount' => 0,
            'total' => $product->price * (int) $validated['quantity'],
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
        $order = Order::findOrFail($id);
        $this->authorizeUpdate($order);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($order);
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

        $order->update($validated);

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

        // Role-based filtering via orders.provider_id (more efficient)
        if (Auth::user()->hasRole('provider')) {
            $query->where('provider_id', Auth::id());
        }

        return $dataTables->eloquent($query)
            ->addColumn('customer_name', fn ($row) => $row->user->name)
            ->addColumn('products', function ($row) {
                $products = $row->orderItems->map(function ($item) {
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
            ->addColumn('total', fn ($row) => '$' . number_format($row->total_amount, 2))
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
                $btns .= '<button class="btn btn-sm btn-outline-primary edit-order" data-id="'.$row->id.'" data-action="edit" data-modal="#orderModal">';
                $btns .= '<i class="fas fa-edit"></i> Edit</button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger delete-order" data-id="'.$row->id.'" data-delete-url="/'.(auth()->user()->hasRole('admin') ? 'admin' : 'provider').'/orders/'.$row->id.'">';
                $btns .= '<i class="fas fa-trash"></i> Delete</button>';
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
        abort_unless($order->provider_id === $user->id, 403);
    }

    private function authorizeUpdate(Order $order): void
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return;
        }
        abort_unless($order->provider_id === $user->id, 403);
    }
}
