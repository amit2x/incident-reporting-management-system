<?php

// app/Exports/Sheets/CategorySheet.php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CategorySheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $categories;

    public function __construct(array $categories)
    {
        $this->categories = $categories;
    }

    public function title(): string
    {
        return 'Category Breakdown';
    }

    public function headings(): array
    {
        return ['Category', 'Total', 'Open', 'Resolved', 'SLA %'];
    }

    public function array(): array
    {
        return array_map(function ($cat) {
            return [
                $cat['name'] ?? '',
                $cat['total'] ?? 0,
                $cat['open'] ?? 0,
                $cat['resolved'] ?? 0,
                ($cat['sla_compliance'] ?? 0) . '%',
            ];
        }, $this->categories);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '7C3AED'],
                ],
            ],
        ];
    }
}