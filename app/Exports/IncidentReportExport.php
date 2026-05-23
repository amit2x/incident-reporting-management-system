<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class IncidentReportExport implements WithMultipleSheets
{
    protected $data;
    protected $type;

    public function __construct(array $data, string $type)
    {
        $this->data = $data;
        $this->type = $type;
    }

    public function sheets(): array
    {
        $sheets = [
            new SummarySheet($this->data),
            new DepartmentSheet($this->data['department'] ?? []),
            new CategorySheet($this->data['category'] ?? []),
        ];

        if ($this->type === 'sla') {
            $sheets[] = new SlaSheet($this->data);
        }

        return $sheets;
    }
}

class SummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function array(): array
    {
        $stats = $this->data['stats'] ?? [];
        
        return [
            ['Total Incidents', $stats['total'] ?? 0],
            ['Open Incidents', $stats['open'] ?? 0],
            ['Resolved Incidents', $stats['resolved'] ?? 0],
            ['Closed Incidents', $stats['closed'] ?? 0],
            ['Escalated Incidents', $stats['escalated'] ?? 0],
            ['Overdue Incidents', $stats['overdue'] ?? 0],
            ['Average Response Time', round($stats['avg_response_time'] ?? 0, 2) . ' minutes'],
            ['Average Resolution Time', round($stats['avg_resolution_time'] ?? 0, 2) . ' minutes'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
        ];
    }
}

class DepartmentSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $departments;

    public function __construct(array $departments)
    {
        $this->departments = $departments;
    }

    public function title(): string
    {
        return 'Departments';
    }

    public function headings(): array
    {
        return ['Department', 'Total', 'Active', 'Resolved', 'Escalated', 'Performance %'];
    }

    public function array(): array
    {
        return array_map(function ($dept) {
            return [
                $dept['name'] ?? '',
                $dept['total'] ?? 0,
                $dept['active'] ?? 0,
                $dept['resolved'] ?? 0,
                $dept['escalated'] ?? 0,
                $dept['performance'] ?? 0,
            ];
        }, $this->departments);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ];
    }
}

class CategorySheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $categories;

    public function __construct(array $categories)
    {
        $this->categories = $categories;
    }

    public function title(): string
    {
        return 'Categories';
    }

    public function headings(): array
    {
        return ['Category', 'Total', 'Open', 'Resolved', 'SLA Compliance %'];
    }

    public function array(): array
    {
        return array_map(function ($cat) {
            return [
                $cat['name'] ?? '',
                $cat['total'] ?? 0,
                $cat['open'] ?? 0,
                $cat['resolved'] ?? 0,
                $cat['sla_compliance'] ?? 0,
            ];
        }, $this->categories);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ];
    }
}

class SlaSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'SLA Compliance';
    }

    public function headings(): array
    {
        return ['Department', 'SLA Compliance %'];
    }

    public function array(): array
    {
        return array_map(function ($value, $key) {
            return [$key, $value];
        }, $this->data['compliance'] ?? [], array_keys($this->data['compliance'] ?? []));
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ];
    }
}