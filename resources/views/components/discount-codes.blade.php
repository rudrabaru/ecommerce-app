@if($discountCodes && $discountCodes->count() > 0)
<div class="discount-codes-section">
    <div class="discount-codes-header">
        <h5><i class="fa fa-tag"></i> Available Discount Codes</h5>
        <p class="text-muted">Save money with these exclusive offers for this category!</p>
    </div>
    
    <div class="discount-codes-list">
        @foreach($discountCodes as $discount)
        <div class="discount-code-item" data-code="{{ $discount->code }}">
            <div class="discount-code-info">
                <div class="discount-code-main">
                    <span class="discount-code">{{ $discount->code }}</span>
                    <span class="discount-value">
                        @if($discount->discount_type === 'percentage')
                            {{ $discount->discount_value }}% OFF
                        @else
                            ${{ number_format($discount->discount_value, 2) }} OFF
                        @endif
                    </span>
                </div>
                <div class="discount-code-details">
                    @if($discount->minimum_order_amount)
                        <small class="text-muted">Min. order: ${{ number_format($discount->minimum_order_amount, 2) }}</small>
                    @endif
                    @if($discount->valid_until)
                        <small class="text-muted">Expires: {{ $discount->valid_until->format('M d, Y') }}</small>
                    @endif
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary copy-discount-code" 
                    data-code="{{ $discount->code }}"
                    title="Click to copy code">
                <i class="fa fa-copy"></i> Copy
            </button>
        </div>
        @endforeach
    </div>
    
    <div class="discount-codes-note">
        <small class="text-info">
            <i class="fa fa-info-circle"></i> 
            Apply these codes at checkout to get your discount!
        </small>
    </div>
</div>

<style>
.discount-codes-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.discount-codes-header h5 {
    color: #e7ab3c;
    margin-bottom: 5px;
    font-weight: 600;
}

.discount-codes-header p {
    margin-bottom: 15px;
    font-size: 14px;
}

.discount-codes-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.discount-code-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 12px 15px;
    transition: all 0.2s ease;
}

.discount-code-item:hover {
    border-color: #e7ab3c;
    box-shadow: 0 2px 4px rgba(231, 171, 60, 0.1);
}

.discount-code-info {
    flex: 1;
}

.discount-code-main {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
}

.discount-code {
    background: #e7ab3c;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 13px;
    letter-spacing: 0.5px;
}

.discount-value {
    color: #28a745;
    font-weight: 600;
    font-size: 14px;
}

.discount-code-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.discount-code-details small {
    font-size: 11px;
}

.copy-discount-code {
    border-color: #e7ab3c;
    color: #e7ab3c;
    font-size: 12px;
    padding: 6px 12px;
    transition: all 0.2s ease;
}

.copy-discount-code:hover {
    background: #e7ab3c;
    color: white;
    border-color: #e7ab3c;
}

.discount-codes-note {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
}

.discount-codes-note small {
    font-size: 12px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .discount-code-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .discount-code-main {
        flex-wrap: wrap;
    }
    
    .copy-discount-code {
        align-self: flex-end;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy discount code functionality
    document.querySelectorAll('.copy-discount-code').forEach(button => {
        button.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            
            // Create temporary input element
            const tempInput = document.createElement('input');
            tempInput.value = code;
            document.body.appendChild(tempInput);
            tempInput.select();
            tempInput.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                // Copy the text
                document.execCommand('copy');
                
                // Show success feedback
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fa fa-check"></i> Copied!';
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-success');
                
                // Reset after 2 seconds
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-primary');
                }, 2000);
                
            } catch (err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy code. Please copy manually: ' + code);
            }
            
            // Remove temporary input
            document.body.removeChild(tempInput);
        });
    });
});
</script>
@endif
