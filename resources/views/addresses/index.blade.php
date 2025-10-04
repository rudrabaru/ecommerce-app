<x-header />

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>My Addresses</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home') }}">Home</a>
                        <span>My Addresses</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Addresses Section Begin -->
<section class="checkout spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="checkout__form">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Shipping Addresses</h4>
                        <a href="{{ route('addresses.create') }}" class="site-btn">
                            <i class="fa fa-plus"></i> Add New Address
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($addresses->count() > 0)
                        <div class="row">
                            @foreach($addresses as $address)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="address-card {{ $address->is_default ? 'default-address' : '' }}">
                                        <div class="address-header">
                                            <h5>{{ $address->full_name }}</h5>
                                            @if($address->is_default)
                                                <span class="default-badge">Default</span>
                                            @endif
                                        </div>
                                        <div class="address-details">
                                            @if($address->company)
                                                <p><strong>{{ $address->company }}</strong></p>
                                            @endif
                                            <p>{{ $address->address_line_1 }}</p>
                                            @if($address->address_line_2)
                                                <p>{{ $address->address_line_2 }}</p>
                                            @endif
                                            <p>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</p>
                                            <p>{{ $address->country }}</p>
                                            <p><strong>Phone:</strong> {{ $address->phone }}</p>
                                        </div>
                                        <div class="address-actions">
                                            <a href="{{ route('addresses.edit', $address) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                            @if(!$address->is_default)
                                                <form method="POST" action="{{ route('addresses.set-default', $address) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        <i class="fa fa-star"></i> Set Default
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('addresses.destroy', $address) }}" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this address?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fa fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <h5>No addresses found</h5>
                                <p class="text-muted">You haven't added any shipping addresses yet.</p>
                                <a href="{{ route('addresses.create') }}" class="site-btn">
                                    <i class="fa fa-plus"></i> Add Your First Address
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Addresses Section End -->

<x-footer />

<style>
.address-card {
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 20px;
    height: 100%;
    transition: all 0.3s ease;
}

.address-card:hover {
    border-color: #e7ab3c;
    box-shadow: 0 4px 12px rgba(231, 171, 60, 0.1);
}

.default-address {
    border-color: #e7ab3c;
    background: linear-gradient(135deg, #fff9f0 0%, #ffffff 100%);
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e5e5;
}

.address-header h5 {
    margin: 0;
    color: #333;
}

.address-details p {
    margin-bottom: 5px;
    color: #666;
}

.address-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e5e5e5;
}

.address-actions .btn {
    margin-right: 5px;
    margin-bottom: 5px;
}

.empty-state {
    padding: 40px 20px;
}

.default-badge {
    background: #e7ab3c;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
</style>
