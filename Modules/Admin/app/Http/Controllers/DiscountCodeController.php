<?php

namespace Modules\Admin\app\Http\Controllers;

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
        return view('admin::backend.pages.discounts.index');
    }

    public function data(DataTables $dataTables)
    {
        $query = DiscountCode::query()->with('categories');
        return $dataTables->eloquent($query)
            ->addColumn('categories', function ($row) {
                return view('admin::backend.pages.discounts.partials.category-list', [
                    'categories' => $row->categories
                ])->render();
            })
            ->editColumn('is_active', fn ($r) => $r->is_active ? 'Active' : 'Inactive')
            ->editColumn('valid_from', function ($row) {
                return $row->valid_from
                    ? $row->valid_from->copy()->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s')
                    : '';
            })
            ->editColumn('valid_until', function ($row) {
                return $row->valid_until
                    ? $row->valid_until->copy()->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s')
                    : '';
            })
            ->addColumn('actions', function ($row) {
                $del = route('admin.discounts.destroy', $row->id);
                return '<div class="btn-group" role="group">'
                    .'<button class="btn btn-sm btn-outline-primary" title="Edit" data-action="edit" data-modal="#discountEditModal" data-bs-toggle="modal" data-bs-target="#discountEditModal" data-id="'.$row->id.'"><i class="fas fa-pencil-alt"></i></button>'
                    .'<button class="btn btn-sm btn-outline-danger js-delete" title="Delete" data-delete-url="'.$del.'"><i class="fas fa-trash"></i></button>'
                    .'</div>';
            })
            ->rawColumns(['actions','categories'])
            ->toJson();
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get(['id','name']);
        return response()->json(['categories' => $categories]);
    }

    public function store(StoreDiscountCodeRequest $request)
    {
        try {
            $data = $request->validated();
            $categoryIds = $request->input('category_ids', []);
            
            $discount = DiscountCode::create($data);
            $discount->categories()->sync($categoryIds);
            
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Discount code created successfully']);
            }

            return redirect()->route('admin.discounts.index')->with('status', 'Discount code created successfully');
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to create discount code', 'error' => $e->getMessage()], 422);
            }
            return redirect()->back()->withErrors(['error' => 'Failed to create discount code'])->withInput();
        }
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
        try {
            $data = $request->validated();
            $categoryIds = $request->input('category_ids', []);
            
            $discount_code->update($data);
            $discount_code->categories()->sync($categoryIds);
            
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Discount code updated successfully']);
            }

            return redirect()->route('admin.discounts.index')->with('status', 'Discount code updated successfully');
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to update discount code', 'error' => $e->getMessage()], 422);
            }
            return redirect()->back()->withErrors(['error' => 'Failed to update discount code'])->withInput();
        }
    }

    public function destroy(DiscountCode $discount_code)
    {
        // Detach pivots then hard delete so it is removed from DB as requested
        try {
            $discount_code->categories()->detach();
        } catch (\Throwable $throwable) {
            // ignore if not set
        }

        $discount_code->forceDelete();
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Deleted']);
        }

        return redirect()->route('admin.discounts.index')->with('status', 'Discount code deleted');
    }
}