/**
 * Umma Directory - Main JavaScript
 * Handles interactive features and AJAX functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    
    if (mobileMenuToggle && navbarMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navbarMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                navbarMenu.classList.remove('active');
            }
        });
    }
    
    // Star rating interaction
    const starInputs = document.querySelectorAll('.rating-input');
    starInputs.forEach(input => {
        input.addEventListener('change', function() {
            const value = this.value;
            const stars = this.parentElement.querySelectorAll('.star');
            stars.forEach((star, index) => {
                if (index < value) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        });
    });
    
    // Image upload preview
    const imageUploads = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageUploads.forEach(upload => {
        upload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = upload.parentElement.querySelector('.image-preview');
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Show error message
                    const errorMsg = field.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.style.display = 'block';
                    }
                } else {
                    field.classList.remove('error');
                    const errorMsg = field.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.style.display = 'none';
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    // Search autocomplete (placeholder for future implementation)
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(function() {
            const query = this.value.trim();
            if (query.length >= 2) {
                // Future: Fetch autocomplete suggestions
                console.log('Searching for:', query);
            }
        }, 300));
    });
    
    // Lazy loading images
    const lazyImages = document.querySelectorAll('img[data-src]');
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for browsers without IntersectionObserver
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
    
    // Review helpful button
    const helpfulButtons = document.querySelectorAll('.helpful-btn');
    helpfulButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const reviewId = this.dataset.reviewId;
            
            try {
                const response = await fetch('/api/reviews/helpful', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ review_id: reviewId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const countSpan = this.querySelector('.count');
                    countSpan.textContent = parseInt(countSpan.textContent) + 1;
                    this.classList.add('voted');
                }
            } catch (error) {
                console.error('Error marking review as helpful:', error);
            }
        });
    });
    
    // Check-in button
    const checkinButtons = document.querySelectorAll('.checkin-btn');
    checkinButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const businessId = this.dataset.businessId;
            
            try {
                const response = await fetch('/api/checkin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ business_id: businessId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✓ Checked in successfully!');
                    this.disabled = true;
                    this.textContent = 'Checked In';
                }
            } catch (error) {
                console.error('Error checking in:', error);
                alert('Failed to check in. Please try again.');
            }
        });
    });
    
    // Business claim button
    const claimButtons = document.querySelectorAll('.claim-btn');
    claimButtons.forEach(button => {
        button.addEventListener('click', function() {
            const businessId = this.dataset.businessId;
            window.location.href = `/business/${businessId}/claim`;
        });
    });
    
    // Filter toggle on mobile
    const filterToggle = document.querySelector('.filter-toggle');
    const filtersSidebar = document.querySelector('.filters-sidebar');
    
    if (filterToggle && filtersSidebar) {
        filterToggle.addEventListener('click', function() {
            filtersSidebar.classList.toggle('active');
        });
        
        // Close filters when clicking outside
        document.addEventListener('click', function(e) {
            if (!filtersSidebar.contains(e.target) && !filterToggle.contains(e.target)) {
                filtersSidebar.classList.remove('active');
            }
        });
    }
    
    // Price range slider
    const priceSlider = document.getElementById('price-range');
    const priceValue = document.getElementById('price-value');
    
    if (priceSlider && priceValue) {
        priceSlider.addEventListener('input', function() {
            priceValue.textContent = this.value;
        });
    }
    
    // Distance selector
    const distanceSelect = document.getElementById('distance');
    if (distanceSelect) {
        distanceSelect.addEventListener('change', function() {
            // Submit form or update results
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    }
});

// Utility: Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Utility: Format number with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Utility: Time ago formatter
function timeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " years ago";
    
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " months ago";
    
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " days ago";
    
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " hours ago";
    
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " minutes ago";
    
    return Math.floor(seconds) + " seconds ago";
}

// Service Worker Registration for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js').then(function(registration) {
            console.log('ServiceWorker registration successful');
        }).catch(function(err) {
            console.log('ServiceWorker registration failed: ', err);
        });
    });
}
