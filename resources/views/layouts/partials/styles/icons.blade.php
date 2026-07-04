<style>
    /* ==========================================
       RESERVE SPACE FOR ICONS - No Layout Shift
       ========================================== */
    
    /* Reserve space for all icons */
    .fa-solid, 
    .fa-regular, 
    .fas, 
    .far, 
    .fab {
        display: inline-block !important;
        width: 1.25em !important;  /* Fixed width */
        height: 1.25em !important; /* Fixed height */
        min-width: 1.25em !important;
        min-height: 1.25em !important;
        line-height: 1.25em !important;
        text-align: center !important;
        vertical-align: middle !important;
        font-size: inherit !important;
        
        /* Reserve space with background */
        background-color: #f3f4f6;
        border-radius: 4px;
        
        /* Smooth transition */
        transition: background-color 0.2s ease, opacity 0.2s ease;
        
        /* Start invisible but space is reserved */
        opacity: 0;
        color: transparent;
    }
    
    /* When Font Awesome loads, show icons */
    body.font-awesome-loaded .fa-solid,
    body.font-awesome-loaded .fa-regular,
    body.font-awesome-loaded .fas,
    body.font-awesome-loaded .far,
    body.font-awesome-loaded .fab {
        background-color: transparent !important;
        opacity: 1 !important;
        color: inherit !important;
    }
    
    /* Special handling for icons in buttons */
    .btn .fa-solid,
    .btn .fa-regular,
    .btn .fas,
    .btn .far {
        width: 1.1em !important;
        height: 1.1em !important;
        min-width: 1.1em !important;
        min-height: 1.1em !important;
        line-height: 1.1em !important;
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }
    
    .btn.font-awesome-loaded .fa-solid,
    .btn.font-awesome-loaded .fa-regular,
    .btn.font-awesome-loaded .fas,
    .btn.font-awesome-loaded .far {
        background-color: transparent !important;
        color: inherit !important;
    }
    
    /* Specific icon sizes */
    .icon-sm {
        width: 0.9em !important;
        height: 0.9em !important;
        min-width: 0.9em !important;
        min-height: 0.9em !important;
        line-height: 0.9em !important;
        font-size: 0.875rem !important;
    }
    
    .icon-lg {
        width: 1.5em !important;
        height: 1.5em !important;
        min-width: 1.5em !important;
        min-height: 1.5em !important;
        line-height: 1.5em !important;
        font-size: 1.25rem !important;
    }
    
    .icon-xl {
        width: 2em !important;
        height: 2em !important;
        min-width: 2em !important;
        min-height: 2em !important;
        line-height: 2em !important;
        font-size: 1.5rem !important;
    }
    
    /* Pulsing animation for loading state */
    @keyframes iconPulse {
        0%, 100% { 
            background-color: #f3f4f6;
            transform: scale(1);
        }
        50% { 
            background-color: #e5e7eb;
            transform: scale(0.95);
        }
    }
    
    .fa-solid:not(.font-awesome-loaded),
    .fas:not(.font-awesome-loaded),
    .fa-regular:not(.font-awesome-loaded),
    .far:not(.font-awesome-loaded) {
        animation: iconPulse 1.5s ease-in-out infinite;
    }
    
    /* When loaded, remove animation */
    body.font-awesome-loaded .fa-solid,
    body.font-awesome-loaded .fas,
    body.font-awesome-loaded .fa-regular,
    body.font-awesome-loaded .far {
        animation: none !important;
    }
    
    /* Captcha icons - always show */
    .captcha-refresh-hint i {
        background-color: transparent !important;
        opacity: 1 !important;
        color: white !important;
        animation: none !important;
        width: auto !important;
        height: auto !important;
        min-width: auto !important;
        min-height: auto !important;
    }
    
    /* Alert icons - show immediately */
    .alert i {
        background-color: transparent !important;
        opacity: 1 !important;
        color: inherit !important;
        animation: none !important;
        width: auto !important;
        height: auto !important;
        min-width: auto !important;
        min-height: auto !important;
    }
    
    /* Toastr icons */
    .toastr i {
        background-color: transparent !important;
        opacity: 1 !important;
        color: inherit !important;
        animation: none !important;
        width: auto !important;
        height: auto !important;
        min-width: auto !important;
        min-height: auto !important;
    }
    
    /* Form icons - reserve space with specific styling */
    .form-floating-custom .form-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 1rem;
        transition: color 0.2s;
        pointer-events: none;
        z-index: 2;
        width: 1.25em !important;
        height: 1.25em !important;
        min-width: 1.25em !important;
        min-height: 1.25em !important;
        line-height: 1.25em !important;
        text-align: center !important;
        background-color: transparent !important;
        opacity: 1 !important;
        color: #9ca3af !important;
        animation: none !important;
    }
    
    .form-floating-custom .form-control:focus ~ .form-icon {
        color: #1a56db !important;
    }
    
    /* Login header icon */
    .login-logo i {
        background-color: transparent !important;
        opacity: 1 !important;
        color: #1a56db !important;
        animation: none !important;
        width: auto !important;
        height: auto !important;
        min-width: auto !important;
        min-height: auto !important;
    }
    
    /* Select2 icons */
    .select2-container .select2-selection__arrow {
        width: 1.25em !important;
        height: 1.25em !important;
    }
    
    .select2-container .select2-selection__arrow b {
        background-color: transparent !important;
        opacity: 1 !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .fa-solid, 
        .fa-regular, 
        .fas, 
        .far {
            width: 1em !important;
            height: 1em !important;
            min-width: 1em !important;
            min-height: 1em !important;
            line-height: 1em !important;
        }
    }
</style>
