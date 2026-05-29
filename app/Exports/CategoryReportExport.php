<?php

// app/Exports/CategoryReportExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected array $categories;

    public function __construct(array $categories)
    {
        $this->categories = $categories;
    }

    public function title(): string
    {
        return 'Category Analysis';
    }

    public function headings(): array
    {
        return [
            'Category',
            'Total Incidents',
            'Resolved',
            'Open',
            'Escalated',
            'SLA Breaches',
            'SLA Compliance %',
            'Resolution Rate %',
            'Avg Response Time (min)',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->categories as $cat) {
            $rows[] = [
                $cat['name'] ?? '',
                $cat['total'] ?? 0,
                $cat['resolved'] ?? 0,
                $cat['open'] ?? 0,
                $cat['escalated'] ?? 0,
                $cat['breached'] ?? 0,
                $cat['sla_compliance'] ?? 0,
                $cat['resolution_rate'] ?? 0,
                $cat['avg_response_time'] ?? 0,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
        ];
    }
}
