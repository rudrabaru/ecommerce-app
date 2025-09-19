<?php

namespace Modules\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Products\Models\Category;
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
                'parent_id' => ['nullable', 'exists:categories,id']
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
                'parent_id' => ['nullable', 'exists:categories,id']
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
            ->addColumn('products_count', fn($row) => $row->products->count())
            ->addColumn('actions', function($row){
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openCategoryModal('.$row->id.')">';
                $btns .= '<i class="fas fa-edit"></i> Edit</button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger" onclick="deleteCategory('.$row->id.')">';
                $btns .= '<i class="fas fa-trash"></i> Delete</button>';
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
}


