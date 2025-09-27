{{-- Cart Dropdown Component --}}
<div class="cart-dropdown" id="cart-dropdown" style="position: absolute; top: 100%; right: 0; width: 350px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); z-index: 1000; display: none; max-height: 400px; overflow-y: auto;">
    <div class="cart-dropdown-header" style="padding: 15px; border-bottom: 1px solid #eee; background: #f8f9fa;">
        <h6 style="margin: 0; font-weight: 600;">Shopping Cart</h6>
        <small class="text-muted" id="cart-item-count">0 items</small>
    </div>
    
    <div class="cart-dropdown-items" id="cart-dropdown-items" style="max-height: 250px; overflow-y: auto;">
        {{-- Cart items will be loaded here via AJAX --}}
    </div>
    
    <div class="cart-dropdown-footer" style="padding: 15px; border-top: 1px solid #eee; background: #f8f9fa;">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Total:</strong>
            <strong id="cart-dropdown-total">$0.00</strong>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('cart.index') }}" class="btn btn-outline-primary btn-sm flex-fill">View Cart</a>
            <a href="{{ auth()->check() ? route('checkout') : route('login') }}" class="btn btn-primary btn-sm flex-fill">Checkout</a>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle cart dropdown
    $('.cart-icon').on('click', function(e) {
        e.preventDefault();
        loadCartDropdown();
        $('#cart-dropdown').toggle();
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.cart-icon-wrapper').length) {
            $('#cart-dropdown').hide();
        }
    });
    
    // Load cart dropdown content
    function loadCartDropdown() {
        $.ajax({
            url: '/cart/dropdown',
            method: 'GET',
            success: function(response) {
                $('#cart-dropdown-items').html(response.items);
                $('#cart-dropdown-total').text('$' + response.total.toFixed(2));
                $('#cart-item-count').text(response.itemCount + ' items');
            }
        });
    }
});
</script>
