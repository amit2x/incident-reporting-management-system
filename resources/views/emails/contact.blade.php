{{-- resources/views/emails/contact.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Submission</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .email-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .email-header {
            background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .email-header h2 {
            margin: 0;
            font-size: 22px;
            color: white;
        }

        .email-body {
            padding: 30px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6b7280;
            min-width: 100px;
        }

        .info-value {
            color: #1f2937;
            text-align: right;
            word-break: break-word;
        }

        .message-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
            white-space: pre-wrap;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .category-account {
            background: #dbeafe;
            color: #1e40af;
        }

        .category-incident {
            background: #fee2e2;
            color: #991b1b;
        }

        .category-technical {
            background: #fef3c7;
            color: #92400e;
        }

        .category-access {
            background: #d1fae5;
            color: #065f46;
        }

        .category-other {
            background: #e5e7eb;
            color: #374151;
        }

        .email-footer {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <h2>📩 New Contact Form Submission</h2>
        </div>
        <div class="email-body">
            <div class="info-row">
                <span class="info-label">From</span>
                <span class="info-value">{{ $name }} ({{ $email }})</span>
            </div>
            <div class="info-row">
                <span class="info-label">Subject</span>
                <span class="info-value">{{ $subject }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Category</span>
                <span class="info-value">
                    <span class="badge category-{{ $category }}">{{ ucfirst($category) }}</span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">User</span>
                <span class="info-value">
                    @if($isAuthenticated && $user)
                    {{ $user->name }} ({{ $user->email }}) - {{ $user->getFirstRoleName() }}
                    @else
                    Guest / Not Logged In
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Date</span>
                <span class="info-value">{{ now()->format('d M Y, H:i') }}</span>
            </div>

            <div style="margin-top: 16px;">
                <strong style="color: #374151;">Message:</strong>
                <div class="message-box">{{ $userMessage }}</div>
            </div>
        </div>
        <div class="email-footer">
            <p>This email was sent from the IRMSystem Contact Form.</p>
            <p>© {{ date('Y') }} IRMSystem. All rights reserved.</p>
        </div>
    </div>
</body>

</html>