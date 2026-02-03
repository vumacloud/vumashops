import './bootstrap';

// VumaShops Frontend JavaScript

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuClose = document.getElementById('mobile-menu-close');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.remove('closed');
        });
    }

    if (mobileMenuClose && mobileMenu) {
        mobileMenuClose.addEventListener('click', () => {
            mobileMenu.classList.add('closed');
        });
    }
});

// Add to cart functionality
window.addToCart = async function(productId, quantity = 1) {
    try {
        const response = await fetch('/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ product_id: productId, quantity })
        });

        const data = await response.json();

        if (data.success) {
            updateCartCount(data.cart_count);
            showNotification('Product added to cart!', 'success');
        } else {
            showNotification(data.message || 'Failed to add product', 'error');
        }
    } catch (error) {
        console.error('Add to cart error:', error);
        showNotification('An error occurred', 'error');
    }
};

// Update cart count in header
function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        el.textContent = count;
        el.classList.toggle('hidden', count === 0);
    });
}

// Show notification toast
window.showNotification = function(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-y-full opacity-0`;

    const colors = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        info: 'bg-blue-500 text-white',
        warning: 'bg-yellow-500 text-black'
    };

    notification.classList.add(...(colors[type] || colors.info).split(' '));
    notification.textContent = message;

    document.body.appendChild(notification);

    // Animate in
    requestAnimationFrame(() => {
        notification.classList.remove('translate-y-full', 'opacity-0');
    });

    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-y-full', 'opacity-0');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
};

// Quantity selector
window.updateQuantity = function(input, delta) {
    const currentValue = parseInt(input.value) || 1;
    const min = parseInt(input.min) || 1;
    const max = parseInt(input.max) || 999;
    const newValue = Math.min(Math.max(currentValue + delta, min), max);
    input.value = newValue;

    // Trigger change event
    input.dispatchEvent(new Event('change', { bubbles: true }));
};

// Image gallery
window.changeMainImage = function(src, thumbnail) {
    const mainImage = document.getElementById('main-product-image');
    if (mainImage) {
        mainImage.src = src;
    }

    // Update active thumbnail
    document.querySelectorAll('.product-thumbnail').forEach(t => {
        t.classList.remove('ring-2', 'ring-vuma-500');
    });
    if (thumbnail) {
        thumbnail.classList.add('ring-2', 'ring-vuma-500');
    }
};

// Format currency
window.formatCurrency = function(amount, currency = 'KES') {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: currency
    }).format(amount);
};
