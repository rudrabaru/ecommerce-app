<?php

namespace Modules\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Products\Models\Product;
use Modules\Products\Models\Category;
use Yajra\DataTables\DataTables;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('products::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('products::create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'price' => ['required', 'numeric', 'min:0'],
                'stock' => ['required', 'integer', 'min:0'],
                'category_id' => ['required', 'exists:categories,id'],
                'image' => ['nullable', 'image']
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

        $validated['provider_id'] = Auth::id();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        }

        Product::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Product created successfully')
            ]);
        }

        $route = auth()->user()->hasRole('admin') ? 'admin.products.index' : 'provider.products.index';
        return redirect()->route($route)->with('status', __('Product created'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $product = Product::with('category', 'provider')->findOrFail($id);
        $this->authorizeView($product);
        return view('products::show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $this->authorizeUpdate($product);
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($product);
        }
        
        $categories = Category::orderBy('name')->get();
        return view('products::edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $this->authorizeUpdate($product);

        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'price' => ['required', 'numeric', 'min:0'],
                'stock' => ['required', 'integer', 'min:0'],
                'category_id' => ['required', 'exists:categories,id'],
                'image' => ['nullable', 'image']
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

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        }

        $product->update($validated);
        
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Product updated successfully')
            ]);
        }
        
        $route = auth()->user()->hasRole('admin') ? 'admin.products.index' : 'provider.products.index';
        return redirect()->route($route)->with('status', __('Product updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $this->authorizeUpdate($product);
        $product->delete();
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Product deleted successfully')
            ]);
        }
        
        $route = auth()->user()->hasRole('admin') ? 'admin.products.index' : 'provider.products.index';
        return redirect()->route($route)->with('status', __('Product deleted'));
    }

    public function approve($id)
    {
        $this->authorizeAdmin();
        $product = Product::findOrFail($id);
        $product->is_approved = true;
        $product->save();
        return redirect()->back()->with('status', __('Product approved'));
    }

    public function block($id)
    {
        $this->authorizeAdmin();
        $product = Product::findOrFail($id);
        $product->is_approved = false;
        $product->save();
        return redirect()->back()->with('status', __('Product blocked'));
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user() && Auth::user()->hasRole('admin'), 403);
    }

    private function authorizeUpdate(Product $product): void
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) { return; }
        abort_unless($product->provider_id === $user->id, 403);
    }

    private function authorizeView(Product $product): void
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) { return; }
        abort_unless($product->is_approved || $product->provider_id === $user->id, 403);
    }

    public function data(DataTables $dataTables)
    {
        $query = Product::query()->with(['category', 'provider']);
        if (!Auth::user()->hasRole('admin')) {
            $query->where('provider_id', Auth::id());
        }
        return $dataTables->eloquent($query)
            ->addColumn('category', fn($row) => optional($row->category)->name)
            ->addColumn('status', fn($row) => $row->is_approved ? 'Approved' : 'Pending')
            ->addColumn('actions', function($row){
                $isAdmin = Auth::user()->hasRole('admin');
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary edit-product" data-id="'.$row->id.'" data-bs-toggle="modal" data-bs-target="#productModal" onclick="openProductModal('.$row->id.')">';
                $btns .= '<i class="fas fa-edit"></i> Edit</button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger delete-product" data-id="'.$row->id.'" onclick="deleteProduct('.$row->id.')">';
                $btns .= '<i class="fas fa-trash"></i> Delete</button>';
                if ($isAdmin) {
                    $approve = route('admin.products.approve', $row->id);
                    $block = route('admin.products.block', $row->id);
                    if ($row->is_approved) {
                        $btns .= '<a href="'.$block.'" class="btn btn-sm btn-warning js-ajax-link" title="Block Product">';
                        $btns .= '<i class="fas fa-ban"></i> Block</a>';
                    } else {
                        $btns .= '<a href="'.$approve.'" class="btn btn-sm btn-success js-ajax-link" title="Approve Product">';
                        $btns .= '<i class="fas fa-check"></i> Approve</a>';
                    }
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
}
