<script>
$(document).ready(function() {
    // Add to cart functionality
    $('.add-to-cart').on('click', function(e) {
        e.preventDefault();
        
        const productId = $(this).data('product-id');
        const quantity = $(this).closest('form').find('input[name="quantity"]').val() || 1;
        
        $.ajax({
            url: '/cart/add',
            method: 'POST',
            data: {
                product_id: productId,
                quantity: quantity,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                updateCartCount(response.cart_count);
                Swal.fire('Success', response.message, 'success');
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire('Error', response.message || 'Error adding to cart', 'error');
            }
        });
    });
});
</script>