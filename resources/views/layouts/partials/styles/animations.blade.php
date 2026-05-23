<style>
    /* ==========================================
       KEYFRAME ANIMATIONS
       ========================================== */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(12px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-12px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-12px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(12px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInUp {
        from {
            transform: translateY(100%);
        }
        to {
            transform: translateY(0);
        }
    }

    @keyframes slideInDown {
        from {
            transform: translateY(-100%);
        }
        to {
            transform: translateY(0);
        }
    }

    @keyframes slideInLeft {
        from {
            transform: translateX(-100%);
        }
        to {
            transform: translateX(0);
        }
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
        }
        to {
            transform: translateX(0);
        }
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes scaleOut {
        from {
            opacity: 1;
            transform: scale(1);
        }
        to {
            opacity: 0;
            transform: scale(0.9);
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    @keyframes pulseRing {
        0% {
            transform: scale(0.8);
            opacity: 1;
        }
        100% {
            transform: scale(2.4);
            opacity: 0;
        }
    }

    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
        20%, 40%, 60%, 80% { transform: translateX(4px); }
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }
        100% {
            background-position: 200% 0;
        }
    }

    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-6px);
        }
    }

    @keyframes glow {
        0%, 100% {
            box-shadow: 0 0 5px rgba(26, 86, 219, 0.3);
        }
        50% {
            box-shadow: 0 0 20px rgba(26, 86, 219, 0.6);
        }
    }

    /* ==========================================
       ANIMATION UTILITY CLASSES
       ========================================== */
    .animate-fade-in {
        animation: fadeIn 0.3s ease;
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.4s ease;
    }

    .animate-fade-in-down {
        animation: fadeInDown 0.4s ease;
    }

    .animate-fade-in-left {
        animation: fadeInLeft 0.4s ease;
    }

    .animate-fade-in-right {
        animation: fadeInRight 0.4s ease;
    }

    .animate-scale-in {
        animation: scaleIn 0.3s ease;
    }

    .animate-slide-in-up {
        animation: slideInUp 0.4s ease;
    }

    .animate-pulse {
        animation: pulse 2s infinite;
    }

    .animate-bounce {
        animation: bounce 1s infinite;
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    .animate-shake {
        animation: shake 0.5s ease;
    }

    .animate-float {
        animation: float 3s ease-in-out infinite;
    }

    .animate-glow {
        animation: glow 2s ease-in-out infinite;
    }

    /* ==========================================
       ANIMATION DELAYS
       ========================================== */
    .delay-100 { animation-delay: 100ms; }
    .delay-200 { animation-delay: 200ms; }
    .delay-300 { animation-delay: 300ms; }
    .delay-400 { animation-delay: 400ms; }
    .delay-500 { animation-delay: 500ms; }
    .delay-700 { animation-delay: 700ms; }
    .delay-1000 { animation-delay: 1000ms; }

    /* ==========================================
       ANIMATION DURATIONS
       ========================================== */
    .duration-fast { animation-duration: 150ms; }
    .duration-normal { animation-duration: 300ms; }
    .duration-slow { animation-duration: 500ms; }
    .duration-slower { animation-duration: 800ms; }

    /* ==========================================
       TRANSITION UTILITIES
       ========================================== */
    .transition-fast {
        transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .transition {
        transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .transition-slow {
        transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .transition-transform {
        transition: transform 200ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .transition-opacity {
        transition: opacity 200ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ==========================================
       HOVER EFFECTS
       ========================================== */
    .hover-lift {
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
    }

    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .hover-scale {
        transition: transform var(--transition-fast);
    }

    .hover-scale:hover {
        transform: scale(1.02);
    }

    .hover-glow:hover {
        box-shadow: 0 0 20px rgba(26, 86, 219, 0.3);
    }

    /* ==========================================
       PAGE TRANSITIONS
       ========================================== */
    .page-enter {
        animation: fadeInUp 0.3s ease;
    }

    .page-enter-right {
        animation: slideInRight 0.3s ease;
    }

    .page-enter-left {
        animation: slideInLeft 0.3s ease;
    }

    /* ==========================================
       RIPPLE EFFECT
       ========================================== */
    .ripple {
        position: relative;
        overflow: hidden;
    }

    .ripple::after {
        content: '';
        display: block;
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        pointer-events: none;
        background-image: radial-gradient(circle, rgba(255,255,255,0.3) 10%, transparent 10.01%);
        background-repeat: no-repeat;
        background-position: 50%;
        transform: scale(10, 10);
        opacity: 0;
        transition: transform 0.5s, opacity 1s;
    }

    .ripple:active::after {
        transform: scale(0, 0);
        opacity: 0.3;
        transition: 0s;
    }

    /* ==========================================
       REDUCED MOTION (Accessibility)
       ========================================== */
    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>
