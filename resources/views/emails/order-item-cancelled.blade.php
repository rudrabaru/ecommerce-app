<x-mail::message>
# Item Cancellation Notice

Hi {{ $order->user->name }},

We're sorry to inform you that an item from your order **#{{ $order->order_number }}** has been cancelled.

## Item Details

**Product:** {{ $orderItem->product->title ?? 'Product' }}  
**Quantity:** {{ $orderItem->quantity }}  
**Order Number:** #{{ $order->order_number }}

The rest of your order will continue to be processed as normal. If you have any questions about this cancellation, please contact our customer service team.

<x-mail::button :url="route('home')">
Visit Our Store
</x-mail::button>

Thank you for your understanding.<br>
{{ config('app.name') }}
</x-mail::message>
