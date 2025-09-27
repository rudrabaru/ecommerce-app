{{-- Cart Icon Component - Always positioned at extreme right --}}
<div class="cart-icon-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
    <a href="{{ route('cart.index') }}" class="cart-icon" style="position: relative; text-decoration: none; color: #111111; font-size: 24px; background: white; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.3s ease;">
        <i class="fa fa-shopping-cart"></i>
        <span class="cart-count" id="cart-count" style="position: absolute; top: -5px; right: -5px; background: #e7ab3c; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; min-width: 22px;">{{ \App\Http\Controllers\CartController::getCartCount() }}</span>
    </a>
    
    {{-- Cart Dropdown --}}
    @include('components.cart-dropdown')
</div>

<style>
.cart-icon:hover {
    color: #e7ab3c !important;
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.cart-icon-wrapper {
    animation: cartPulse 2s infinite;
}

@keyframes cartPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .cart-icon-wrapper {
        top: 15px;
        right: 15px;
    }
    
    .cart-icon {
        width: 45px !important;
        height: 45px !important;
        font-size: 20px !important;
    }
    
    .cart-count {
        width: 20px !important;
        height: 20px !important;
        font-size: 11px !important;
    }
}
</style>
