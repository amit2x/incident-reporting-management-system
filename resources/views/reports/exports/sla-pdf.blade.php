{{-- resources/views/reports/exports/sla-pdf.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>SLA Compliance Report - IRMSystem</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1a56db;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 20px;
            color: #1a56db;
            margin: 0 0 5px;
        }

        .header .subtitle {
            font-size: 11px;
            color: #666;
        }

        .meta-info {
            margin-bottom: 20px;
            font-size: 10px;
            color: #666;
        }

        .meta-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-info td {
            padding: 3px 8px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a56db;
            margin: 20px 0 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }

        table.data-table th {
            background: #1a56db;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
        }

        table.data-table td {
            padding: 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        table.data-table tr:nth-child(even) {
            background: #f9fafb;
        }

        .kpi-box {
            text-align: center;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 15px;
            display: inline-block;
            width: 30%;
        }

        .kpi-box .value {
            font-size: 24px;
            font-weight: bold;
        }

        .kpi-box .label {
            font-size: 10px;
            color: #666;
        }

        .footer {
            text-align: center;
            font-size: 9px;
            color: #999;
            margin-top: 30px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>SLA Compliance Report</h1>
        <div class="subtitle">Incident Reporting & Management System</div>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td style="font-weight:bold;width:100px;">Period:</td>
                <td>{{ $date_from ?? '' }} to {{ $date_to ?? '' }}</td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Generated:</td>
                <td>{{ $generated_at ?? now()->format('d M Y, H:i') }}</td>
            </tr>
            <tr>
                <td style="font-weight:bold;">By:</td>
                <td>{{ $generated_by ?? 'System' }}</td>
            </tr>
        </table>
    </div>

    <div style="text-align:center;margin-bottom:20px;">
        <div class="kpi-box">
            <div class="value"
                style="color:{{ ($overall_sla ?? 100) >= 80 ? '#10b981' : (($overall_sla ?? 100) >= 50 ? '#f59e0b' : '#ef4444') }}">
                {{ $overall_sla ?? 100 }}%</div>
            <div class="label">Overall SLA Compliance</div>
        </div>
        <div class="kpi-box">
            <div class="value" style="color:#3b82f6;">{{ $total_incidents ?? 0 }}</div>
            <div class="label">Total Incidents</div>
        </div>
        <div class="kpi-box">
            <div class="value" style="color:#ef4444;">{{ $breached_incidents ?? 0 }}</div>
            <div class="label">SLA Breaches</div>
        </div>
    </div>

    <div class="section-title">SLA Compliance by Department</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Department</th>
                <th>SLA Compliance %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sla_by_department ?? [] as $dept => $sla)
            <tr>
                <td>{{ $dept }}</td>
                <td>{{ $sla }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="2" style="text-align:center;">No data</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">SLA Breaches</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Incident ID</th>
                <th>Title</th>
                <th>Department</th>
                <th>Category</th>
                <th>Breaches</th>
                <th>SLA Due</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sla_breaches ?? [] as $breach)
            <tr>
                <td>{{ $breach->incident_id }}</td>
                <td>{{ Str::limit($breach->title, 50) }}</td>
                <td>{{ $breach->department?->name ?? 'N/A' }}</td>
                <td>{{ $breach->category?->name ?? 'N/A' }}</td>
                <td>{{ $breach->sla_breach_count }}</td>
                <td>{{ $breach->sla_due_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($breach->status)) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;">No SLA breaches found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Generated by IRMSystem - Incident Reporting & Management System</p>
        <p>© {{ date('Y') }} IRMSystem. All rights reserved.</p>
    </div>
</body>

</html>