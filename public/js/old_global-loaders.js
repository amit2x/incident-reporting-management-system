// Global Loader System
class GlobalLoader {
    constructor() {
        this.loaderElement = document.getElementById('globalLoader');
        this.messageElement = document.getElementById('loaderMessage');
        this.dotsElement = document.getElementById('loaderDots');
        this.timeout = null;
        this.currentMessage = '';
        this.messageInterval = null;
        this.activeRequests = 0;
        this.isVisible = false;
        
        this.init();
    }
    
    init() {
        // Show loader on all internal link clicks
        this.bindLinkClicks();
        
        // Show loader on form submits
        this.bindFormSubmits();
        
        // Handle AJAX requests globally
        this.bindAjaxRequests();
        
        // Handle browser navigation (popstate)
        this.bindPopState();
        
        // Show loader on page load if it takes time
        this.checkPageLoad();
    }
    
    bindLinkClicks() {
        document.addEventListener('click', (e) => {
            // Find the closest anchor tag
            const link = e.target.closest('a');
            
            if (!link) return;
            
            // Get the href
            const href = link.getAttribute('href');
            
            // Skip if no href or it's a javascript: link
            if (!href || href === '#' || href.startsWith('javascript:')) return;
            
            // Skip if it has download attribute
            if (link.hasAttribute('download')) return;
            
            // Skip if it's an external link
            if (this.isExternalLink(href)) return;
            
            // Skip if modifier keys are pressed (Ctrl/Cmd + click)
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;
            
            // Show loader with custom message based on link context
            const message = this.getLinkMessage(link);
            this.show(message);
        });
    }
    
