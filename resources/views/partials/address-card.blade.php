<!-- Single Address Card -->
<div class="address-card mb-3" id="address-card-{{ $address->id }}">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="card-title">{{ $address->first_name }} {{ $address->last_name }}</h5>
                    @if($address->is_default)
                        <span class="badge bg-primary">Primary</span>
                    @endif
                </div>
                <div class="address-actions">
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" 
                            onclick="openEditAddressModal({{ $address->id }})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    @unless($address->is_default)
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deleteAddress({{ $address->id }})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    @endunless
                </div>
            </div>
            
            <p class="card-text mt-2 mb-1">
                {{ $address->address_line_1 }}
                @if($address->address_line_2)
                    <br>{{ $address->address_line_2 }}
                @endif
            </p>
            
            <p class="card-text mb-1">
                {{ $address->city->name }}, {{ $address->state->name }} {{ $address->postal_code }}
            </p>
            
            <p class="card-text mb-1">
                {{ $address->country->name }}
            </p>
            
            <p class="card-text mb-0">
                <i class="fas fa-phone me-2"></i> {{ $address->country_code }} {{ $address->phone }}
                @if($address->email)
                    <br>
                    <i class="fas fa-envelope me-2"></i> {{ $address->email }}
                @endif
            </p>
            
            @if($address->company)
                <p class="card-text mt-2 mb-0">
                    <i class="fas fa-building me-2"></i> {{ $address->company }}
                </p>
            @endif
        </div>
    </div>
</div>