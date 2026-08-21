<script>
/**
 * Close celebration banner with localStorage persistence
 * @param {string} elementId - The DOM element ID
 * @param {string} bannerId - Unique identifier for the banner type (independence, republic, newyear)
 */
function closeCelebrationBanner(elementId, bannerId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    
    // Store in localStorage so it doesn't reappear
    try {
        const storageKey = 'samanvay_banner_' + bannerId;
        const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
        localStorage.setItem(storageKey, today);
    } catch (e) {
        console.warn('Could not save banner preference:', e);
    }
    
    // Animate and remove
    el.style.transition = 'opacity 0.3s ease, max-height 0.3s ease, margin 0.3s ease';
    el.style.opacity = '0';
    el.style.maxHeight = '0';
    el.style.margin = '0';
    el.style.padding = '0';
    
    setTimeout(() => {
        if (el.parentNode) {
            el.remove();
        }
    }, 350);
}

/**
 * Check if a banner should be displayed based on localStorage
 * @param {string} bannerId - Unique identifier for the banner type
 * @param {number} daysToShow - How many days to check (default 1)
 * @returns {boolean} - True if banner should be shown
 */
function shouldShowBanner(bannerId, daysToShow = 1) {
    try {
        const storageKey = 'samanvay_banner_' + bannerId;
        const storedDate = localStorage.getItem(storageKey);
        
        if (!storedDate) return true;
        
        // Check if the stored date is from today
        const today = new Date().toISOString().split('T')[0];
        const stored = new Date(storedDate);
        const now = new Date(today);
        
        // Calculate difference in days
        const diffTime = Math.abs(now - stored);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        // If it's been more than 'daysToShow' days, show again
        return diffDays > daysToShow;
    } catch (e) {
        console.warn('Could not check banner preference:', e);
        return true;
    }
}

/**
 * Initialize banners - hide closed ones
 */
document.addEventListener('DOMContentLoaded', function() {
    // Check each banner type
    const bannerTypes = ['independence', 'republic', 'newyear'];
    
    bannerTypes.forEach(type => {
        const bannerElement = document.querySelector(`[data-banner-id="${type}"]`);
        if (bannerElement) {
            if (!shouldShowBanner(type, 1)) {
                // Banner was closed today, hide it
                bannerElement.style.display = 'none';
            }
        }
    });
});

/**
 * New Year Overlay - Falling flowers and fireworks
 * Shows only once per user per year (localStorage)
 */
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('newYearOverlay');
    if (!overlay) return;

    const STORAGE_KEY = 'samanvay_newyear_overlay';
    const currentYear = new Date().getFullYear();
    const storedData = localStorage.getItem(STORAGE_KEY);
    
    // Check if overlay was shown this year
    if (storedData === String(currentYear)) {
        overlay.remove();
        return;
    }

    overlay.classList.remove('d-none');

    const colors = ['#ff4d4d', '#ffd11a', '#4da6ff', '#66ff99', '#ff99ff'];

    // Create falling flowers
    for (let i = 0; i < 80; i++) {
        const flower = document.createElement('div');
        flower.className = 'flower';
        flower.style.left = Math.random() * 100 + 'vw';
        flower.style.animationDuration = (4 + Math.random() * 4) + 's';
        flower.style.background = colors[Math.floor(Math.random() * colors.length)];
        overlay.appendChild(flower);
    }

    // Fireworks
    let fired = 0;
    const maxFireworks = 12;

    function launchFirework() {
        const rocket = document.createElement('div');
        rocket.className = 'firework';
        rocket.style.left = Math.random() * 100 + 'vw';
        overlay.appendChild(rocket);

        setTimeout(() => {
            rocket.remove();
            explode(rocket.style.left);
        }, 1500);
    }

    function explode(xPos) {
        for (let i = 0; i < 25; i++) {
            const spark = document.createElement('div');
            spark.className = 'spark';
            spark.style.left = xPos;
            spark.style.bottom = '50vh';
            spark.style.background = colors[Math.floor(Math.random() * colors.length)];
            spark.style.setProperty('--x', (Math.random() * 200 - 100) + 'px');
            spark.style.setProperty('--y', (Math.random() * 200 - 100) + 'px');
            overlay.appendChild(spark);
            setTimeout(() => spark.remove(), 1200);
        }
    }

    const fwInterval = setInterval(() => {
        launchFirework();
        fired++;
        if (fired >= maxFireworks) clearInterval(fwInterval);
    }, 350);

    // Auto remove after 7.5 seconds and save preference
    setTimeout(() => {
        overlay.remove();
        try {
            localStorage.setItem(STORAGE_KEY, String(currentYear));
        } catch (e) {
            console.warn('Could not save overlay preference:', e);
        }
    }, 7500);
});
</script>