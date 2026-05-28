<style>
    :root {
        /* ==========================================
           PRIMARY COLOR PALETTE
           ========================================== */
        --primary: #1a56db;
        --primary-dark: #1e40af;
        --primary-light: #3b82f6;
        --primary-50: #eff6ff;
        --primary-100: #dbeafe;
        --primary-200: #bfdbfe;
        --primary-500: #3b82f6;
        --primary-600: #2563eb;
        --primary-700: #1d4ed8;

        --secondary: #7c3aed;
        --secondary-light: #a78bfa;

        --accent: #f59e0b;
        --accent-light: #fcd34d;

        /* ==========================================
           STATUS COLORS
           ========================================== */
        --success: #059669;
        --success-light: #d1fae5;
        --success-bg: #ecfdf5;

        --danger: #dc2626;
        --danger-light: #fee2e2;
        --danger-bg: #fef2f2;

        --warning: #d97706;
        --warning-light: #fef3c7;
        --warning-bg: #fffbeb;

        --info: #0891b2;
        --info-light: #cffafe;
        --info-bg: #ecfeff;

        /* ==========================================
           NEUTRAL COLORS
           ========================================== */
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --gray-900: #111827;

        --white: #ffffff;
        --black: #000000;

        /* ==========================================
           TYPOGRAPHY
           ========================================== */
        --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        --font-heading: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

        --text-xs: 0.75rem;
        --text-sm: 0.8125rem;
        --text-base: 0.875rem;
        --text-lg: 1rem;
        --text-xl: 1.125rem;
        --text-2xl: 1.25rem;
        --text-3xl: 1.5rem;
        --text-4xl: 1.875rem;

        /* ==========================================
           SPACING & LAYOUT
           ========================================== */
        --sidebar-width: 260px;
        --topbar-height: 64px;
        --bottom-nav-height: 64px;
        --safe-area-bottom: env(safe-area-inset-bottom, 0px);
        --safe-area-top: env(safe-area-inset-top, 0px);

        /* ==========================================
           BORDERS & RADIUS
           ========================================== */
        --radius-xs: 4px;
        --radius-sm: 6px;
        --radius: 8px;
        --radius-md: 10px;
        --radius-lg: 12px;
        --radius-xl: 16px;
        --radius-2xl: 20px;
        --radius-full: 9999px;

        /* ==========================================
           SHADOWS
           ========================================== */
        --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
        --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);

        /* ==========================================
           TRANSITIONS
           ========================================== */
        --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ==========================================
       GLOBAL RESET
       ========================================== */
    *, *::before, *::after {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        scroll-behavior: smooth;
        -webkit-tap-highlight-color: transparent;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    body {
        font-family: var(--font-primary);
        font-size: var(--text-base);
        line-height: 1.6;
        color: var(--gray-800);
        background: var(--gray-50);
        overflow-x: hidden;
        min-height: 100vh;
        min-height: -webkit-fill-available;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: var(--font-heading);
        font-weight: 700;
        color: var(--gray-900);
        line-height: 1.3;
    }

    h1 { font-size: 1.75rem; }
    h2 { font-size: 1.5rem; }
    h3 { font-size: 1.25rem; }
    h4 { font-size: 1.125rem; }
    h5 { font-size: 1rem; }
    h6 { font-size: 0.875rem; }

    a {
        color: var(--primary);
        text-decoration: none;
        transition: color var(--transition-fast);
    }
    a:hover { color: var(--primary-dark); }

    img { max-width: 100%; }

    ul, ol { list-style: none; padding: 0; margin: 0; }

    ::selection {
        background: var(--primary);
        color: white;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--gray-400); }
</style>
