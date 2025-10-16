<x-header />

<section class="checkout spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h3 class="mb-4">My Orders</h3>
                @forelse($orders as $order)
                    <div class="summary-card mb-3" style="padding:20px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>#{{ $order->order_number }}</strong>
                                <span class="ml-2">Status: {{ ucfirst($order->status) }}</span>
                                <span class="ml-2">Payment: {{ optional($order->paymentMethod)->display_name ?? 'N/A' }}</span>
                            </div>
                            <div><strong>Total: ${{ number_format((float)$order->total_amount, 2) }}</strong></div>
                        </div>
                        <div>
                            @foreach($order->orderItems as $item)
                                <div class="d-flex align-items-center py-2" style="border-top:1px solid #f0f0f0;">
                                    <div style="width:48px;height:48px;background:#f8f8f8;border-radius:6px;" class="mr-3"></div>
                                    <div class="flex-1">
                                        <div class="font-weight-600">{{ optional($item->product)->title ?? 'Product' }}</div>
                                        <small>Qty: {{ $item->quantity }} • ${{ number_format((float)$item->unit_price, 2) }} each</small>
                                    </div>
                                    <div class="ml-auto">${{ number_format((float)$item->total, 2) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">No orders yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</section>

<x-footer />


