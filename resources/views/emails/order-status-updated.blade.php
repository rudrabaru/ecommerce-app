<p>Hi {{ $order->user->name ?? 'there' }},</p>

<p>Your order <strong>#{{ $order->order_number ?? $order->id }}</strong> status has been updated to
    <strong>{{ ucfirst($order->order_status) }}</strong>.</p>

<p>Thank you for shopping with us.</p>

<p>— {{ config('app.name') }}</p>


