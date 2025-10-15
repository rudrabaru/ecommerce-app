<x-header />

<section class="checkout spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="summary-card text-center" style="padding:40px;">
                    <h3 class="text-danger mb-3"><i class="fa fa-times-circle"></i> Payment Failed</h3>
                    <p>Your payment could not be completed. No amount has been charged in demo mode.</p>
                    <a href="{{ route('checkout') }}" class="btn btn-primary mt-3">Try Again</a>
                </div>
            </div>
        </div>
    </div>
</section>

<x-footer />


