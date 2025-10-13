<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiscountCodeRequest;
use App\Http\Requests\UpdateDiscountCodeRequest;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Modules\Products\Models\Category;
use Yajra\DataTables\DataTables;

class DiscountCodeController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('admin.discounts.index');
    }

    public function data(DataTables $dataTables)
    {
        $query = DiscountCode::query()->with('categories');
        return $dataTables->eloquent($query)
            ->addColumn('categories', function ($row) {
                return $row->categories->pluck('name')->implode(', ');
            })
            ->editColumn('is_active', fn ($r) => $r->is_active ? 'Active' : 'Inactive')
            ->addColumn('actions', function ($row) {
                $del = route('admin.discounts.destroy', $row->id);
                return '<div class="btn-group" role="group">'
                    .'<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#discountModal" data-discount-id="'.$row->id.'">Edit</button>'
                    .'<button class="btn btn-sm btn-outline-danger js-delete" data-delete-url="'.$del.'">Delete</button>'
                    .'</div>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get(['id','name']);
        return response()->json(['categories' => $categories]);
    }

    public function store(StoreDiscountCodeRequest $request)
    {
        $data = $request->validated();
        // Sync both single category_id and pivot for future extensibility
        $categoryId = $data['category_id'];
        $discount = DiscountCode::create($data);
        // Persist primary category_id as well as pivot for compatibility
        $discount->category_id = $categoryId;
        $discount->save();
        $discount->categories()->sync([$categoryId]);
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Discount code created']);
        }
        return redirect()->route('admin.discounts.index')->with('status', 'Discount code created');
    }

    public function edit(DiscountCode $discount_code)
    {
        $categories = Category::orderBy('name')->get(['id','name']);
        return response()->json([
            'discount' => $discount_code->load('categories:id'),
            'categories' => $categories
        ]);
    }

    public function update(UpdateDiscountCodeRequest $request, DiscountCode $discount_code)
    {
        $data = $request->validated();
        $categoryId = $data['category_id'];
        $discount_code->update($data);
        // Update primary category_id and pivot
        $discount_code->category_id = $categoryId;
        $discount_code->save();
        $discount_code->categories()->sync([$categoryId]);
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Discount code updated']);
        }
        return redirect()->route('admin.discounts.index')->with('status', 'Discount code updated');
    }

    public function destroy(DiscountCode $discount_code)
    {
        // Detach pivots then hard delete so it is removed from DB as requested
        try {
            $discount_code->categories()->detach();
        } catch (\Throwable $e) {
            // ignore if not set
        }
        $discount_code->forceDelete();
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Deleted']);
        }
        return redirect()->route('admin.discounts.index')->with('status', 'Discount code deleted');
    }
}
