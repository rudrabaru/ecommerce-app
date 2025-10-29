<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('payments::index');
    }

    /**
     * Get payments data for DataTable
     */
    public function data(DataTables $dataTables)
    {
        $query = Payment::query()->with(['order', 'paymentMethod']);

        // Provider should see only payments for their orders
        if (Auth::user()->hasRole('provider')) {
            // Orders track multiple providers in JSON column provider_ids
            $query->whereHas('order', function ($q) {
                $q->whereJsonContains('provider_ids', Auth::id());
            });
        }

        return $dataTables->eloquent($query)
            ->addColumn('order_number', function ($row) {
                return optional($row->order)->order_number ?: optional($row->order)->id;
            })
            ->addColumn('payment_method', function ($row) {
                return optional($row->paymentMethod)->name ?: '-';
            })
            ->editColumn('amount', fn ($row) => '$' . number_format($row->amount, 2))
            ->editColumn('status', function ($row) {
                $badgeClass = match($row->status) {
                    'pending' => 'badge-warning',
                    'paid' => 'badge-success',
                    default => 'badge-secondary'
                };
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($row->status) . '</span>';
            })
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)
                    ? $row->created_at->copy()->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s')
                    : null;
            })
            ->addColumn('actions', function ($row) {
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary js-payment-edit" title="Edit" data-id="'.$row->id.'">';
                $btns .= '<i class="fas fa-pencil-alt"></i></button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger js-payment-delete" title="Delete" data-id="'.$row->id.'">';
                $btns .= '<i class="fas fa-trash"></i></button>';
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions', 'status'])
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('payments::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,paid']
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $this->authorizePaymentAction($order);

        Payment::create($validated + ['currency' => $request->input('currency', 'USD')]);

        return $request->wantsJson() || $request->ajax()
            ? response()->json(['success' => true, 'message' => __('Payment created successfully')])
            : back()->with('status', __('Payment created'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $payment = Payment::with(['order','paymentMethod'])->findOrFail($id);
        $this->authorizePaymentAction($payment->order);
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($payment);
        }
        return view('payments::show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $payment = Payment::findOrFail($id);
        $this->authorizePaymentAction($payment->order);
        if (request()->wantsJson() || request()->ajax()) {
            // Return only fields needed for edit; currency omitted per requirement
            return response()->json([
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'payment_method_id' => $payment->payment_method_id,
                'amount' => $payment->amount,
                'status' => $payment->status,
            ]);
        }
        return view('payments::edit', compact('payment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $this->authorizePaymentAction($payment->order);

        $validated = $request->validate([
            'order_id' => ['sometimes', 'exists:orders,id'],
            'payment_method_id' => ['sometimes', 'exists:payment_methods,id'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,paid'],
        ]);

        $payment->update($validated);

        return $request->wantsJson() || $request->ajax()
            ? response()->json(['success' => true, 'message' => __('Payment updated successfully')])
            : back()->with('status', __('Payment updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $this->authorizePaymentAction($payment->order);
        $payment->delete();

        return request()->wantsJson() || request()->ajax()
            ? response()->json(['success' => true, 'message' => __('Payment deleted successfully')])
            : back()->with('status', __('Payment deleted'));
    }

    private function authorizePaymentAction(Order $order): void
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return;
        }
        abort_unless($order->containsProvider($user->id), 403);
    }
}
