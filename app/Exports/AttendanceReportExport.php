<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $rows;
    protected $label;

    public function __construct($rows, $label)
    {
        $this->rows = $rows;
        $this->label = $label;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            ['Attendance Report: ' . $this->label],
            ['Department', 'Employee Name', 'Employee ID', 'Total Days', 'Present', 'Absent', 'Late', 'Half Day', 'On Leave', 'Self Marked', 'Nodal Marked']
        ];
    }

    public function map($row): array
    {
        $deptName = $row['user']->department ? $row['user']->department->name : 'No Department Assigned';

        return [
            $deptName,
            $row['user']->name,
            $row['user']->employee_id ?? '—',
            $row['total'],
            $row['present'],
            $row['absent'],
            $row['late'],
            $row['half_day'],
            $row['on_leave'],
            $row['self_marked'],
            $row['nodal_marked'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB']
                ]
            ],
        ];
    }
}