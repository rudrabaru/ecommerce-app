<x-mail::message>
# Order Cancellation Notice

Hi {{ $order->user->name }},

We're sorry to inform you that your order **#{{ $order->order_number }}** has been cancelled.

## Order Details

**Order Number:** #{{ $order->order_number }}  
**Cancellation Date:** {{ now()->format('F j, Y \a\t g:i A') }}

@if($order->notes)
**Reason:** {{ $order->notes }}
@endif

If you have any questions about this cancellation or would like to place a new order, please contact our customer service team.

<x-mail::button :url="route('home')">
Visit Our Store
</x-mail::button>

Thank you for your understanding.<br>
{{ config('app.name') }}
</x-mail::message>
