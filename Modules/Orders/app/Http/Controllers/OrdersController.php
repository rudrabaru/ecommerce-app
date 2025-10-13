<?php

namespace Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
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

        $validated['provider_id'] = $product->provider_id;
        $validated['unit_price'] = $product->price;
        $validated['total_amount'] = $product->price * $validated['quantity'];

        Order::create($validated);

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
        $order = Order::with(['user', 'provider', 'product'])->findOrFail($id);
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
                'status' => ['required', 'in:pending,confirmed,shipped,delivered,cancelled'],
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
        $query = Order::query()->with(['user', 'provider', 'product']);

        // Role-based filtering
        if (Auth::user()->hasRole('provider')) {
            $query->where('provider_id', Auth::id());
        }

        return $dataTables->eloquent($query)
            ->addColumn('customer_name', fn ($row) => $row->user->name)
            ->addColumn('product_name', fn ($row) => $row->product->title)
            ->addColumn('total', fn ($row) => '$' . number_format($row->total_amount, 2))
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)
                    ? $row->created_at->copy()->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s')
                    : null;
            })
            ->addColumn('actions', function ($row) {
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary edit-order" data-id="'.$row->id.'" data-bs-toggle="modal" data-bs-target="#orderModal" onclick="openOrderModal('.$row->id.')">';
                $btns .= '<i class="fas fa-edit"></i> Edit</button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger delete-order" data-id="'.$row->id.'" onclick="deleteOrder('.$row->id.')">';
                $btns .= '<i class="fas fa-trash"></i> Delete</button>';
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions'])
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
