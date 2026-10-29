<?php

// app/Exports/CompleteReportExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CompleteReportExport implements WithMultipleSheets
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new Sheets\KpiSummarySheet($this->data),
            new Sheets\IncidentsSheet($this->data['incidents'] ?? []),
            new Sheets\DepartmentSheet($this->data['department_performance'] ?? []),
            new Sheets\CategorySheet($this->data['category_breakdown'] ?? []),
            new Sheets\SlaSheet($this->data),
        ];
    }
}