<script>
$(document).ready(function() {
    // Add to cart functionality for both .add-to-cart and .add-to-cart-btn classes
    $(document).on('click', '.add-to-cart, .add-to-cart-btn', function(e) {
        e.preventDefault();
        
        const form = $(this).closest('form');
        const productId = form.find('input[name="product_id"]').val();
        const quantity = form.find('input[name="quantity"]').val() || 1;
        
        // Show loading state
        const button = $(this);
        const originalText = button.text();
        button.prop('disabled', true).text('Adding...');
        
        $.ajax({
            url: '/cart/add',
            method: 'POST',
            data: {
                product_id: productId,
                quantity: quantity,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateCartCount(response.cart_count);
                    // If user is authenticated, show success toast immediately
                    // If guest, prompt login/register modal immediately after adding to session cart
                    var isAuth = $('body').attr('data-is-auth') === '1';
                    if (isAuth) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message || 'Added to cart successfully',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        // Open global login modal
                        if (typeof $ !== 'undefined' && $('#loginModal').length) {
                            $('#loginModal').modal('show');
                        } else {
                            // Fallback: redirect to login
                            window.location.href = "{{ route('login') }}";
                        }
                    }
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'Error adding to cart',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error adding to cart';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 422) {
                    errorMessage = 'Validation error. Please check your input.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                }
                
                Swal.fire({
                    title: 'Error!',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Reset button state
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Post-login toast: show once after page reload
    try {
        if (sessionStorage.getItem('postLoginCartToast') === '1') {
            sessionStorage.removeItem('postLoginCartToast');
            Swal.fire({
                title: 'Success!',
                text: 'Item added to your cart',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }
    } catch (e) {}
});
</script>