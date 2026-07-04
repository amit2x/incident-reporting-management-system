    <script>
    // ==========================================
    // FONT AWESOME LOADING DETECTION
    // ==========================================
    (function() {
        'use strict';
        
        var isLoaded = false;
        var attempts = 0;
        var maxAttempts = 20; // 20 attempts * 200ms = 4 seconds max
        
        function checkFontAwesome() {
            attempts++;
            
            // Create test icon
            var testIcon = document.createElement('i');
            testIcon.className = 'fas fa-check';
            testIcon.style.position = 'absolute';
            testIcon.style.visibility = 'hidden';
            testIcon.style.pointerEvents = 'none';
            testIcon.style.width = '1em';
            testIcon.style.height = '1em';
            document.body.appendChild(testIcon);
            
            // Check using multiple methods
            var isFontAwesomeLoaded = false;
            
            // Method 1: Check computed font family
            var computedStyle = window.getComputedStyle(testIcon);
            var fontFamily = computedStyle.fontFamily || '';
            if (fontFamily && fontFamily.toLowerCase().includes('font awesome')) {
                isFontAwesomeLoaded = true;
            }
            
            // Method 2: Check if icon has content (pseudo-element)
            if (!isFontAwesomeLoaded) {
                try {
                    var beforeContent = window.getComputedStyle(testIcon, '::before').content || '';
                    if (beforeContent && beforeContent !== 'none' && beforeContent !== '') {
                        isFontAwesomeLoaded = true;
                    }
                } catch (e) {
                    // Ignore
                }
            }
            
            document.body.removeChild(testIcon);
            
            if (isFontAwesomeLoaded) {
                onFontAwesomeLoaded();
            } else if (attempts < maxAttempts) {
                // Try again
                setTimeout(checkFontAwesome, 200);
            } else {
                // Max attempts reached - still mark as loaded to show icons
                console.warn('Font Awesome not detected after ' + maxAttempts + ' attempts. Showing icons anyway.');
                onFontAwesomeLoaded();
            }
        }
        
        function onFontAwesomeLoaded() {
            if (isLoaded) return;
            isLoaded = true;
            
            // Add class to body
            document.body.classList.add('font-awesome-loaded');
            
            // Also add to all icon elements directly
            document.querySelectorAll('.fa-solid, .fas, .fa-regular, .far, .fab').forEach(function(el) {
                el.classList.add('font-awesome-loaded');
            });
            
            console.log('✅ Font Awesome loaded and ready');
            
            // Dispatch event for other scripts
            document.dispatchEvent(new CustomEvent('fontawesome-ready'));
        }
        
        // Start checking when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(checkFontAwesome, 50);
            });
        } else {
            setTimeout(checkFontAwesome, 50);
        }
        
        // Also check when fonts are ready
        if (document.fonts) {
            document.fonts.ready.then(function() {
                setTimeout(checkFontAwesome, 100);
            });
        }
        
        // Final fallback: always show after 3 seconds
        setTimeout(function() {
            if (!isLoaded) {
                document.body.classList.add('font-awesome-loaded');
                document.querySelectorAll('.fa-solid, .fas, .fa-regular, .far, .fab').forEach(function(el) {
                    el.classList.add('font-awesome-loaded');
                });
                console.log('ℹ️ Font Awesome shown after fallback timeout');
            }
        }, 3000);
        
        // Expose helper globally
        window.waitForFontAwesome = function(callback) {
            if (isLoaded) {
                callback();
            } else {
                document.addEventListener('fontawesome-ready', callback);
            }
        };
        
    })();
</script>
