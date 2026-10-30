<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2 { text-align: center; margin-bottom: 5px; color: #2563EB; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #2563EB; color: white; padding: 8px 5px; text-align: left; font-size: 9px; }
        td { padding: 6px 5px; border-bottom: 1px solid #ddd; font-size: 9px; }
        .text-center { text-align: center; }
        .text-success { color: #10B981; font-weight: bold; }
        .text-danger { color: #EF4444; }
        .text-info { color: #3B82F6; }
        .text-primary { color: #6366F1; }
        .dept-row { background: #F3F4F6; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Attendance Report</h2>
    <div class="subtitle">{{ $label }} ({{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }})</div>

    <table>
        <thead>
            <tr>
                <th>Department / Employee</th>
                <th class="text-center">Total</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Late</th>
                <th class="text-center">Half Day</th>
                <th class="text-center">Leave</th>
                <th class="text-center">Self</th>
                <th class="text-center">Nodal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows->groupBy('user.department_id') as $deptId => $deptUsers)
                @php
                    $firstUser = $deptUsers->first();
                    $department = $firstUser['user']->department ?? null;
                    $deptName = $department ? $department->name : 'No Department Assigned';
                    
                    $deptTotalPresent = $deptUsers->sum('present');
                    $deptTotalAbsent = $deptUsers->sum('absent');
                    $deptTotalLate = $deptUsers->sum('late');
                    $deptTotalHalfDay = $deptUsers->sum('half_day');
                    $deptTotalLeave = $deptUsers->sum('on_leave');
                    $deptTotalSelf = $deptUsers->sum('self_marked');
                    $deptTotalNodal = $deptUsers->sum('nodal_marked');
                    $deptTotalDays = $deptUsers->sum('total');
                    $isDeptPresent = $deptTotalPresent > 0;
                @endphp

                {{-- Department Header --}}
                <tr class="dept-row">
                    <td>{{ $deptName }} {{ $isDeptPresent ? '(Participated)' : '(Absent)' }}</td>
                    <td class="text-center">{{ $deptTotalDays }}</td>
                    <td class="text-center text-success">{{ $deptTotalPresent }}</td>
                    <td class="text-center text-danger">{{ $deptTotalAbsent }}</td>
                    <td class="text-center">{{ $deptTotalLate }}</td>
                    <td class="text-center">{{ $deptTotalHalfDay }}</td>
                    <td class="text-center">{{ $deptTotalLeave }}</td>
                    <td class="text-center text-info">{{ $deptTotalSelf }}</td>
                    <td class="text-center text-primary">{{ $deptTotalNodal }}</td>
                </tr>

                {{-- Employees --}}
                @foreach($deptUsers as $r)
                    <tr>
                        <td style="padding-left: 15px;">{{ $r['user']->name }} ({{ $r['user']->employee_id }})</td>
                        <td class="text-center">{{ $r['total'] }}</td>
                        <td class="text-center text-success">{{ $r['present'] }}</td>
                        <td class="text-center text-danger">{{ $r['absent'] }}</td>
                        <td class="text-center">{{ $r['late'] }}</td>
                        <td class="text-center">{{ $r['half_day'] }}</td>
                        <td class="text-center">{{ $r['on_leave'] }}</td>
                        <td class="text-center text-info">{{ $r['self_marked'] }}</td>
                        <td class="text-center text-primary">{{ $r['nodal_marked'] }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>