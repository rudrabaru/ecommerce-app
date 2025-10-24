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
            ->addColumn('parent', fn ($row) => optional($row->parent)->name)
            ->addColumn('image', function ($row) {
                if (!$row->image) {
                    return '';
                }
                $image = trim((string)$row->image, " \t\n\r\0\x0B\"'{}");
                if (strlen($image) && $image[0] === '@') {
                    $image = substr($image, 1); // strip accidental leading '@'
                }
                // If URL is encoded (e.g. http%3A%2F%2F...), decode it first
                if (preg_match('#^(https?%3A|http%3A)//#i', $image)) {
                    $image = urldecode($image);
                }
                // Replace spaces with %20 to avoid broken src
                $image = str_replace(' ', '%20', $image);
                // Accept absolute http/https or protocol-relative URLs
                if (preg_match('#^https?://#i', $image) || preg_match('#^//#', $image)) {
                    // If source is via.placeholder.com, convert to placehold.co which is more reliable
                    try {
                        $urlParts = parse_url($image);
                        if (!empty($urlParts['host']) && stripos($urlParts['host'], 'via.placeholder.com') !== false) {
                            // Expected path like /640x480.png/0099ee?text=Hello+World
                            $path = $urlParts['path'] ?? '';
                            $query = $urlParts['query'] ?? '';
                            $text = '';
                            parse_str($query, $q);
                            if (!empty($q['text'])) {
                                $text = $q['text'];
                            }
                            if (preg_match('#/(\d+x\d+)\.png/([0-9a-fA-F]{3,6})#', $path, $m)) {
                                $size = $m[1];
                                $bg = $m[2];
                                $src = 'https://placehold.co/' . $size . '/' . $bg . '/ffffff?text=' . urlencode($text ?: '');
                            } elseif (preg_match('#/(\d+x\d+)#', $path, $m)) {
                                $size = $m[1];
                                $src = 'https://placehold.co/' . $size . '?text=' . urlencode($text ?: '');
                            } else {
                                $src = 'https://placehold.co/40x40?text=';
                            }
                        } else {
                            $src = $image;
                        }
                    } catch (\Throwable $e) {
                        $src = $image;
                    }
                } else {
                    // Normalize common stored paths
                    if (\Illuminate\Support\Str::startsWith($image, ['storage/'])) {
                        $src = asset($image);
                    } elseif (\Illuminate\Support\Str::startsWith($image, ['public/'])) {
                        $src = asset(str_replace('public/', 'storage/', $image));
                    } else {
                        $src = asset('storage/' . ltrim($image, '/'));
                    }
                }
                $alt = e($row->name);
                $fallback = 'https://via.placeholder.com/40x40.png?text=%20';
                $html = '<img src="'.e($src).'" alt="'.$alt.'" style="height:40px;width:40px;object-fit:cover;border-radius:4px;" referrerpolicy="no-referrer" crossorigin="anonymous" onerror="this.onerror=null;this.src=\''.$fallback.'\';" />';
                return $html;
            })
            ->addColumn('description', fn ($row) => e(Str::limit((string)$row->description, 80)))
            ->addColumn('products_count', fn ($row) => $row->products->count())
            ->addColumn('actions', function ($row) {
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openCategoryModal('.$row->id.')" title="Edit Category">';
                $btns .= '<i class="fas fa-pencil-alt"></i></button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger js-delete" data-delete-url="'.route('admin.categories.destroy', $row->id).'" title="Delete Category">';
                $btns .= '<i class="fas fa-trash"></i></button>';
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions','image'])
            ->toJson();
    }

    // Storefront: list all categories as cards
    public function storefrontIndex()
    {
        // 6 category cards per page on storefront index
        $query = Category::query();
        if ($q = request('q')) {
            $query->where('name', 'like', "%$q%")
                  ->orWhere('description', 'like', "%$q%");
        }
        $categories = $query->orderBy('name')->paginate(6)->withQueryString();
        if (request()->ajax()) {
            $html = view('components.category-cards', ['categories' => $categories])->render();
            $pagination = view('components.pagination', ['paginator' => $categories])->render();
            return response()->json(['html' => $html, 'pagination' => $pagination, 'page' => (int)$categories->currentPage()]);
        }
        return view('categories', compact('categories'));
    }

    // Storefront: show products of a category
    public function storefrontShow($id)
    {
        $category = Category::findOrFail($id);
        // 12 products per page within a category
        $productsQuery = Product::where('is_approved', true)
            ->where('category_id', $category->id)
            ->orderByDesc('id');

        if ($q = request('q')) {
            $productsQuery->where(function ($qb) use ($q) {
                $qb->where('title', 'like', "%$q%")
                   ->orWhere('description', 'like', "%$q%");
            });
        }

        $products = $productsQuery->paginate(12, ['id','title','price','image'])->withQueryString();
        if (request()->ajax()) {
            $html = view('components.product-cards', ['products' => $products])->render();
            $pagination = view('components.pagination', ['paginator' => $products])->render();
            return response()->json(['html' => $html, 'pagination' => $pagination, 'page' => (int)$products->currentPage()]);
        }
        return view('category-products', compact('category', 'products'));
    }

    // Note: Admin resource uses index(). Storefront uses dedicated routes/methods above.
}
