<?php

namespace Modules\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Products\Models\Category;
use Modules\Products\Models\Product;
use Yajra\DataTables\DataTables;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();
        return view('products::categories.index');
    }

    public function create()
    {
        $this->authorizeAdmin();
        $parents = Category::orderBy('name')->get();
        return view('products::categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        
        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'parent_id' => ['nullable', 'exists:categories,id'],
                'image' => ['required', 'image'],
                'description' => ['required', 'string']
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
            $path = $request->file('image')->store('categories', 'public');
            $data['image'] = $path;
        }

        Category::create($data);
        
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Category created successfully')
            ]);
        }
        
        return redirect()->route('admin.categories.index')->with('status', __('Category created'));
    }

    public function edit(Category $category)
    {
        $this->authorizeAdmin();
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($category);
        }
        
        $parents = Category::where('id', '!=', $category->id)->orderBy('name')->get();
        return view('products::categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeAdmin();
        
        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'parent_id' => ['nullable', 'exists:categories,id'],
                'image' => ['nullable', 'image'],
                'description' => ['required', 'string']
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
            $path = $request->file('image')->store('categories', 'public');
            $data['image'] = $path;
        }

        $category->update($data);
        
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Category updated successfully')
            ]);
        }
        
        return redirect()->route('admin.categories.index')->with('status', __('Category updated'));
    }

    public function destroy(Category $category)
    {
        $this->authorizeAdmin();
        $category->delete();
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Category deleted successfully')
            ]);
        }
        
        return redirect()->route('admin.categories.index')->with('status', __('Category deleted'));
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user() && Auth::user()->hasRole('admin'), 403);
    }

    public function data(DataTables $dataTables)
    {
        $this->authorizeAdmin();
        $query = Category::with(['parent', 'products']);
        return $dataTables->eloquent($query)
            ->addColumn('parent', fn($row) => optional($row->parent)->name)
            ->addColumn('image', function($row){
                if (!$row->image) return '';
                $src = str_starts_with($row->image, 'http') ? $row->image : asset('storage/' . $row->image);
                return '<img src="'.$src.'" alt="'.$row->name.'" style="height:40px;width:40px;object-fit:cover;border-radius:4px;" />';
            })
            ->addColumn('description', fn($row) => e(Str::limit((string)$row->description, 80)))
            ->addColumn('products_count', fn($row) => $row->products->count())
            ->addColumn('actions', function($row){
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openCategoryModal('.$row->id.')">';
                $btns .= '<i class="fas fa-edit"></i> Edit</button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger js-delete" data-delete-url="'.route('admin.categories.destroy', $row->id).'">';
                $btns .= '<i class="fas fa-trash"></i> Delete</button>';
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions','image'])
            ->toJson();
    }

    // Storefront: list all categories as cards
    public function storefrontIndex()
    {
        $categories = Category::orderBy('name')->get(['id','name','image','description']);
        return view('categories', compact('categories'));
    }

    // Storefront: show products of a category
    public function storefrontShow($id)
    {
        $category = Category::findOrFail($id);
        $products = Product::where('is_approved', true)
            ->where('category_id', $category->id)
            ->orderByDesc('id')
            ->paginate(12, ['id','title','price','image']);
        return view('category-products', compact('category', 'products'));
    }

    // Note: Admin resource uses index(). Storefront uses dedicated routes/methods above.
}


