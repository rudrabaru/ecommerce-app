<x-mail::message>
# Your Order Has Been Shipped! 🚚

Hi {{ $order->user->name }},

Great news! Your order **#{{ $order->order_number }}** has been shipped and is on its way to you.

## Order Details

**Order Number:** #{{ $order->order_number }}  
**Shipping Address:** {{ $order->shipping_address }}

You can track your order progress through your account dashboard. We'll notify you once it's delivered.

<x-mail::button :url="route('orders.myorder')">
View Order Status
</x-mail::button>

Thanks for shopping with us!<br>
{{ config('app.name') }}
</x-mail::message>
