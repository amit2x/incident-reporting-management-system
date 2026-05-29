<style>
    /* ==========================================
       BUTTONS
       ========================================== */
    .btn {
        font-weight: 600;
        font-size: 0.8125rem;
        padding: 8px 16px;
        border-radius: var(--radius);
        transition: all var(--transition-fast);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .btn:active {
        transform: scale(0.96);
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        pointer-events: none;
    }

    .btn-primary {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
        box-shadow: 0 2px 8px rgba(26, 86, 219, 0.25);
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
        box-shadow: 0 4px 16px rgba(26, 86, 219, 0.35);
        transform: translateY(-1px);
        color: white;
    }

    .btn-secondary {
        background: var(--gray-100);
        border-color: var(--gray-200);
        color: var(--gray-700);
    }

    .btn-secondary:hover {
        background: var(--gray-200);
        color: var(--gray-900);
    }

    .btn-success {
        background: var(--success);
        border-color: var(--success);
        color: white;
    }

    .btn-success:hover {
        background: #047857;
        color: white;
    }

    .btn-danger {
        background: var(--danger);
        border-color: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #b91c1c;
        color: white;
    }

    .btn-warning {
        background: var(--warning);
        border-color: var(--warning);
        color: white;
    }

    .btn-outline-primary {
        background: transparent;
        border-color: var(--primary);
        color: var(--primary);
    }

    .btn-outline-primary:hover {
        background: var(--primary);
        color: white;
    }

    .btn-outline-secondary {
        background: transparent;
        border-color: var(--gray-300);
        color: var(--gray-600);
    }

    .btn-outline-secondary:hover {
        background: var(--gray-100);
        color: var(--gray-800);
    }

    .btn-light {
        background: var(--gray-50);
        border-color: var(--gray-200);
        color: var(--gray-700);
    }

    .btn-light:hover {
        background: var(--gray-100);
        color: var(--gray-900);
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 0.75rem;
        border-radius: var(--radius-sm);
    }

    .btn-lg {
        padding: 12px 24px;
        font-size: 0.9375rem;
        border-radius: var(--radius-md);
    }

    .btn-icon {
        width: 38px;
        height: 38px;
        padding: 0;
        border-radius: var(--radius-md);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-icon-sm {
        width: 32px;
        height: 32px;
        padding: 0;
        border-radius: var(--radius-sm);
    }

    .btn-icon-lg {
        width: 48px;
        height: 48px;
        padding: 0;
        border-radius: var(--radius-lg);
    }

    .btn-rounded {
        border-radius: var(--radius-full) !important;
    }

    .btn-group .btn {
        border-radius: 0;
    }

    .btn-group .btn:first-child {
        border-radius: var(--radius) 0 0 var(--radius);
    }

    .btn-group .btn:last-child {
        border-radius: 0 var(--radius) var(--radius) 0;
    }

    /* ==========================================
       CARDS
       ========================================== */
    .card {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xs);
        transition: all var(--transition);
        margin-bottom: 16px;
    }

    .card:hover {
        box-shadow: var(--shadow-sm);
    }

    .card-header {
        background: transparent;
        border-bottom: 1px solid var(--gray-100);
        padding: 16px 20px;
        font-weight: 600;
        font-size: 0.9375rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: var(--radius-xl) var(--radius-xl) 0 0;
    }

    .card-body {
        padding: 20px;
    }

    .card-footer {
        background: transparent;
        border-top: 1px solid var(--gray-100);
        padding: 12px 20px;
        border-radius: 0 0 var(--radius-xl) var(--radius-xl);
    }

    .card-flush {
        border: none;
        box-shadow: none;
    }

    .card-compact .card-body {
        padding: 12px 16px;
    }

    /* ==========================================
       STAT CARDS
       ========================================== */
    .stat-card {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-xl);
        padding: 20px;
        transition: all var(--transition);
    }

    .stat-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 12px;
    }

    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gray-900);
        line-height: 1.2;
        font-family: var(--font-heading);
    }

    .stat-card .stat-label {
        font-size: 0.75rem;
        color: var(--gray-500);
        font-weight: 500;
        margin-top: 2px;
    }

    .stat-card .stat-change {
        font-size: 0.6875rem;
        font-weight: 600;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .stat-card .stat-change.positive {
        color: var(--success);
    }

    .stat-card .stat-change.negative {
        color: var(--danger);
    }

    /* ==========================================
       BADGES
       ========================================== */
    .badge {
        font-weight: 600;
        font-size: 0.6875rem;
        padding: 4px 10px;
        border-radius: var(--radius-full);
        display: inline-flex;
        align-items: center;
        gap: 4px;
        line-height: 1;
    }

    .badge-primary {
        background: var(--primary-100);
        color: var(--primary-700);
    }

    .badge-success {
        background: var(--success-light);
        color: var(--success);
    }

    .badge-danger {
        background: var(--danger-light);
        color: var(--danger);
    }

    .badge-warning {
        background: var(--warning-light);
        color: var(--warning);
    }

    .badge-info {
        background: var(--info-light);
        color: var(--info);
    }

    .badge-secondary {
        background: var(--gray-100);
        color: var(--gray-600);
    }

    .badge-dark {
        background: var(--gray-800);
        color: white;
    }

    .badge-dot {
        width: 8px;
        height: 8px;
        padding: 0;
        border-radius: 50%;
    }

    /* ==========================================
       STATUS BADGES
       ========================================== */
    .status-open {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-acknowledged {
        background: #fef3c7;
        color: #92400e;
    }

    .status-in_progress {
        background: #ede9fe;
        color: #5b21b6;
    }

    .status-escalated {
        background: #fce7f3;
        color: #9b1c1c;
    }

    .status-resolved {
        background: #d1fae5;
        color: #065f46;
    }

    .status-closed {
        background: #e2e8f0;
        color: #475569;
    }

    .status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    /* ==========================================
       PRIORITY BADGES
       ========================================== */
    .priority-critical {
        background: #fee2e2;
        color: #991b1b;
        border-left: 3px solid #dc2626;
    }

    .priority-high {
        background: #fed7aa;
        color: #9a3412;
        border-left: 3px solid #ea580c;
    }

    .priority-medium {
        background: #fef3c7;
        color: #92400e;
        border-left: 3px solid #d97706;
    }

    .priority-low {
        background: #d1fae5;
        color: #065f46;
        border-left: 3px solid #059669;
    }

    /* ==========================================
       FORMS
       ========================================== */
    .form-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 6px;
    }

    .form-label.required::after {
        content: ' *';
        color: var(--danger);
    }

    .form-control,
    .form-select {
        font-size: 0.875rem;
        /* padding: 10px 14px; */
        border: 1px solid var(--gray-300);
        border-radius: var(--radius);
        background: white;
        color: var(--gray-800);
        transition: all var(--transition-fast);
        width: 100%;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1);
        outline: none;
    }

    .form-control::placeholder {
        color: var(--gray-400);
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: var(--danger);
    }

    .form-control.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }

    .form-control-lg {
        padding: 12px 18px;
        font-size: 0.9375rem;
    }

    .form-control-sm {
        padding: 6px 10px;
        font-size: 0.75rem;
    }

    .form-text {
        font-size: 0.75rem;
        color: var(--gray-500);
        margin-top: 4px;
    }

    .invalid-feedback {
        font-size: 0.75rem;
        color: var(--danger);
        margin-top: 4px;
    }

    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    .form-switch .form-check-input:checked {
        background-color: var(--primary);
    }

    /* ==========================================
       INPUT GROUPS
       ========================================== */
    .input-group-text {
        font-size: 0.875rem;
        background: var(--gray-50);
        border-color: var(--gray-300);
        color: var(--gray-500);
    }

    /* ==========================================
       TABLES
       ========================================== */
    .table {
        font-size: 0.8125rem;
        margin-bottom: 0;
    }

    .table th {
        font-weight: 600;
        color: var(--gray-600);
        background: var(--gray-50);
        border-bottom: 2px solid var(--gray-200);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
    }

    .table td {
        padding: 12px 16px;
        vertical-align: middle;
        border-bottom: 1px solid var(--gray-100);
    }

    .table tr:hover td {
        background: var(--gray-50);
    }

    .table-compact th,
    .table-compact td {
        padding: 8px 12px;
    }

    /* ==========================================
       PROGRESS BARS
       ========================================== */
    .progress {
        height: 6px;
        background: var(--gray-100);
        border-radius: var(--radius-full);
        overflow: hidden;
    }

    .progress-bar {
        border-radius: var(--radius-full);
        transition: width var(--transition-slow);
    }

    /* ==========================================
       DROPDOWNS
       ========================================== */
    .dropdown-menu {
        border: 1px solid var(--gray-200);
        box-shadow: var(--shadow-lg);
        border-radius: var(--radius-lg);
        padding: 4px;
        min-width: 200px;
    }

    .dropdown-item {
        font-size: 0.8125rem;
        padding: 8px 12px;
        border-radius: var(--radius-sm);
        transition: all var(--transition-fast);
    }

    .dropdown-item:hover {
        background: var(--gray-50);
    }

    .dropdown-item i {
        width: 20px;
        text-align: center;
    }

    .dropdown-divider {
        margin: 4px 0;
        border-color: var(--gray-100);
    }

    /* ==========================================
       MODALS
       ========================================== */
    .modal-content {
        border: none;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xl);
    }

    .modal-header {
        padding: 20px 24px 0;
        border-bottom: none;
    }

    .modal-body {
        padding: 20px 24px;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--gray-100);
    }

    /* ==========================================
       TABS
       ========================================== */
    .nav-tabs {
        border-bottom: 2px solid var(--gray-200);
        gap: 4px;
    }

    .nav-tabs .nav-link {
        border: none;
        color: var(--gray-500);
        font-weight: 600;
        font-size: 0.8125rem;
        padding: 10px 16px;
        border-radius: var(--radius) var(--radius) 0 0;
        transition: all var(--transition-fast);
        position: relative;
    }

    .nav-tabs .nav-link::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--primary);
        transform: scaleX(0);
        transition: transform var(--transition-fast);
    }

    .nav-tabs .nav-link:hover {
        color: var(--gray-700);
    }

    .nav-tabs .nav-link.active {
        color: var(--primary);
    }

    .nav-tabs .nav-link.active::after {
        transform: scaleX(1);
    }

    /* ==========================================
       PAGINATION
       ========================================== */
    .pagination {
        gap: 4px;
    }

    .page-link {
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        color: var(--gray-600);
        font-size: 0.8125rem;
        padding: 6px 12px;
        transition: all var(--transition-fast);
    }

    .page-link:hover {
        background: var(--gray-50);
        color: var(--primary);
        border-color: var(--primary);
    }

    .page-item.active .page-link {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .page-item.disabled .page-link {
        background: var(--gray-50);
        color: var(--gray-400);
    }

    /* ==========================================
       SKELETON LOADERS
       ========================================== */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: var(--radius-sm);
    }

    .skeleton-text {
        height: 14px;
        margin-bottom: 8px;
    }

    .skeleton-title {
        height: 20px;
        width: 60%;
        margin-bottom: 12px;
    }

    .skeleton-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
    }

    .skeleton-card {
        height: 120px;
        border-radius: var(--radius-lg);
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    /* ==========================================
       EMPTY STATES
       ========================================== */
    .empty-state {
        text-align: center;
        padding: 48px 20px;
    }

    .empty-state .empty-icon {
        font-size: 3rem;
        color: var(--gray-300);
        margin-bottom: 16px;
    }

    .empty-state .empty-title {
        font-weight: 600;
        color: var(--gray-600);
        margin-bottom: 8px;
    }

    .empty-state .empty-description {
        color: var(--gray-400);
        font-size: 0.8125rem;
        margin-bottom: 20px;
    }

    /* ==========================================
       AVATAR GROUPS
       ========================================== */
    .avatar-group {
        display: flex;
    }

    .avatar-group .avatar {
        border: 2px solid white;
        margin-left: -8px;
    }

    .avatar-group .avatar:first-child {
        margin-left: 0;
    }

    /* ==========================================
       TOOLTIPS
       ========================================== */
    .tooltip .tooltip-inner {
        font-size: 0.75rem;
        padding: 6px 12px;
        border-radius: var(--radius-sm);
    }
</style>