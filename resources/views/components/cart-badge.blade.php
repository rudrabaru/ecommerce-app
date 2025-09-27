{{-- Cart Badge Component --}}
<a href="{{ route('cart.index') }}" class="cart-icon" style="position: relative; text-decoration: none; color: #333;">
    <i class="fa fa-shopping-cart" style="font-size: 20px;"></i>
    <span class="cart-count" id="cart-count" data-cart-count style="position: absolute; top: -8px; right: -8px; background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">0</span>
</a>
