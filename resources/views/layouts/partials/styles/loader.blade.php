<style>
    /* Enhanced Loader Animations */
@keyframes loaderSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes loaderPulse {
    0%, 100% { 
        opacity: 0.6;
        transform: scale(0.8);
    }
    50% { 
        opacity: 1;
        transform: scale(1.2);
    }
}

/* Optional: Add progress bar at top of page */
.global-progress-bar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #4361ee, #06ffa5, #ff9f1c, #ef233c);
    z-index: 100000;
    transform-origin: 0%;
    animation: progressBar 0.3s ease-out;
}

@keyframes progressBar {
    from {
        transform: scaleX(0);
    }
    to {
        transform: scaleX(1);
    }
}
</style>