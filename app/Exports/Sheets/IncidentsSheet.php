<?php

// app/Exports/Sheets/IncidentsSheet.php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class IncidentsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $incidents;

    public function __construct($incidents)
    {
        $this->incidents = $incidents;
    }

    public function title(): string
    {
        return 'All Incidents';
    }

    public function headings(): array
    {
        return [
            'Incident ID',
            'Title',
            'Airport',
            'Department',
            'Category',
            'Severity',
            'Priority',
            'Status',
            'Reported By',
            'Assigned To',
            'Location',
            'Created At',
            'Acknowledged At',
            'Resolved At',
            'Closed At',
            'SLA Due At',
            'SLA Breaches',
        ];
    }

    public function array(): array
    {
        return $this->incidents->map(function ($incident) {
            return [
                $incident->incident_id,
                $incident->title,
                $incident->airport?->code ?? 'N/A',
                $incident->department?->name ?? 'N/A',
                $incident->category?->name ?? 'N/A',
                ucfirst($incident->severity),
                ucfirst($incident->priority),
                str_replace('_', ' ', ucfirst($incident->status)),
                $incident->reportedBy?->name ?? 'N/A',
                $incident->assignedTo?->name ?? 'Unassigned',
                $incident->location ?? 'N/A',
                optional($incident->created_at)->format('Y-m-d H:i'),
                optional($incident->acknowledged_at)->format('Y-m-d H:i'),
                optional($incident->resolved_at)->format('Y-m-d H:i'),
                optional($incident->closed_at)->format('Y-m-d H:i'),
                optional($incident->sla_due_at)->format('Y-m-d H:i'),
                $incident->sla_breach_count,
            ];
        })->toArray();
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
            ],
        ];
    }
}