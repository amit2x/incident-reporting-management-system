<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Mail\Incident\IncidentAssignedMail;
use App\Mail\Incident\IncidentClosedMail;
use App\Mail\Incident\IncidentCommentMail;
use App\Mail\Incident\IncidentCreatedMail;
use App\Mail\Incident\IncidentEscalatedMail;
use App\Mail\Incident\IncidentMentionMail;
use App\Mail\Incident\IncidentRejectedMail;
use App\Mail\Incident\IncidentResolvedMail;
use App\Models\Escalation;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;
use App\Notifications\IncidentAssignedNotification;
use App\Notifications\IncidentClosedNotification;
use App\Notifications\IncidentCreatedNotification;
use App\Notifications\IncidentEscalatedNotification;
use App\Notifications\IncidentRejectedNotification;
use App\Notifications\IncidentResolvedNotification;
use App\Notifications\MentionNotification;
use App\Notifications\NewCommentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class EmailTestController extends Controller
{
    /**
     * Display email test dashboard.
     */
    public function index()
    {
        return view('test.emails');
    }

    /**
     * Test a specific email.
     */
    public function test(Request $request)
    {
        $type = $request->input('type');
        $format = $request->input('format', 'html');
        $email = $request->input('email', auth()->user()?->email);

        // Get test data
        $incident = $this->getTestIncident();
        $user = $this->getTestUser();
        $escalation = $this->getTestEscalation($incident);
        $comment = $this->getTestComment($incident);

        // Log::info(['incident' => $incident, 'user' => $user, 'Esc ' => $escalation, 'comment' => $comment]);
        // Build the mail based on type
        $mail = $this->buildMail($type, $incident, $user, $escalation, $comment);
        Log::info(['mail' => $mail]);
        if (! $mail) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email type',
            ], 400);
        }

        // Send email if email address provided
        if ($email && $format === 'send') {
            Mail::to($email)->send($mail);

            return response()->json([
                'success' => true,
                'message' => "Email sent to {$email}",
            ]);
        }

        // Return HTML preview
        if ($format === 'html') {
            return $mail->render();
        }

        return response()->json([
            'success' => true,
            'data' => $mail->render(),
        ]);
    }

    /**
     * Test notification via database.
     */
    public function testNotification(Request $request)
    {
        $type = $request->input('type');
        $userId = $request->input('user_id', auth()->id());

        $user = User::find($userId);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $incident = $this->getTestIncident();
        $escalation = $this->getTestEscalation($incident);
        $comment = $this->getTestComment($incident);

        // Build notification based on type
        $notification = $this->buildNotification($type, $incident, $user, $escalation, $comment);

        if (! $notification) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid notification type',
            ], 400);
        }

        // Send notification
        $user->notify($notification);

        return response()->json([
            'success' => true,
            'message' => "Notification sent to {$user->name}",
            'data' => $notification->toDatabase($user),
        ]);
    }

    /**
     * Test all emails at once.
     */
    public function testAll(Request $request)
    {
        $email = $request->input('email', auth()->user()?->email);

        if (! $email) {
            return response()->json([
                'success' => false,
                'message' => 'Email address required',
            ], 400);
        }

        $types = [
            'assigned', 'closed', 'escalated', 'rejected',
            'resolved', 'created', 'mention', 'comment',
        ];

        $sent = [];
        $failed = [];

        foreach ($types as $type) {
            try {
                $incident = $this->getTestIncident();
                $user = $this->getTestUser();
                $escalation = $this->getTestEscalation($incident);
                $comment = $this->getTestComment($incident);

                $mail = $this->buildMail($type, $incident, $user, $escalation, $comment);
                Mail::to($email)->send($mail);

                $sent[] = $type;
            } catch (\Exception $e) {
                $failed[] = [
                    'type' => $type,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Test emails sent',
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Get or create test incident.
     */
    protected function getTestIncident(): Incident
    {
        $incident = Incident::first();

        if (! $incident) {
            $incident = Incident::create([
                'incident_id' => 'INC-TEST-001',
                'title' => 'Test Incident for Email',
                'description' => 'This is a test incident created for email testing purposes.',
                'status' => 'open',
                'priority' => 'high',
                'severity' => 'critical',
                'department_id' => 1,
                'category_id' => 1,
                'reported_by' => 1,
                'assigned_to' => 1,
                'location' => 'Test Location',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $incident;
    }

    /**
     * Get test user.
     */
    protected function getTestUser(): User
    {
        $user = User::first();

        if (! $user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        return $user;
    }

    /**
     * Get test escalation.
     */
    protected function getTestEscalation(Incident $incident): ?Escalation
    {
        $escalation = Escalation::where('incident_id', $incident->id)->first();

        if (! $escalation) {
            $escalation = Escalation::create([
                'incident_id' => $incident->id,
                'escalated_by' => 1,
                'escalated_to' => 1,
                'from_department_id' => 1,
                'to_department_id' => 1,
                'level' => 1,
                'reason' => 'Test escalation for email',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $escalation;
    }

    /**
     * Get test comment.
     */
    protected function getTestComment(Incident $incident): ?IncidentComment
    {
        $comment = IncidentComment::where('incident_id', $incident->id)->first();

        if (! $comment) {
            $comment = IncidentComment::create([
                'incident_id' => $incident->id,
                'user_id' => 1,
                'content' => 'This is a test comment for email testing.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $comment;
    }

    /**
     * Build mail based on type.
     */
    protected function buildMail(string $type, Incident $incident, User $user, ?Escalation $escalation, ?IncidentComment $comment)
    {
        return match ($type) {
            'assigned' => new IncidentAssignedMail($incident, $user, $user),
            'closed' => new IncidentClosedMail($incident, $user),
            'escalated' => new IncidentEscalatedMail($incident, $user, $escalation),
            'rejected' => new IncidentRejectedMail($incident, $user),
            'resolved' => new IncidentResolvedMail($incident, $user),
            'created' => new IncidentCreatedMail($incident, $user),
            'mention' => new IncidentMentionMail($incident, $user, $comment),
            'comment' => new IncidentCommentMail($incident, $user, $comment),
            default => null,
        };
    }

    /**
     * Build notification based on type.
     */
    protected function buildNotification(string $type, Incident $incident, User $user, ?Escalation $escalation, ?IncidentComment $comment)
    {
        return match ($type) {
            'assigned' => new IncidentAssignedNotification($incident, $user, $user),
            'closed' => new IncidentClosedNotification($incident, $user),
            'escalated' => new IncidentEscalatedNotification($incident, $user, $escalation),
            'rejected' => new IncidentRejectedNotification($incident, $user),
            'resolved' => new IncidentResolvedNotification($incident, $user),
            'created' => new IncidentCreatedNotification($incident, $user),
            'mention' => new MentionNotification($incident, $user, $comment),
            'comment' => new NewCommentNotification($incident, $user, $comment),
            default => null,
        };
    }
}
