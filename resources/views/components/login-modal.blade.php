<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">Login to Continue</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="loginForm" method="POST" action="{{ route('login.ajax') }}">
                    @csrf
                    <input type="hidden" name="redirect" id="loginRedirect" value="">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary btn-block">Login</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <p class="mb-0">Don't have an account? <a href="{{ route('register') }}" id="registerLinkFromLogin" class="register-link">Sign up here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    function init() {
        // Populate redirect param with current URL for both login and register flows
        try {
            var currentUrl = window.location.href;
            $('#loginRedirect').val(currentUrl);
            var $reg = $('#registerLinkFromLogin');
            if ($reg.length) {
                var base = $reg.attr('href').split('?')[0];
                $reg.attr('href', base + '?redirect=' + encodeURIComponent(currentUrl));
            }
            // Also append redirect to form action as a fallback
            var $form = $('#loginForm');
            var act = $form.attr('action').split('?')[0];
            $form.attr('action', act + '?redirect=' + encodeURIComponent(currentUrl));
        } catch (e) {}
    // Set a post-login toast if needed, then reload to show auth-only UI (e.g., cart icon)
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response && response.success) {
                    $('#loginModal').modal('hide');
                    // Reveal cart icon and update count dynamically without full reload
                    $('#cartIconVisibilityWrapper').show();
                    if (typeof updateCartCount === 'function') {
                        if (typeof response.cart_count !== 'undefined' && response.cart_count !== null) {
                            updateCartCount(response.cart_count);
                        } else {
                            $.getJSON('/cart/dropdown', function(data){
                                if (typeof data.itemCount !== 'undefined') updateCartCount(data.itemCount);
                            });
                        }
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Login successful',
                            text: 'Item added to your cart.',
                            icon: 'success',
                            timer: 1800,
                            showConfirmButton: false
                        });
                    }
                } else if (response && response.errors) {
                    Object.keys(response.errors).forEach(function(key) {
                        const input = $('input[name="' + key + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(response.errors[key][0]);
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (response && response.errors) {
                    Object.keys(response.errors).forEach(function(key) {
                        const input = $('input[name="' + key + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(response.errors[key][0]);
                    });
                } else {
                    alert('Login failed. Please try again.');
                }
            }
        });
    });
    // Clear validation errors when modal is closed
    $('#loginModal').on('hidden.bs.modal', function() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    });
    }
    function waitForJQ(){
        if (window.jQuery) { $(init); return; }
        setTimeout(waitForJQ, 50);
    }
    waitForJQ();
})();
</script>

<style>
    /* Ensure visibility and consistent styling of the register link */
    .register-link {
        color: #0d6efd !important;
        text-decoration: underline;
        display: inline;
    }
    .register-link:hover, .register-link:focus {
        color: #0a58ca !important;
        text-decoration: underline;
    }
</style>

