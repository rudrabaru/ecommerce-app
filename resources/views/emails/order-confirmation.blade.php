<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #e7ab3c;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .order-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #e7ab3c;
        }
        .order-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .product-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e5e5;
        }
        .total {
            font-weight: bold;
            font-size: 18px;
            color: #e7ab3c;
            border-top: 2px solid #e7ab3c;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            color: #666;
        }
        .btn {
            display: inline-block;
            background: #e7ab3c;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Confirmation</h1>
        <p>Thank you for your purchase!</p>
    </div>
    
    <div class="content">
        <p>Dear {{ $order->user->name }},</p>
        
        <p>We're excited to confirm that your order has been successfully placed! Here are the details:</p>
        
        <div class="order-info">
            <h3>Order Information</h3>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y \a\t g:i A') }}</p>
            <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
            <p><strong>Payment Method:</strong> {{ $order->paymentMethod->display_name ?? 'N/A' }}</p>
        </div>
        
        <div class="order-details">
            <h3>Order Details</h3>
            @php($items = $order->orderItems ?? [])
            @forelse($items as $item)
                <div class="product-item">
                    <div>
                        <strong>{{ optional($item->product)->title ?? 'Product' }}</strong><br>
                        <small>Quantity: {{ (int) $item->quantity }} @ ${{ number_format((float) $item->unit_price, 2) }}</small>
                    </div>
                    <div>
                        ${{ number_format((float) ($item->total ?? ($item->unit_price * $item->quantity)), 2) }}
                    </div>
                </div>
            @empty
                <div class="product-item">
                    <div>
                        <strong>Items not available</strong>
                    </div>
                </div>
            @endforelse
            <div class="total">
                Total: ${{ number_format((float) $order->total_amount, 2) }}
            </div>
        </div>
        
        <div class="order-info">
            <h3>Shipping Address</h3>
            <p>{{ $order->shipping_address }}</p>
            @if($order->notes)
                <p><strong>Order Notes:</strong> {{ $order->notes }}</p>
            @endif
        </div>
        
        <h3>What happens next?</h3>
        <ul>
            <li>We'll process your order within 1-2 business days</li>
            <li>You'll receive a shipping confirmation email with tracking information</li>
            <li>Your order will be delivered within 3-5 business days</li>
        </ul>
        
        <p>If you have any questions about your order, please don't hesitate to contact our customer service team.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('home') }}" class="btn">Visit Our Store</a>
        </div>
        
        <div class="footer">
            <p>Thank you for choosing us!</p>
            <p><small>This is an automated email. Please do not reply to this message.</small></p>
        </div>
    </div>
</body>
</html>
