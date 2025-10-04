<x-header />

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Order Confirmation</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home') }}">Home</a>
                        <span>Order Confirmation</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Order Success Section Begin -->
<section class="checkout spad">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="order-success">
                    <div class="success-icon">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    
                    <h2>Order Placed Successfully!</h2>
                    
                    @if(session('success'))
                        <div class="success-message">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    <div class="order-details">
                        <h4>What happens next?</h4>
                        <div class="steps">
                            <div class="step">
                                <div class="step-icon">
                                    <i class="fa fa-envelope"></i>
                                </div>
                                <div class="step-content">
                                    <h5>Confirmation Email</h5>
                                    <p>You'll receive an order confirmation email shortly with all the details.</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-icon">
                                    <i class="fa fa-truck"></i>
                                </div>
                                <div class="step-content">
                                    <h5>Processing & Shipping</h5>
                                    <p>We'll process your order and prepare it for shipment within 1-2 business days.</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-icon">
                                    <i class="fa fa-home"></i>
                                </div>
                                <div class="step-content">
                                    <h5>Delivery</h5>
                                    <p>Your order will be delivered to your specified address within 3-5 business days.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="{{ route('shop') }}" class="site-btn">
                            <i class="fa fa-shopping-bag"></i> Continue Shopping
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline-primary">
                            <i class="fa fa-home"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Order Success Section End -->

<x-footer />

<style>
.order-success {
    text-align: center;
    padding: 60px 40px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    margin: 40px 0;
}

.success-icon {
    margin-bottom: 30px;
}

.success-icon i {
    font-size: 80px;
    color: #28a745;
}

.order-success h2 {
    color: #333;
    margin-bottom: 20px;
    font-weight: 600;
}

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    border: 1px solid #c3e6cb;
}

.order-details {
    margin: 40px 0;
    text-align: left;
}

.order-details h4 {
    color: #333;
    margin-bottom: 30px;
    text-align: center;
}

.steps {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.step {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #e7ab3c;
}

.step-icon {
    flex-shrink: 0;
    width: 50px;
    height: 50px;
    background: #e7ab3c;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.step-content h5 {
    color: #333;
    margin-bottom: 8px;
    font-weight: 600;
}

.step-content p {
    color: #666;
    margin: 0;
    line-height: 1.5;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 40px;
    flex-wrap: wrap;
}

.action-buttons .btn {
    padding: 12px 30px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-outline-primary {
    background: transparent;
    color: #e7ab3c;
    border: 2px solid #e7ab3c;
}

.btn-outline-primary:hover {
    background: #e7ab3c;
    color: white;
}

@media (max-width: 768px) {
    .order-success {
        padding: 40px 20px;
    }
    
    .steps {
        gap: 20px;
    }
    
    .step {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .action-buttons .btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
}
</style>
