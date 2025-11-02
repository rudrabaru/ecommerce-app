<x-mail::message>
# Item Delivered! ✅

Hi {{ $order->user->name }},

Your item from order **#{{ $order->order_number }}** has been successfully delivered!

## Item Details

**Product:** {{ $orderItem->product->title ?? 'Product' }}  
**Quantity:** {{ $orderItem->quantity }}  
**Order Number:** #{{ $order->order_number }}

We hope you're happy with your purchase. If you have any questions or concerns, please don't hesitate to contact us.

<x-mail::button :url="route('orders.myorder')">
View Order Details
</x-mail::button>

Thank you for your order!<br>
{{ config('app.name') }}
</x-mail::message>
