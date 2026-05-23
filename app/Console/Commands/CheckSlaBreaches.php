<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckSlaBreaches extends Command
{
    protected $signature = 'incidents:check-sla';
    protected $description = 'Check for SLA breaches and send notifications';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $breachedIncidents = Incident::where('sla_due_at', '<', now())
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])
            ->where(function ($query) {
                $query->whereNull('sla_breach_notified_at')
                    ->orWhere('sla_breach_count', '>', 0);
            })
            ->get();

        foreach ($breachedIncidents as $incident) {
            $this->handleSlaBreach($incident);
        }

        $this->info("Checked SLA for {$breachedIncidents->count()} incidents");
    }

    protected function handleSlaBreach(Incident $incident): void
    {
        // Increment breach count
        $incident->increment('sla_breach_count');
        $incident->update(['sla_breach_notified_at' => now()]);

        // Notify HOD
        $hod = $incident->department->getHeadOfDepartment();
        if ($hod) {
            // Implement SLA breach notification
        }

        // Auto-escalate if max breaches reached
        if ($incident->sla_breach_count >= 3) {
            $this->autoEscalate($incident);
        }
    }

    protected function autoEscalate(Incident $incident): void
    {
        $admin = \App\Models\User::role('admin')->first();
        if ($admin) {
            $incident->escalate($admin->id, 'Auto-escalated due to multiple SLA breaches');
        }
    }
}