<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Assigned</title>
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px;
        }
        .incident-details {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #64748b;
        }
        .detail-value {
            color: #1e293b;
        }
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-high { background: #fee2e2; color: #991b1b; }
        .priority-medium { background: #fef3c7; color: #92400e; }
        .priority-low { background: #d1fae5; color: #065f46; }
        .action-button {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0;
        }
        .email-footer {
            text-align: center;
            padding: 20px;
            color: #64748b;
            font-size: 12px;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>📋 Incident Assigned to You</h1>
        </div>
        <div class="email-body">
            <p>Hello {{ $user->name }},</p>
            <p>An incident has been assigned to you for action. Please review the details below and take appropriate action.</p>
            
            <div class="incident-details">
                <div class="detail-row">
                    <span class="detail-label">Incident ID</span>
                    <span class="detail-value">{{ $incident->incident_id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Title</span>
                    <span class="detail-value">{{ $incident->title }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Priority</span>
                    <span class="detail-value">
                        <span class="priority-badge priority-{{ $incident->priority }}">
                            {{ ucfirst($incident->priority) }}
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Department</span>
                    <span class="detail-value">{{ $incident->department->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Category</span>
                    <span class="detail-value">{{ $incident->category->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Location</span>
                    <span class="detail-value">{{ $incident->location ?? 'Not specified' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Reported</span>
                    <span class="detail-value">{{ $incident->created_at->format('d M Y, H:i') }}</span>
                </div>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ route('incidents.show', $incident->id) }}" class="action-button">
                    View Incident Details
                </a>
            </div>
            
            <p>Please acknowledge the incident and update the status as you progress.</p>
            <p>If you have any questions, please contact your department head or the system administrator.</p>
        </div>
        <div class="email-footer">
            <p>This is an automated notification from IRMS - Incident Reporting & Management System</p>
            <p>© {{ date('Y') }} IRMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>