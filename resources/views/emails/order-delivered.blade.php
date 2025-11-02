<x-mail::message>
# Your Order Has Been Delivered! ✅

Hi {{ $order->user->name }},

Your order **#{{ $order->order_number }}** has been successfully delivered!

## Order Details

**Order Number:** #{{ $order->order_number }}  
**Delivery Address:** {{ $order->shipping_address }}

We hope you're happy with your purchase. If you have any questions or concerns, please don't hesitate to contact us.

<x-mail::button :url="route('orders.myorder')">
View Order Details
</x-mail::button>

Thank you for your order!<br>
{{ config('app.name') }}
</x-mail::message>
