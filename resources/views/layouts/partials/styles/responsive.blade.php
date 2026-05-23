<style>
    /* ==========================================
       MOBILE FIRST (< 768px)
       ========================================== */
    @media (max-width: 767.98px) {
        .desktop-sidebar,
        .desktop-topbar .hide-on-mobile,
        .hide-on-mobile {
            display: none !important;
        }

        .show-on-mobile {
            display: block !important;
        }

        .mobile-bottom-nav {
            display: block;
        }

        .fab {
            display: flex;
        }

        .content-with-sidebar {
            margin-left: 0;
        }

        .desktop-topbar {
            left: 0;
            height: 56px;
            padding: 0 12px;
        }

        .page-content {
            padding-top: 56px;
            padding-bottom: calc(var(--bottom-nav-height) + var(--safe-area-bottom) + 20px);
        }

        .container-fluid {
            padding-left: 12px;
            padding-right: 12px;
        }

        h1 { font-size: 1.5rem; }
        h2 { font-size: 1.25rem; }
        h3 { font-size: 1.125rem; }

        .card {
            border-radius: var(--radius-lg);
        }
    }

    /* ==========================================
       TABLET (768px - 1023px)
       ========================================== */
    @media (min-width: 768px) and (max-width: 1023.98px) {
        .desktop-sidebar {
            transform: translateX(-100%);
            width: 280px;
        }

        .desktop-sidebar.show {
            transform: translateX(0);
        }

        .content-with-sidebar {
            margin-left: 0;
        }

        .desktop-topbar {
            left: 0;
        }

        .content-with-sidebar .desktop-topbar {
            left: 0;
        }

        .mobile-bottom-nav {
            display: none;
        }

        .fab {
            display: none;
        }

        .page-content {
            padding-bottom: 40px;
        }
    }

    /* ==========================================
       DESKTOP (≥ 1024px)
       ========================================== */
    @media (min-width: 1024px) {
        .desktop-sidebar {
            transform: translateX(0);
        }

        .content-with-sidebar {
            margin-left: var(--sidebar-width);
        }

        .content-with-sidebar .desktop-topbar {
            left: var(--sidebar-width);
        }

        .content-full .desktop-topbar {
            left: 0;
        }

        .mobile-bottom-nav,
        .mobile-drawer,
        .drawer-overlay,
        .fab,
        .mobile-only {
            display: none !important;
        }

        .page-content {
            padding-bottom: 40px;
        }
    }

    /* ==========================================
       LARGE DESKTOP (≥ 1280px)
       ========================================== */
    @media (min-width: 1280px) {
        .container-fluid {
            max-width: 1280px;
            margin: 0 auto;
        }
    }

    /* ==========================================
       SAFE AREA (iPhone X+)
       ========================================== */
    @supports (padding: max(0px)) {
        .mobile-bottom-nav {
            padding-bottom: max(4px, var(--safe-area-bottom));
        }

        .page-content {
            padding-bottom: calc(var(--bottom-nav-height) + max(20px, var(--safe-area-bottom)));
        }
    }

    /* ==========================================
       DARK MODE
       ========================================== */
    [data-theme="dark"] body {
        background: #0f172a;
        color: #e2e8f0;
    }

    [data-theme="dark"] .desktop-topbar {
        background: rgba(15, 23, 42, 0.95);
        border-bottom-color: rgba(255,255,255,0.06);
    }

    [data-theme="dark"] .mobile-bottom-nav {
        background: rgba(15, 23, 42, 0.98);
        border-top-color: rgba(255,255,255,0.06);
    }

    [data-theme="dark"] .card {
        background: #1e293b;
        border-color: #334155;
    }

    [data-theme="dark"] .text-muted {
        color: #94a3b8 !important;
    }

    /* ==========================================
       PRINT
       ========================================== */
    @media print {
        .desktop-sidebar,
        .desktop-topbar,
        .mobile-bottom-nav,
        .fab,
        .drawer-overlay,
        .mobile-drawer {
            display: none !important;
        }

        .content-with-sidebar {
            margin-left: 0;
        }

        .page-content {
            padding-top: 0;
        }

        body {
            background: white;
        }
    }
</style>
