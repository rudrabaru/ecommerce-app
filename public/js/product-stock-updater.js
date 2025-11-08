/**
 * Dynamic Product Stock Updater
 * Polls for stock updates and updates UI accordingly
 */
(function() {
    'use strict';
    
    // Get product ID from page
    const productIdElement = document.getElementById('product-id');
    if (!productIdElement) return;
    
    const productId = parseInt(productIdElement.value);
    if (!productId) return;
    
    let currentStock = parseInt(document.getElementById('product-quantity')?.getAttribute('max') || '0');
    let stockCheckInterval = null;
    
    // Get DOM elements
    const qtyInput = document.getElementById('product-quantity');
    const decBtn = document.querySelector('.qtybtn.dec');
    const incBtn = document.querySelector('.qtybtn.inc');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const stockBadge = document.getElementById('stock-badge');
    const cartForm = document.getElementById('product-cart-form');
    
    // Update quantity buttons state
    function updateQuantityButtons() {
        if (!qtyInput || !decBtn || !incBtn) return;
        
        const qty = parseInt(qtyInput.value) || 1;
        decBtn.disabled = qty <= 1;
        incBtn.disabled = qty >= currentStock;
        if (qtyInput) {
            qtyInput.setAttribute('max', currentStock);
        }
    }
    
    // Quantity selector handlers specific to product details page
    function adjustQuantity(delta) {
        if (!qtyInput) return;
        const minValue = parseInt(qtyInput.getAttribute('min')) || 1;
        const maxValue = parseInt(qtyInput.getAttribute('max')) || currentStock || 999;
        const currentValue = parseInt(qtyInput.value) || minValue;
        let nextValue = currentValue + delta;
        nextValue = Math.max(minValue, Math.min(maxValue, nextValue));
        if (nextValue === currentValue) {
            return;
        }
        qtyInput.value = nextValue;
        updateQuantityButtons();
        qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (decBtn) {
        decBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            adjustQuantity(-1);
        }, true); // capture phase to run before delegated handlers in main.js
    }
    
    if (incBtn) {
        incBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            adjustQuantity(1);
        }, true);
    }

    if (qtyInput) {
        qtyInput.addEventListener('change', function() {
            const minValue = parseInt(qtyInput.getAttribute('min')) || 1;
            const maxValue = parseInt(qtyInput.getAttribute('max')) || currentStock || 999;
            let qty = parseInt(qtyInput.value) || minValue;
            if (qty < minValue) qty = minValue;
            if (qty > maxValue) qty = maxValue;
            qtyInput.value = qty;
                updateQuantityButtons();
        });
    }
    
    // Update stock in UI
    function updateStock(data) {
        if (data.stock === currentStock) return;
        
        currentStock = data.stock;
        const isAvailable = data.is_available;
        
        // Update stock badge
        if (stockBadge) {
            stockBadge.textContent = isAvailable ? `In Stock (${currentStock})` : 'Out of Stock';
            stockBadge.className = 'badge ' + (isAvailable ? 'bg-success' : 'bg-danger');
        }
        
        // Update quantity input max
        if (qtyInput) {
            qtyInput.setAttribute('max', currentStock);
            const currentQty = parseInt(qtyInput.value) || 1;
            if (currentQty > currentStock) {
                qtyInput.value = Math.max(1, currentStock);
            }
        }
        
        // Update buttons
        updateQuantityButtons();
        
        // Update add to cart button
        if (addToCartBtn) {
            if (isAvailable) {
                addToCartBtn.disabled = false;
                addToCartBtn.innerHTML = '<i class="fa fa-shopping-cart me-2"></i>Add to Cart';
                if (cartForm) cartForm.style.display = '';
            } else {
                addToCartBtn.disabled = true;
                addToCartBtn.innerHTML = '<i class="fa fa-ban me-2"></i>Out of Stock';
            }
        }
        
        // Update stock display in product details option section
        const stockDisplay = document.querySelector('.product__details__option__size .badge');
        if (stockDisplay) {
            stockDisplay.textContent = isAvailable ? `In Stock (${currentStock})` : 'Out of Stock';
            stockDisplay.className = 'badge ' + (isAvailable ? 'bg-success' : 'bg-danger');
        }
    }
    
    // Check stock from API
    function checkStock() {
        // Use route helper if available, otherwise use direct path
        const stockUrl = window.productStockUrl || `/products/${productId}/stock`;
        fetch(stockUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateStock(data);
            }
        })
        .catch(error => {
            console.error('Error checking stock:', error);
        });
    }
    
    // Start polling when page loads
    if (productId) {
        // Check immediately
        checkStock();
        // Then poll every 5 seconds
        stockCheckInterval = setInterval(checkStock, 5000);
    }
    
    // Clean up interval when page unloads
    window.addEventListener('beforeunload', function() {
        if (stockCheckInterval) {
            clearInterval(stockCheckInterval);
        }
    });
    
    // Initialize quantity buttons on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateQuantityButtons);
    } else {
        updateQuantityButtons();
    }
})();

