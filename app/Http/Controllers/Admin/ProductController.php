<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Products\Models\Product;
use Modules\Products\Models\Category;
use Yajra\DataTables\DataTables;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin.products');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $providers = User::whereHas('roles', function($q) { 
            $q->where('name', 'provider'); 
        })->get(['id', 'name']);
        
        $categories = Category::orderBy('name')->get(['id', 'name']);
        
        return response()->json([
            'providers' => $providers,
            'categories' => $categories
        ]);
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
                'provider_id' => ['required', 'exists:users,id'],
                'category_id' => ['required', 'exists:categories,id'],
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
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

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }

        $validated['is_approved'] = true; // Admin created products are auto-approved
        $validated['status'] = 'active';

        $product = Product::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully'
            ]);
        }

        return redirect()->route('admin.products.index')->with('status', 'Product created');
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $product = Product::with(['provider', 'category'])->findOrFail($id);
        return response()->json($product);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product = Product::with(['provider', 'category'])->findOrFail($id);
        $providers = User::whereHas('roles', function($q) { 
            $q->where('name', 'provider'); 
        })->get(['id', 'name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'product' => $product,
                'providers' => $providers,
                'categories' => $categories
            ]);
        }
        
        return view('admin.products.edit', compact('product', 'providers', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'price' => ['required', 'numeric', 'min:0'],
                'stock' => ['required', 'integer', 'min:0'],
                'provider_id' => ['required', 'exists:users,id'],
                'category_id' => ['required', 'exists:categories,id'],
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
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

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                \Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }

        $product->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully'
            ]);
        }

        return redirect()->route('admin.products.index')->with('status', 'Product updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // Delete image if exists
        if ($product->image) {
            \Storage::disk('public')->delete($product->image);
        }
        
        $product->delete();
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        }
        
        return redirect()->route('admin.products.index')->with('status', 'Product deleted');
    }

    /**
     * Get products data for DataTable
     */
    public function data(DataTables $dataTables)
    {
        $query = Product::with(['provider', 'category'])
            ->select('products.*');

        return $dataTables->eloquent($query)
            ->editColumn('created_at', function ($row) {
                return $row->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s');
            })
            ->editColumn('price', function ($row) {
                return '$' . number_format($row->price, 2);
            })
            ->addColumn('provider_name', function ($row) {
                return $row->provider ? $row->provider->name : 'N/A';
            })
            ->addColumn('category_name', function ($row) {
                return $row->category ? $row->category->name : 'N/A';
            })
            ->addColumn('status', function ($row) {
                $badgeClass = $row->is_approved ? 'badge-success' : 'badge-warning';
                $statusText = $row->is_approved ? 'Approved' : 'Pending';
                return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
            })
            ->addColumn('actions', function ($row) {
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary edit-product" data-id="' . $row->id . '" onclick="openProductModal(' . $row->id . ')">';
                $btns .= '<i class="fas fa-edit"></i> Edit</button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger delete-product" data-id="' . $row->id . '" onclick="deleteProduct(' . $row->id . ')">';
                $btns .= '<i class="fas fa-trash"></i> Delete</button>';
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['status', 'actions'])
            ->toJson();
    }
}

