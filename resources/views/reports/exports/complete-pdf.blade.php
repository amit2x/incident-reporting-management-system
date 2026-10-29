{{-- resources/views/reports/exports/complete-pdf.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Complete Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { color: #2563EB; font-size: 20px; }
        h2 { color: #1F2937; font-size: 14px; margin-top: 20px; border-bottom: 2px solid #E5E7EB; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #2563EB; color: white; padding: 6px; text-align: left; font-size: 10px; }
        td { padding: 5px; border-bottom: 1px solid #E5E7EB; font-size: 10px; }
        tr:nth-child(even) { background: #F9FAFB; }
        .header-info { background: #EFF6FF; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .header-info p { margin: 3px 0; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
        .badge-critical { background: #DC2626; color: white; }
        .badge-high { background: #EA580C; color: white; }
        .badge-medium { background: #CA8A04; color: white; }
        .badge-low { background: #16A34A; color: white; }
    </style>
</head>
<body>
    <h1>📊 IRMSystem - Complete Report</h1>

    <div class="header-info">
        <p><strong>Period:</strong> {{ $date_from }} to {{ $date_to }}</p>
        <p><strong>Department:</strong> {{ $department_name }}</p>
        <p><strong>Generated:</strong> {{ $generated_at }} by {{ $generated_by }}</p>
        @if($is_restricted)
            <p><strong>Note:</strong> This report is restricted to your department only</p>
        @endif
    </div>

    {{-- KPI Summary --}}
    <h2>KPI Summary</h2>
    <table>
        <tr><th>Metric</th><th>Value</th></tr>
        @foreach($kpi as $key => $value)
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
    </table>

    {{-- Department Performance --}}
    <h2>Department Performance</h2>
    <table>
        <thead>
            <tr>
                <th>Department</th>
                <th>Total</th>
                <th>Active</th>
                <th>Resolved</th>
                <th>Escalated</th>
                <th>SLA %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($department_performance as $dept)
                <tr>
                    <td>{{ $dept['name'] }}</td>
                    <td>{{ $dept['total'] }}</td>
                    <td>{{ $dept['active'] }}</td>
                    <td>{{ $dept['resolved'] }}</td>
                    <td>{{ $dept['escalated'] }}</td>
                    <td>{{ $dept['sla_compliance'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Recent Incidents --}}
    <h2>Recent Incidents (Top 50)</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Dept</th>
                <th>Category</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incidents->take(50) as $incident)
                <tr>
                    <td>{{ $incident->incident_id }}</td>
                    <td>{{ Str::limit($incident->title, 40) }}</td>
                    <td>{{ $incident->department?->code ?? 'N/A' }}</td>
                    <td>{{ $incident->category?->name ?? 'N/A' }}</td>
                    <td><span class="badge badge-{{ $incident->severity }}">{{ $incident->severity }}</span></td>
                    <td>{{ $incident->status }}</td>
                    <td>{{ $incident->created_at->format('d M Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>