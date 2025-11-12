<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\RefundRequest;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                $statuses = array_filter(array_map(function($s) {
                    $s = strtolower(trim($s));
                    if ($s === '') {
                        return null;
                    }
                    if (in_array($s, ['pending', 'processing', 'failed', 'cancelled'], true)) {
                        return 'unpaid';
                    }
                    if ($s === 'refunded') {
                        return 'paid';
                    }
                    return $s;
                }, explode(',', $row->statuses ?? '')));

                $final = 'unpaid';
                if (!empty($statuses) && !in_array('unpaid', $statuses, true)) {
                    $final = 'paid';
                }

                $map = [
                    'unpaid' => 'bg-warning',
                    'paid' => 'bg-success',
                ];
                $cls = $map[$final] ?? 'bg-warning';
                return '<span class="badge rounded-pill ' . $cls . '">' . ucfirst($final) . '</span>';
            })
            ->addColumn('refund_action', function ($row) use ($isProvider, $providerId) {
                $refunds = RefundRequest::where('order_id', $row->order_id)
                    ->when($isProvider, function ($query) use ($providerId) {
                        $query->where(function ($q) use ($providerId) {
                            $q->whereNull('provider_id');
                            if ($providerId) {
                                $q->orWhere('provider_id', $providerId);
                            }
                        });
                    })
                    ->get();

                if ($refunds->isEmpty()) {
                    return '-';
                }

                // Separate full order refunds (order_item_id is null) from item-level refunds
                $fullOrderRefunds = $refunds->whereNull('order_item_id');
                $itemLevelRefunds = $refunds->whereNotNull('order_item_id');

                // For full order returns, aggregate into a single refund request display
                if ($fullOrderRefunds->isNotEmpty()) {
                    $fullOrderRefund = $fullOrderRefunds->first(); // Should only be one for full order
                    
                    if ($fullOrderRefund->status === RefundRequest::STATUS_FAILED) {
                        $amount = number_format((float) $fullOrderRefund->amount, 2);
                        $label = strtoupper($fullOrderRefund->payment_method);
                        $route = route('admin.payments.refunds.approve', $fullOrderRefund->id);
                        $isCod = strtolower($fullOrderRefund->payment_method) === 'cod';
                        $hasBankDetails = !empty($fullOrderRefund->account_holder_name) || !empty($fullOrderRefund->bank_name);
                        $button = '<button type="button" class="btn btn-sm btn-outline-danger js-approve-refund"'
                            . ' data-refund-id="' . e((string) $fullOrderRefund->id) . '"'
                            . ' data-url="' . e($route) . '"'
                            . ' data-amount="' . e($amount) . '"'
                            . ' data-method="' . e($label) . '"'
                            . ' data-is-cod="' . ($isCod ? '1' : '0') . '"'
                            . ' data-has-bank-details="' . ($hasBankDetails ? '1' : '0') . '"'
                            . ($hasBankDetails ? ' data-account-holder="' . e($fullOrderRefund->account_holder_name ?? '') . '"'
                                . ' data-bank-name="' . e($fullOrderRefund->bank_name ?? '') . '"'
                                . ' data-account-number="' . e($fullOrderRefund->account_number ?? '') . '"'
                                . ' data-ifsc="' . e($fullOrderRefund->ifsc ?? '') . '"' : '')
                            . '>Retry $' . e($amount) . ' (' . e($label) . ')</button>';
                        
                        // If there are item-level refunds too, show them below
                        if ($itemLevelRefunds->isNotEmpty()) {
                            $itemButtons = $this->formatItemRefundButtons($itemLevelRefunds, $isProvider);
                            return $button . '<br><small class="text-muted">Item Returns:</small><br>' . $itemButtons;
                        }
                        return $button;
                    }

                    if (in_array($fullOrderRefund->status, [RefundRequest::STATUS_PENDING, RefundRequest::STATUS_PROCESSING])) {
                        if ($isProvider) {
                            return '<span class="badge bg-warning text-dark">Pending Admin</span>';
                        }

                        $amount = number_format((float) $fullOrderRefund->amount, 2);
                        $label = strtoupper($fullOrderRefund->payment_method);
                        $route = route('admin.payments.refunds.approve', $fullOrderRefund->id);
                        $isCod = strtolower($fullOrderRefund->payment_method) === 'cod';
                        $hasBankDetails = !empty($fullOrderRefund->account_holder_name) || !empty($fullOrderRefund->bank_name);
                        $button = '<button type="button" class="btn btn-sm btn-outline-success js-approve-refund"'
                            . ' data-refund-id="' . e((string) $fullOrderRefund->id) . '"'
                            . ' data-url="' . e($route) . '"'
                            . ' data-amount="' . e($amount) . '"'
                            . ' data-method="' . e($label) . '"'
                            . ' data-is-cod="' . ($isCod ? '1' : '0') . '"'
                            . ' data-has-bank-details="' . ($hasBankDetails ? '1' : '0') . '"'
                            . ($hasBankDetails ? ' data-account-holder="' . e($fullOrderRefund->account_holder_name ?? '') . '"'
                                . ' data-bank-name="' . e($fullOrderRefund->bank_name ?? '') . '"'
                                . ' data-account-number="' . e($fullOrderRefund->account_number ?? '') . '"'
                                . ' data-ifsc="' . e($fullOrderRefund->ifsc ?? '') . '"' : '')
                            . '>Approve $' . e($amount) . ' (' . e($label) . ')</button>';
                        
                        // If there are item-level refunds too, show them below
                        if ($itemLevelRefunds->isNotEmpty()) {
                            $itemButtons = $this->formatItemRefundButtons($itemLevelRefunds, $isProvider);
                            return $button . '<br><small class="text-muted">Item Returns:</small><br>' . $itemButtons;
                        }
                        return $button;
                    }

                    if ($fullOrderRefund->status === RefundRequest::STATUS_COMPLETED) {
                        // Check if all refunds (full order + items) are completed
                        $allCompleted = $refunds->every(function ($r) {
                            return $r->status === RefundRequest::STATUS_COMPLETED;
                        });
                        if ($allCompleted) {
                            return '<span class="badge bg-success">Refunded</span>';
                        }
                    }
                }

                // Handle item-level refunds only (no full order refund)
                if ($itemLevelRefunds->isNotEmpty()) {
                    return $this->formatItemRefundButtons($itemLevelRefunds, $isProvider);
                }

                // All refunds completed
                return '<span class="badge bg-success">Refunded</span>';
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
            ->rawColumns(['actions', 'status', 'refund_action'])
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
     * Format item-level refund buttons for display
     */
    private function formatItemRefundButtons($itemLevelRefunds, bool $isProvider): string
    {
        $failed = $itemLevelRefunds->where('status', RefundRequest::STATUS_FAILED);
        if ($failed->isNotEmpty()) {
            return $failed->map(function (RefundRequest $request) {
                $amount = number_format((float) $request->amount, 2);
                $label = strtoupper($request->payment_method);
                $route = route('admin.payments.refunds.approve', $request->id);
                $isCod = strtolower($request->payment_method) === 'cod';
                $hasBankDetails = !empty($request->account_holder_name) || !empty($request->bank_name);
                return '<button type="button" class="btn btn-sm btn-outline-danger js-approve-refund"'
                    . ' data-refund-id="' . e((string) $request->id) . '"'
                    . ' data-url="' . e($route) . '"'
                    . ' data-amount="' . e($amount) . '"'
                    . ' data-method="' . e($label) . '"'
                    . ' data-is-cod="' . ($isCod ? '1' : '0') . '"'
                    . ' data-has-bank-details="' . ($hasBankDetails ? '1' : '0') . '"'
                    . ($hasBankDetails ? ' data-account-holder="' . e($request->account_holder_name ?? '') . '"'
                        . ' data-bank-name="' . e($request->bank_name ?? '') . '"'
                        . ' data-account-number="' . e($request->account_number ?? '') . '"'
                        . ' data-ifsc="' . e($request->ifsc ?? '') . '"' : '')
                    . '>Retry $' . e($amount) . ' (' . e($label) . ')</button>';
            })->implode('<br>');
        }

        $pending = $itemLevelRefunds->whereIn('status', [RefundRequest::STATUS_PENDING, RefundRequest::STATUS_PROCESSING]);
        if ($pending->isEmpty()) {
            return '<span class="badge bg-success">Refunded</span>';
        }

        if ($isProvider) {
            return '<span class="badge bg-warning text-dark">Pending Admin</span>';
        }

        return $pending->map(function (RefundRequest $request) {
            $amount = number_format((float) $request->amount, 2);
            $label = strtoupper($request->payment_method);
            $route = route('admin.payments.refunds.approve', $request->id);
            $isCod = strtolower($request->payment_method) === 'cod';
            $hasBankDetails = !empty($request->account_holder_name) || !empty($request->bank_name);
            return '<button type="button" class="btn btn-sm btn-outline-success js-approve-refund"'
                . ' data-refund-id="' . e((string) $request->id) . '"'
                . ' data-url="' . e($route) . '"'
                . ' data-amount="' . e($amount) . '"'
                . ' data-method="' . e($label) . '"'
                . ' data-is-cod="' . ($isCod ? '1' : '0') . '"'
                . ' data-has-bank-details="' . ($hasBankDetails ? '1' : '0') . '"'
                . ($hasBankDetails ? ' data-account-holder="' . e($request->account_holder_name ?? '') . '"'
                    . ' data-bank-name="' . e($request->bank_name ?? '') . '"'
                    . ' data-account-number="' . e($request->account_number ?? '') . '"'
                    . ' data-ifsc="' . e($request->ifsc ?? '') . '"' : '')
                . '>Approve $' . e($amount) . ' (' . e($label) . ')</button>';
        })->implode('<br>');
    }

    public function approveRefund(Request $request, RefundRequest $refundRequest)
    {
        abort_unless(Auth::user()->hasRole('admin'), 403);

        if ($refundRequest->status === RefundRequest::STATUS_COMPLETED) {
            return response()->json([
                'success' => true,
                'message' => __('Refund already processed'),
            ]);
        }

        try {
            DB::transaction(function () use ($refundRequest) {
                // Reload refund request with relationships
                $refundRequest->refresh();
                $refundRequest->loadMissing(['order', 'payment']);
                
                // Ensure order and payment are loaded
                if (!$refundRequest->order) {
                    throw new \RuntimeException('Order not found for refund request.');
                }
                
                $result = RefundService::processRefundRequest($refundRequest);
                
                // Check if refund failed
                if ($result->status === RefundRequest::STATUS_FAILED) {
                    throw new \RuntimeException('Refund processing failed. Please check the refund request notes for details.');
                }
            });

            return response()->json([
                'success' => true,
                'message' => __('Refund processed successfully'),
                'refresh_tables' => ['payments-table'],
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Refund approval failed', [
                'refund_request_id' => $refundRequest->id,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('Refund processing failed: :message', ['message' => $throwable->getMessage()]),
            ], 500);
        }
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
