<x-mail::message>
# Item Shipped! 🚚

Hi {{ $order->user->name }},

Great news! An item from your order **#{{ $order->order_number }}** has been shipped.

## Item Details

**Product:** {{ $orderItem->product->title ?? 'Product' }}  
**Quantity:** {{ $orderItem->quantity }}  
**Order Number:** #{{ $order->order_number }}

The item is on its way to you. You can track your order progress through your account dashboard.

<x-mail::button :url="route('orders.myorder')">
View Order Status
</x-mail::button>

Thanks for shopping with us!<br>
{{ config('app.name') }}
</x-mail::message>
