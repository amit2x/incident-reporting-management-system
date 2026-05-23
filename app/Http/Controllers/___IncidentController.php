<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentMedia;
use App\Models\User;
use App\Models\Department;
use App\Models\IncidentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;

class IncidentController extends Controller
{
    /**
     * Show the form for creating a new incident.
     */
    public function create()
    {
        $this->authorize('create-incident');
        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();
        return view('incidents.create', compact('departments', 'categories'));
    }

    /**
     * Display the specified incident.
     */
    public function show(Request $request, Incident $incident)
    {
        if (!Auth::user()->canAccessIncident($incident)) {
            abort(403, 'Unauthorized access to this incident.');
        }

        $incident->increment('views_count');
        $incident->load([
            'reporter',
            'assignedTo',
            'department',
            'category',
            'escalatedTo',
            'media',
            'comments' => function ($query) {
                $query->with('user')->latest();
            },
            'escalations',
            'assignments',
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'comments' => $incident->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'content' => $comment->content,
                            'user' => [
                                'name' => $comment->user?->name,
                                'avatar_url' => $comment->user?->avatar_url,
                            ],
                            'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                            'created_at_diff' => $comment->created_at->diffForHumans(),
                        ];
                    }),
                    'comments_count' => $incident->comments->count(),
                ]
            ]);
        }

        return view('incidents.show', compact('incident'));
    }

    /**
     * Assign incident to user.
     */
    public function assign(Request $request, Incident $incident)
    {
        $this->authorize('assign-incident');

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $user = User::findOrFail($request->assigned_to);

        $incident->update([
            'assigned_to' => $user->id,
            'status' => $incident->status === 'open' ? 'acknowledged' : $incident->status,
            'acknowledged_at' => $incident->status === 'open' ? now() : $incident->acknowledged_at,
        ]);

        $incident->assignments()->create([
            'assigned_by' => Auth::id(),
            'assigned_to' => $user->id,
            'notes' => $request->notes,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $incident->logActivity('assigned', null, ['assigned_to' => $user->name]);

        // Send notification
        try {
            $user->notify(new \App\Notifications\IncidentAssignedNotification($incident));
        } catch (\Exception $e) {
            \Log::warning('Assignment notification failed: ' . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Incident assigned successfully']);
        }

        return back()->with('success', 'Incident assigned successfully');
    }

    /**
     * Escalate incident.
     */
    public function escalate(Request $request, Incident $incident)
    {
        $this->authorize('escalate-incident');

        $request->validate([
            'escalated_to' => 'required|exists:users,id',
            'to_department_id' => 'required|exists:departments,id',
            'reason' => 'required|string|max:500',
        ]);

        $escalation = $incident->escalations()->create([
            'escalated_by' => Auth::id(),
            'escalated_to' => $request->escalated_to,
            'from_department_id' => $incident->department_id,
            'to_department_id' => $request->to_department_id,
            'level' => $incident->escalations()->count() + 1,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        $incident->update([
            'status' => 'escalated',
            'escalated_to' => $request->escalated_to,
            'escalated_at' => now(),
        ]);

        $incident->logActivity('escalated', null, ['escalated_to' => User::find($request->escalated_to)->name]);

        // Send notification
        try {
            $escalatedUser = User::find($request->escalated_to);
            if ($escalatedUser) {
                $escalatedUser->notify(new \App\Notifications\IncidentEscalatedNotification($incident, $escalation));
            }
        } catch (\Exception $e) {
            \Log::warning('Escalation notification failed: ' . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Incident escalated successfully']);
        }

        return back()->with('success', 'Incident escalated successfully');
    }

    /**
     * Resolve incident.
     */
    public function resolve(Request $request, Incident $incident)
    {
        $this->authorize('resolve-incident');

        $request->validate([
            'resolution_notes' => 'required|string|max:1000',
        ]);

        $incident->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $request->resolution_notes,
        ]);

        $incident->logActivity('resolved', null, ['resolution_notes' => $request->resolution_notes]);

        // Send notification to reporter
        try {
            if ($incident->reporter) {
                $incident->reporter->notify(new \App\Notifications\IncidentResolvedNotification($incident));
            }
        } catch (\Exception $e) {
            \Log::warning('Resolve notification failed: ' . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Incident resolved successfully']);
        }

        return back()->with('success', 'Incident resolved successfully');
    }

    /**
     * Close incident.
     */
    public function close(Request $request, Incident $incident)
    {
        $this->authorize('close-incident');

        $incident->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $incident->logActivity('closed', null, null);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Incident closed successfully']);
        }

        return back()->with('success', 'Incident closed successfully');
    }

    /**
     * Reopen incident.
     */
    public function reopen(Request $request, Incident $incident)
    {
        $this->authorize('reopen-incident');

        $incident->update([
            'status' => 'open',
            'resolved_at' => null,
            'closed_at' => null,
            'resolution_notes' => null,
        ]);

        $incident->logActivity('reopened', null, null);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Incident reopened successfully']);
        }

        return back()->with('success', 'Incident reopened successfully');
    }

    /**
     * Add comment to incident.
     */
    public function addComment(Request $request, Incident $incident)
    {
        $this->authorize('add-comment');

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $comment = $incident->comments()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
            'parent_id' => $request->parent_id ?? null,
        ]);

        $incident->increment('comments_count');
        $incident->logActivity('comment_added', null, ['comment' => \Str::limit($request->content, 50)]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => [
                    'comment' => [
                        'id' => $comment->id,
                        'content' => $comment->content,
                        'user' => [
                            'name' => Auth::user()->name,
                            'avatar_url' => Auth::user()->avatar_url,
                        ],
                        'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                        'created_at_diff' => $comment->created_at->diffForHumans(),
                    ],
                    'comments_count' => $incident->comments()->count(),
                ]
            ]);
        }

        return back()->with('success', 'Comment added successfully');
    }
}
