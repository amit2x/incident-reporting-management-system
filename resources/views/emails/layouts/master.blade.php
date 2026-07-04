<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'SAMANVAY' }}</title>
    <style>
        /* Reset Styles */
        body, table, td, p, a, div, span {
            margin: 0;
            padding: 0;
            border: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #1e293b;
        }

        body {
            background-color: #f8fafc;
            padding: 20px 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .email-content {
            padding: 32px 40px 24px;
        }

        @media (max-width: 600px) {
            .email-content {
                padding: 24px 20px;
            }
            .email-container {
                border-radius: 0;
                max-width: 100%;
            }
        }

        /* Typography */
        .text-muted {
            color: #64748b;
        }
        .text-center {
            text-align: center;
        }
        .text-sm {
            font-size: 12px;
        }
        .text-xs {
            font-size: 10px;
        }
        .fw-bold {
            font-weight: 700;
        }
        .fw-semibold {
            font-weight: 600;
        }

        .mt-1 { margin-top: 8px; }
        .mt-2 { margin-top: 16px; }
        .mt-3 { margin-top: 24px; }
        .mt-4 { margin-top: 32px; }
        .mb-1 { margin-bottom: 8px; }
        .mb-2 { margin-bottom: 16px; }
        .mb-3 { margin-bottom: 24px; }
        .mb-4 { margin-bottom: 32px; }

        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 24px 0;
        }

        .divider-light {
            border: none;
            border-top: 1px solid #f1f5f9;
            margin: 16px 0;
        }

        .greeting {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }

        .greeting span {
            color: #0d47a1;
        }

        /* Status Banner */
        .status-banner {
            padding: 16px 24px;
            border-radius: 8px;
            margin: 16px 0 24px;
            color: #ffffff;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-banner .icon {
            font-size: 20px;
        }

        /* Card Styles */
        .card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px 24px;
            margin: 16px 0;
        }

        .card-title {
            font-weight: 600;
            font-size: 13px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #64748b;
            font-size: 13px;
        }

        .info-value {
            font-weight: 500;
            font-size: 13px;
            color: #0f172a;
        }

        .info-value .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-critical { background: #fecaca; color: #991b1b; }
        .badge-high { background: #fed7aa; color: #9a3412; }
        .badge-medium { background: #fde68a; color: #78350f; }
        .badge-low { background: #d1fae5; color: #065f46; }
        .badge-open { background: #bfdbfe; color: #1e40af; }
        .badge-acknowledged { background: #fde68a; color: #78350f; }
        .badge-in_progress { background: #ddd6fe; color: #5b21b6; }
        .badge-escalated { background: #fecaca; color: #991b1b; }
        .badge-resolved { background: #d1fae5; color: #065f46; }
        .badge-closed { background: #e5e7eb; color: #374151; }

        /* Description */
        .description-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin: 8px 0 16px;
            font-size: 13px;
            color: #334155;
            line-height: 1.7;
        }

        /* Button Styles */
        .btn {
            display: inline-block;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: #0d47a1;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #0a3a86;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        /* Footer */
        .footer {
            background: #f8fafc;
            padding: 24px 40px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .footer-brand {
            font-weight: 700;
            font-size: 16px;
            color: #0d47a1;
        }

        .footer-sub {
            font-size: 12px;
            color: #64748b;
        }

        .footer-text {
            font-size: 11px;
            color: #94a3b8;
        }

        .footer-divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 12px auto;
            width: 40px;
        }

        @media (max-width: 600px) {
            .footer {
                padding: 16px 20px;
            }
            .btn {
                padding: 10px 24px;
                font-size: 13px;
                display: block;
                text-align: center;
            }
            .status-banner {
                font-size: 13px;
                padding: 12px 16px;
            }
            .card {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f8fafc">
        <tr>
            <td align="center" style="padding: 20px 10px;">
                <div class="email-container">
                    {{-- Header --}}
                    @include('emails.partials.header')

                    {{-- Content --}}
                    <div class="email-content">
                        {{ $slot }}
                    </div>

                    {{-- Footer --}}
                    @include('emails.partials.footer')
                </div>
            </td>
        </tr>
    </table>
</body>
</html>