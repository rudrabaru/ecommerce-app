<x-header />

    <!-- Breadcrumb Section Begin -->
    <section class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__text">
                        <h4>Check Out</h4>
                        <div class="breadcrumb__links">
                            <a href="{{ route('home') }}">Home</a>
                            <a href="{{ route('shop') }}">Shop</a>
                            <span>Check Out</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Breadcrumb Section End -->

    <!-- Checkout Section Begin -->
    <section class="checkout spad">
        <div class="container">
            <div class="checkout__form">
                <form method="post" action="{{ route('checkout.store') }}">
                    @csrf
                    <!-- Collect at least shipping_address and optional notes -->
                    <div class="checkout__input">
                        <p>Address<span>*</span></p>
                        <input type="text" name="shipping_address" placeholder="Street Address" class="checkout__input__add" required>
                    </div>
                    <div class="checkout__input">
                        <p>Order notes</p>
                        <input type="text" name="notes" placeholder="Notes about your order (optional)">
                    </div>
                    <!-- Keep the existing order summary; move submit to this form -->
                    <button type="submit" class="site-btn">PLACE ORDER</button>
                </form>
            </div>
        </div>
    </section>
    <!-- Checkout Section End -->

    <x-footer />

    <!-- Search Begin -->
    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch">+</div>
            <form class="search-model-form">
                <input type="text" id="search-input" placeholder="Search here.....">
            </form>
        </div>
    </div>
    <!-- Search End -->

    <!-- Js Plugins -->
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.nicescroll.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/jquery.countdown.min.js"></script>
    <script src="js/jquery.slicknav.js"></script>
    <script src="js/mixitup.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>

</html>