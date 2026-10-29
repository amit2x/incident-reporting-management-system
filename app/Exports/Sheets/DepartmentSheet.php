<?php

// app/Exports/Sheets/DepartmentSheet.php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DepartmentSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $departments;

    public function __construct(array $departments)
    {
        $this->departments = $departments;
    }

    public function title(): string
    {
        return 'Department Performance';
    }

    public function headings(): array
    {
        return [
            'Department', 'Code', 'Total', 'Active',
            'Resolved', 'Escalated', 'SLA %', 'Avg Response (min)', 'Performance %',
        ];
    }

    public function array(): array
    {
        return array_map(function ($dept) {
            return [
                $dept['name'] ?? '',
                $dept['code'] ?? '',
                $dept['total'] ?? 0,
                $dept['active'] ?? 0,
                $dept['resolved'] ?? 0,
                $dept['escalated'] ?? 0,
                ($dept['sla_compliance'] ?? 0) . '%',
                $dept['avg_response_time'] ?? 0,
                ($dept['performance'] ?? 0) . '%',
            ];
        }, $this->departments);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'],
                ],
            ],
        ];
    }
}