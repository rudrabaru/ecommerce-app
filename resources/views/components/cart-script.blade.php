<script>
$(document).ready(function() {
    // Add to cart functionality
    $('.add-to-cart-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const button = form.find('.add-to-cart-btn');
        const originalText = button.text();
        
        button.prop('disabled', true).text('Adding...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    // Update cart count in header
                    $('.header__nav__option .price').text('$' + response.cart_total);
                    $('.header__nav__option a[href*="cart"] span').text(response.cart_count);
                    
                    // Show success message
                    button.text('Added!').removeClass('btn-outline-dark').addClass('btn-success');
                    setTimeout(() => {
                        button.text(originalText).removeClass('btn-success').addClass('btn-outline-dark').prop('disabled', false);
                    }, 2000);
                }
            },
            error: function() {
                button.text('Error').addClass('btn-danger');
                setTimeout(() => {
                    button.text(originalText).removeClass('btn-danger').prop('disabled', false);
                }, 2000);
            }
        });
    });
    
    // Search functionality
    $('#search-input').on('keyup', function() {
        const query = $(this).val();
        if (query.length > 2) {
            $.ajax({
                url: '{{ route("products.search") }}',
                method: 'GET',
                data: { q: query },
                success: function(response) {
                    // Handle search results (you can implement a dropdown here)
                    console.log(response.products);
                }
            });
        }
    });
});
</script>
