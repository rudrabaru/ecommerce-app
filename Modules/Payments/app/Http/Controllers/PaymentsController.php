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
        $isProvider = Auth::user()->hasRole('provider');
        $providerId = $isProvider ? Auth::id() : null;

        // Build aggregated query: group payments by order_id
        if ($isProvider) {
            // For providers: show payments for orders where they're involved
            // Handle both cases:
            // 1. Manual orders: per-provider payment records (match by amount)
            // 2. User-placed orders: single payment record (calculate provider's portion from order items)
            $providerTotalSubquery = DB::table('order_items')
                ->select('order_id', DB::raw('SUM(line_total - COALESCE(line_discount, 0)) as provider_total'))
                ->where('provider_id', $providerId)
                ->groupBy('order_id');
            
            $query = DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->joinSub($providerTotalSubquery, 'provider_totals', function($join) {
                    $join->on('provider_totals.order_id', '=', 'payments.order_id');
                })
                ->whereRaw('JSON_CONTAINS(orders.provider_ids, ?)', [json_encode($providerId)])
                // Include all payments for orders where provider has items
                // (works for both per-provider payments and single payment records)
                ->select(
                    DB::raw('MIN(payments.id) as id'),
                    'payments.order_id',
                    DB::raw('SUM(payments.amount) as amount'),
                    DB::raw('MIN(payments.payment_method_id) as payment_method_id'),
                    DB::raw('MIN(payments.created_at) as created_at'),
                    DB::raw('GROUP_CONCAT(DISTINCT payments.status) as statuses'),
                    'orders.order_number',
                    DB::raw('provider_totals.provider_total as provider_portion')
                )
                ->groupBy('payments.order_id', 'orders.order_number', 'provider_totals.provider_total');
        } else {
            // For admin: aggregate all payments per order
            $query = DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->select(
                    DB::raw('MIN(payments.id) as id'),
                    'payments.order_id',
                    DB::raw('SUM(payments.amount) as amount'),
                    DB::raw('MIN(payments.payment_method_id) as payment_method_id'),
                    DB::raw('MIN(payments.created_at) as created_at'),
                    DB::raw('GROUP_CONCAT(DISTINCT payments.status) as statuses'),
                    'orders.order_number'
                )
                ->groupBy('payments.order_id', 'orders.order_number');
        }

        return $dataTables->query($query)
            ->addColumn('order_number', function ($row) {
                return $row->order_number ?: $row->order_id;
            })
            ->addColumn('payment_method', function ($row) {
                if ($row->payment_method_id) {
                    $pm = PaymentMethod::find($row->payment_method_id);
                    return $pm ? ($pm->display_name ?? $pm->name) : '-';
                }
                return '-';
            })
            ->editColumn('amount', function ($row) use ($isProvider, $providerId) {
                // For providers: show their portion (from subquery or calculated)
                if ($isProvider) {
                    // Use provider_portion from query if available, otherwise calculate
                    if (isset($row->provider_portion)) {
                        return '$' . number_format((float) $row->provider_portion, 2);
                    }
                    // Fallback: calculate from order items
                    $order = Order::with('orderItems')->find($row->order_id);
                    if ($order) {
                        $items = $order->orderItems->where('provider_id', $providerId);
                    $subtotal = $items->sum(function ($item) { return (float) ($item->line_total ?? $item->total); });
                    $discount = $items->sum(function ($item) { return (float) ($item->line_discount ?? 0); });
                    $final = max(0, (float)$subtotal - (float)$discount);
                    return '$' . number_format($final, 2);
                }
                }
                // Admin: show aggregated total
                return '$' . number_format((float) $row->amount, 2);
            })
            ->addColumn('status', function ($row) {
                // Aggregate statuses: if all paid = paid, if any refunded = refunded, else unpaid
                $statuses = explode(',', $row->statuses ?? '');
                $statuses = array_map(function($s) {
                    $s = trim($s);
                    if (in_array($s, ['pending', 'processing', 'failed', 'cancelled'], true)) {
                        return 'unpaid';
                    }
                    return $s;
                }, $statuses);
                
                $final = 'unpaid';
                if (in_array('refunded', $statuses)) {
                    $final = 'refunded';
                } elseif (count($statuses) > 0 && count(array_unique($statuses)) === 1 && $statuses[0] === 'paid') {
                    $final = 'paid';
                }
                
                $map = [
                    'unpaid' => 'bg-warning',
                    'paid' => 'bg-success',
                    'refunded' => 'bg-secondary',
                ];
                $cls = $map[$final] ?? 'bg-secondary';
                return '<span class="badge rounded-pill ' . $cls . '">' . ucfirst($final) . '</span>';
            })
            ->editColumn('created_at', function ($row) {
                if ($row->created_at) {
                    return \Carbon\Carbon::parse($row->created_at)
                        ->setTimezone('Asia/Kolkata')
                        ->format('d-m-Y H:i:s');
                }
                return null;
            })
            ->addColumn('actions', function ($row) {
                // For aggregated records, we need to handle multiple payments
                // Show edit/delete for the first payment ID, or we could show a special action
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary editBtn" title="Edit" data-module="payments" data-id="'.$row->id.'">';
                $btns .= '<i class="fas fa-pencil-alt"></i></button>';
                $btns .= '<button class="btn btn-sm btn-outline-danger deleteBtn" data-module="payments" data-id="'.$row->id.'" title="Delete">';
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
            'status' => ['required', 'in:unpaid,paid,refunded'],
        ]);

        $payment->update($validated);

        // Optional: if refunded, prevent rating by keeping payment unpaid/refunded aggregation
        // No destructive changes to order or items

        return $request->wantsJson() || $request->ajax()
            ? response()->json([
                'success' => true, 
                'message' => __('Payment updated successfully'),
                'refresh_tables' => ['payments-table', 'orders-table'] // Trigger refresh for both tables
            ])
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
