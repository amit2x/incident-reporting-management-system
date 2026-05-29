{{-- resources/views/reports/exports/kpi-pdf.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>KPI Report - IRMSystem</title>
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

        .kpi-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .kpi-row {
            display: table-row;
        }

        .kpi-cell {
            display: table-cell;
            width: 16.6%;
            text-align: center;
            padding: 10px 5px;
            border: 1px solid #e5e7eb;
        }

        .kpi-value {
            font-size: 18px;
            font-weight: bold;
        }

        .kpi-label {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
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
        <h1>KPI Performance Report</h1>
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

    <div class="section-title">Key Performance Indicators</div>
    <div class="kpi-grid">
        @php $kpi = $kpi ?? []; @endphp
        <div class="kpi-row">
            <div class="kpi-cell">
                <div class="kpi-value" style="color:#3b82f6;">{{ $kpi['total_incidents'] ?? 0 }}</div>
                <div class="kpi-label">Total</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-value" style="color:#f59e0b;">{{ $kpi['open_incidents'] ?? 0 }}</div>
                <div class="kpi-label">Open</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-value" style="color:#10b981;">{{ $kpi['resolved_incidents'] ?? 0 }}</div>
                <div class="kpi-label">Resolved</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-value" style="color:#ef4444;">{{ $kpi['escalated_incidents'] ?? 0 }}</div>
                <div class="kpi-label">Escalated</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-value" style="color:#06b6d4;">{{ round($kpi['avg_response_time'] ?? 0, 1) }}m</div>
                <div class="kpi-label">Avg Response</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-value"
                    style="color:{{ ($kpi['sla_compliance'] ?? 100) >= 80 ? '#10b981' : '#f59e0b' }};">{{
                    $kpi['sla_compliance'] ?? 100 }}%</div>
                <div class="kpi-label">SLA</div>
            </div>
        </div>
    </div>

    <div class="section-title">Department Performance</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Department</th>
                <th>Total</th>
                <th>Active</th>
                <th>Resolved</th>
                <th>Escalated</th>
                <th>SLA %</th>
                <th>Performance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($department_performance ?? [] as $dept)
            <tr>
                <td>{{ $dept['name'] ?? '' }}</td>
                <td>{{ $dept['total'] ?? 0 }}</td>
                <td>{{ $dept['active'] ?? 0 }}</td>
                <td>{{ $dept['resolved'] ?? 0 }}</td>
                <td>{{ $dept['escalated'] ?? 0 }}</td>
                <td>{{ $dept['sla_compliance'] ?? 0 }}%</td>
                <td>{{ $dept['performance'] ?? 0 }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;">No data</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Category Breakdown</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Total</th>
                <th>Open</th>
                <th>Resolved</th>
                <th>SLA %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($category_breakdown ?? [] as $cat)
            <tr>
                <td>{{ $cat['name'] ?? '' }}</td>
                <td>{{ $cat['total'] ?? 0 }}</td>
                <td>{{ $cat['open'] ?? 0 }}</td>
                <td>{{ $cat['resolved'] ?? 0 }}</td>
                <td>{{ $cat['sla_compliance'] ?? 0 }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;">No data</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">SLA Compliance by Department</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Department</th>
                <th>SLA Compliance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sla_compliance ?? [] as $dept => $sla)
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

    <div class="footer">
        <p>Generated by IRMSystem - Incident Reporting & Management System</p>
        <p>© {{ date('Y') }} IRMSystem. All rights reserved.</p>
    </div>
</body>

</html>