    bindFormSubmits() {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            
            // Skip if it's a GET form (already handled by link clicks)
            if (form.method && form.method.toUpperCase() === 'GET') return;
            
            // Show loader on form submission
            this.show('Submitting form');
        });
    }
    
    bindAjaxRequests() {
        // Intercept fetch requests
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            // Check if this is a silent/background request
            const options = args[1] || {};
            const headers = options.headers || {};
            
            // Skip loader for silent requests
            if (headers['X-Silent-Request'] === 'true' || 
                headers['X-Background-Request'] === 'true') {
                return originalFetch(...args);
            }
            
            // Also skip for specific URLs
            const url = args[0];
            const silentUrls = [
                '/notifications/unread-count',
                '/notifications/poll',
                '/heartbeat',
                '/ping'
            ];
            
            if (silentUrls.some(silentUrl => url.includes(silentUrl))) {
                return originalFetch(...args);
            }
            
            this.incrementRequests();
            try {
                const response = await originalFetch(...args);
                return response;
            } finally {
                this.decrementRequests();
            }
        };
        
        // Intercept XMLHttpRequest
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function() {
            this._url = arguments[1];
            return originalXHROpen.apply(this, arguments);
        };
        
        XMLHttpRequest.prototype.send = function() {
            this.addEventListener('loadstart', () => {
                if (window._globalLoader) window._globalLoader.incrementRequests();
            });
            this.addEventListener('loadend', () => {
                if (window._globalLoader) window._globalLoader.decrementRequests();
            });
            return originalXHRSend.apply(this, arguments);
        };
        
        // Handle jQuery AJAX if present
        if (window.jQuery) {
            $(document).ajaxStart(() => this.incrementRequests());
            $(document).ajaxStop(() => this.decrementRequests());
        }
    }
    
    bindPopState() {
        window.addEventListener('popstate', () => {
            this.show('Loading page');
        });
    }
    
    checkPageLoad() {
        // If page takes more than 100ms to load, show loader
        const startTime = Date.now();
        window.addEventListener('load', () => {
            const loadTime = Date.now() - startTime;
            if (loadTime > 100) {
                this.hide();
            }
        });
    }
    
    incrementRequests() {
        this.activeRequests++;
        if (this.activeRequests > 0 && !this.isVisible) {
            this.show('Loading data');
        }
    }
    
    decrementRequests() {
        this.activeRequests--;
        if (this.activeRequests <= 0 && this.isVisible) {
            setTimeout(() => this.hide(), 100);
        }
    }
    
    show(message = 'Loading') {
        // Clear any pending hide timeout
        if (this.timeout) {
            clearTimeout(this.timeout);
            this.timeout = null;
        }
        
        // Remove fade-out class if exists
        if (this.loaderElement.classList.contains('loader-fade-out')) {
            this.loaderElement.classList.remove('loader-fade-out');
        }
        
        // Set message
        this.setMessage(message);
        
        // Start animated dots
        this.startDotsAnimation();
        
        // Show loader
        if (!this.isVisible) {
            this.loaderElement.style.display = 'block';
            this.loaderElement.classList.add('loader-fade-in');
            this.isVisible = true;
        }
    }
    
    hide() {
        if (!this.isVisible) return;
        
        this.loaderElement.classList.remove('loader-fade-in');
        this.loaderElement.classList.add('loader-fade-out');
        
        // Clear message interval
        if (this.messageInterval) {
            clearInterval(this.messageInterval);
            this.messageInterval = null;
        }
        
        // Hide after animation
        this.timeout = setTimeout(() => {
            this.loaderElement.style.display = 'none';
            this.isVisible = false;
            this.timeout = null;
        }, 200);
    }
    
    setMessage(message) {
        this.currentMessage = message;
        if (this.messageElement) {
            this.messageElement.textContent = message;
        }
    }
    
    startDotsAnimation() {
        if (this.messageInterval) {
            clearInterval(this.messageInterval);
        }
        
        let dotCount = 3;
        this.messageInterval = setInterval(() => {
            if (this.dotsElement) {
                const dots = '.'.repeat(dotCount);
                this.dotsElement.textContent = dots;
                dotCount = dotCount === 3 ? 1 : dotCount + 1;
            }
        }, 500);
    }
    
    getLinkMessage(link) {
        // Get message based on link context
        const text = link.textContent?.toLowerCase() || '';
        const href = link.getAttribute('href') || '';
        
        if (href.includes('create') || text.includes('create') || text.includes('new') || text.includes('add')) {
            return 'Creating new';
        } else if (href.includes('edit') || text.includes('edit')) {
            return 'Loading edit form';
        } else if (href.includes('delete') || text.includes('delete')) {
            return 'Deleting';
        } else if (href.includes('show') || href.includes('view')) {
            return 'Loading details';
        } else if (href.includes('login')) {
            return 'Signing in';
        } else if (href.includes('register')) {
            return 'Creating account';
        } else if (href.includes('profile')) {
            return 'Loading profile';
        } else if (href.includes('settings')) {
            return 'Loading settings';
        } else if (href.includes('report') || href.includes('incident')) {
            return 'Loading incidents';
        } else if (text.includes('save') || text.includes('update')) {
            return 'Saving changes';
        } else if (text.includes('submit')) {
            return 'Submitting';
        } else if (text.includes('search')) {
            return 'Searching';
        } else if (text.includes('filter')) {
            return 'Applying filters';
        } else if (text.includes('export')) {
            return 'Preparing export';
        } else if (text.includes('import')) {
            return 'Importing data';
        } else if (text.includes('upload')) {
            return 'Uploading file';
        } else if (text.includes('download')) {
            return 'Preparing download';
        } else if (href.includes('logout')) {
            return 'Logging out';
        }
        
        return 'Loading';
    }
    
    isExternalLink(href) {
        if (href.startsWith('http') && !href.includes(window.location.hostname)) {
            return true;
        }
        return false;
    }
    
    // Manual show/hide methods for AJAX calls
    static show(message) {
        if (window._globalLoader) {
            window._globalLoader.show(message);
        }
    }
    
    static hide() {
        if (window._globalLoader) {
            window._globalLoader.hide();
        }
    }
}

// Initialize global loader
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window._globalLoader = new GlobalLoader();
    });
} else {
    window._globalLoader = new GlobalLoader();
}

// Export for use in other scripts
window.showLoader = (message) => GlobalLoader.show(message);
window.hideLoader = () => GlobalLoader.hide();