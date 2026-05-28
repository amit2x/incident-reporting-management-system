<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentComment;
use App\Models\IncidentMedia;
use App\Models\User;
use App\Notifications\IncidentAssignedNotification;
use App\Notifications\IncidentEscalatedNotification;
use App\Notifications\IncidentRejectedNotification;
use App\Notifications\IncidentResolvedNotification;
use App\Notifications\NewIncidentNotification;
use App\Repositories\IncidentRepository;
use App\Services\IncidentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class IncidentController extends Controller
{
    protected IncidentService $incidentService;
    protected NotificationService $notificationService;
    protected IncidentRepository $incidentRepository;

    public function __construct(
        IncidentService $incidentService,
        NotificationService $notificationService,
        IncidentRepository $incidentRepository
    ) {
        $this->incidentService = $incidentService;
        $this->notificationService = $notificationService;
        $this->incidentRepository = $incidentRepository;
        $this->middleware('auth');
    }

    // ==========================================
    // INDEX
    // ==========================================
    public function index(Request $request)
    {
        $filters = $request->only([
            'department_id', 'category_id', 'severity', 'priority',
            'status', 'assigned_to', 'date_from', 'date_to', 'search',
        ]);

        $user = Auth::user();

        // Apply role-based filters
        if (!$user->isAdmin()) {
            $filters['department_id'] = $user->department_id;
        }

        // Get stats
        $stats = $this->getQuickStats($filters);

        // Get incidents
        $incidents = $this->incidentRepository->getFeedIncidents($filters, 15);

        if ($request->ajax()) {
            return $this->paginatedResponse($incidents);
        }

        return view('incidents.index', compact('incidents', 'filters', 'stats'));
    }

    // ==========================================
    // QUICK STATS HELPER
    // ==========================================
    private function getQuickStats(array $filters): array
    {
        $query = \Illuminate\Support\Facades\DB::table('incidents')
            ->whereNull('deleted_at');

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        $result = $query->selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END), 0) as open,
            COALESCE(SUM(CASE WHEN status = 'acknowledged' THEN 1 ELSE 0 END), 0) as acknowledged,
            COALESCE(SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END), 0) as in_progress,
            COALESCE(SUM(CASE WHEN status = 'escalated' THEN 1 ELSE 0 END), 0) as escalated,
            COALESCE(SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END), 0) as resolved,
            COALESCE(SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END), 0) as closed
        ")->first();

        return [
            'total' => (int) ($result->total ?? 0),
            'open' => (int) ($result->open ?? 0),
            'acknowledged' => (int) ($result->acknowledged ?? 0),
            'in_progress' => (int) ($result->in_progress ?? 0),
            'escalated' => (int) ($result->escalated ?? 0),
            'resolved' => (int) ($result->resolved ?? 0),
            'closed' => (int) ($result->closed ?? 0),
        ];
    }

    // ==========================================
    // CREATE & STORE
    // ==========================================
    public function create()
    {
        $this->authorize('create-incident');
        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();
        return view('incidents.create', compact('departments', 'categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create-incident');

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category_id' => 'required|exists:incident_categories,id',
            'severity' => 'required|in:low,medium,high,critical',
            'priority' => 'required|in:low,medium,high,critical',
            'department_id' => 'required|exists:departments,id',
            'location' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:500',
            'is_anonymous' => 'nullable|in:1',
            'files.*' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,wmv,flv,mp3,wav,pdf,doc,docx,xls,xlsx,txt,csv,zip',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $assignedTo = $this->autoAssignIncident($request->department_id, $request->severity);

            $incident = Incident::create([
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'severity' => $request->severity,
                'priority' => $request->priority,
                'department_id' => $request->department_id,
                'location' => $request->location,
                'reported_by' => Auth::id(),
                'assigned_to' => $assignedTo?->id,
                'is_anonymous' => $request->has('is_anonymous'),
                'tags' => $request->filled('tags') ? array_filter(array_map('trim', explode(',', $request->tags))) : null,
                'status' => $assignedTo ? 'acknowledged' : 'open',
                'acknowledged_at' => $assignedTo ? now() : null,
            ]);

            // Log auto-assignment
            if ($assignedTo) {
                $incident->assignments()->create([
                    'assigned_by' => Auth::id(),
                    'assigned_to' => $assignedTo->id,
                    'notes' => 'Auto-assigned based on department workload',
                    'assigned_at' => now(),
                    'is_active' => true,
                ]);
                $incident->logActivity('assigned', null, ['assigned_to' => $assignedTo->name]);
            }

            // Handle files
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $this->storeIncidentFile($incident, $file);
                }
            }

            // Send notifications
            $this->notificationService->notifyNewIncident($incident);

            return redirect()->route('incidents.show', $incident)
                ->with('success', 'Incident #' . $incident->incident_id . ' reported successfully!');

        } catch (\Exception $e) {
            Log::error('Incident creation failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Failed to create incident. Please try again.')->withInput();
        }
    }

    // ==========================================
    // AUTO ASSIGN
    // ==========================================
    private function autoAssignIncident(int $departmentId, string $severity): ?User
    {
        $department = Department::find($departmentId);
        if (!$department) return null;

        // For critical/high, try HOD first
        if (in_array($severity, ['critical', 'high'])) {
            $hod = $department->getHeadOfDepartment();
            if ($hod && $hod->status === 'active') return $hod;
        }

        // Get supervisor with least workload
        $supervisors = $department->getSupervisors();
        if ($supervisors->isNotEmpty()) {
            return $supervisors->filter(fn($u) => $u->status === 'active')
                ->sortBy(fn($u) => $u->assignedIncidents()->open()->count())
                ->first();
        }

        // Get any staff with least workload
        $staff = $department->users()->active()
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['staff', 'supervisor']))
            ->withCount(['assignedIncidents as open_count' => fn($q) => $q->open()])
            ->orderBy('open_count')
            ->first();

        if ($staff) return $staff;

        // Fallback to HOD
        $hod = $department->getHeadOfDepartment();
        return ($hod && $hod->status === 'active') ? $hod : null;
    }

    // ==========================================
    // FILE HANDLING
    // ==========================================
    private function storeIncidentFile(Incident $incident, $file): void
    {
        $mediaType = $this->getMediaType($file);
        $path = 'incidents/' . $incident->id . '/' . date('Y/m');
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs($path, $fileName, 'public');

        $thumbnailPath = null;
        if ($mediaType === 'image') {
            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($file);
                $image->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $thumbPath = $path . '/thumbnails/' . $fileName;
                Storage::disk('public')->put($thumbPath, $image->toJpeg(80));
                $thumbnailPath = $thumbPath;
            } catch (\Exception $e) {
                Log::warning('Thumbnail failed: ' . $e->getMessage());
            }
        }

        IncidentMedia::create([
            'incident_id' => $incident->id,
            'uploaded_by' => Auth::id(),
            'media_type' => $mediaType,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'thumbnail_path' => $thumbnailPath,
            'sort_order' => $incident->media()->count(),
        ]);
    }

    private function getMediaType($file): string
    {
        $mime = $file->getMimeType();
        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_starts_with($mime, 'audio/')) return 'audio';
        return 'document';
    }

    // ==========================================
    // SHOW
    // ==========================================
    // public function show(Request $request, Incident $incident)
    // {
    //     if (!Auth::user()->canAccessIncident($incident)) {
    //         abort(403, 'Unauthorized access to this incident.');
    //     }

    //     $incident->increment('views_count');
    //     $incident = $this->incidentRepository->getIncidentDetails($incident->id);

    //     if ($request->ajax()) {
    //         return $this->successResponse($incident);
    //     }

    //     return view('incidents.show', compact('incident'));
    // }

    // app/Http/Controllers/IncidentController.php

public function show(Request $request, Incident $incident)
{
    if (!Auth::user()->canAccessIncident($incident)) {
        abort(403, 'Unauthorized access to this incident.');
    }

    $incident->increment('views_count');

    // Eager load ALL relationships needed for the view
    $incident->load([
        'reporter',
        'department',
        'category',
        'assignedTo',
        'escalatedTo',
        'media',
        // Load comments with user and replies
        'comments' => function ($query) {
            $query->whereNull('parent_id') // Only root comments
                  ->with(['user', 'replies.user']) // Eager load user for comments and replies
                  ->latest() // Latest first
                  ->limit(50);
        },
        'comments.replies' => function ($query) {
            $query->with('user')->oldest(); // Replies in chronological order
        },
        'escalations',
        'assignments' => function ($query) {
            $query->with(['assignedBy', 'assignedTo'])->latest();
        },
    ]);

    // Load counts
    $incident->loadCount('comments');

    if ($request->ajax()) {
        return response()->json([
            'success' => true,
            'data' => [
                'comments' => $incident->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'content' => $comment->content,
                        'is_internal' => $comment->is_internal,
                        'user' => [
                            'name' => $comment->user?->name ?? 'Unknown',
                            'avatar_url' => $comment->user?->avatar_url ?? '/images/default-avatar.png',
                        ],
                        'attachments' => $comment->attachments,
                        'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                        'created_at_diff' => $comment->created_at->diffForHumans(),
                        'replies' => $comment->replies->map(function ($reply) {
                            return [
                                'id' => $reply->id,
                                'content' => $reply->content,
                                'user' => [
                                    'name' => $reply->user?->name ?? 'Unknown',
                                    'avatar_url' => $reply->user?->avatar_url ?? '/images/default-avatar.png',
                                ],
                                'attachments' => $reply->attachments,
                                'created_at' => $reply->created_at->format('Y-m-d H:i:s'),
                                'created_at_diff' => $reply->created_at->diffForHumans(),
                            ];
                        })->values()->toArray(),
                    ];
                })->values()->toArray(),
                'comments_count' => $incident->comments_count,
            ]
        ]);
    }

    return view('incidents.show', compact('incident'));
}
    // ==========================================
    // EDIT & UPDATE
    // ==========================================
    public function edit(Incident $incident)
    {
        $this->authorize('edit-incident');
        if (!Auth::user()->canAccessIncident($incident)) abort(403);

        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();
        return view('incidents.edit', compact('incident', 'departments', 'categories'));
    }

    public function update(Request $request, Incident $incident)
    {
        $this->authorize('edit-incident');
        if (!Auth::user()->canAccessIncident($incident)) abort(403);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:5000',
            'category_id' => 'sometimes|exists:incident_categories,id',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'location' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) return $this->errorResponse('Validation failed', 422, $validator->errors());
            return back()->withErrors($validator)->withInput();
        }

        try {
            $incident = $this->incidentService->updateIncident($incident, $request->all());
            if ($request->ajax()) return $this->successResponse($incident, 'Incident updated');
            return redirect()->route('incidents.show', $incident)->with('success', 'Incident updated.');
        } catch (\Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
            if ($request->ajax()) return $this->errorResponse('Update failed', 500);
            return back()->with('error', 'Update failed.')->withInput();
        }
    }

    // ==========================================
    // ASSIGN
    // ==========================================
    // public function assign(Request $request, Incident $incident)
    // {
    //     $this->authorize('assign-incident');
    //     $request->validate([
    //         'assigned_to' => 'required|exists:users,id',
    //         'notes' => 'nullable|string|max:500',
    //     ]);

    //     $user = User::findOrFail($request->assigned_to);
    //     $oldAssignee = $incident->assigned_to;

    //     // Deactivate old assignment
    //     $current = $incident->currentAssignment();
    //     if ($current) {
    //         $current->update(['is_active' => false, 'unassigned_at' => now()]);
    //     }

    //     // Create new assignment
    //     $incident->assignments()->create([
    //         'assigned_by' => Auth::id(),
    //         'assigned_to' => $user->id,
    //         'notes' => $request->notes,
    //         'assigned_at' => now(),
    //         'is_active' => true,
    //     ]);

    //     $incident->update([
    //         'assigned_to' => $user->id,
    //         'status' => $incident->status === 'open' ? 'acknowledged' : $incident->status,
    //         'acknowledged_at' => $incident->status === 'open' ? now() : $incident->acknowledged_at,
    //     ]);

    //     $incident->logActivity('assigned', ['assigned_to' => $oldAssignee], ['assigned_to' => $user->name]);
    //     $this->notificationService->notifyIncidentAssigned($incident, $user->id);

    //     if ($request->ajax()) return response()->json(['success' => true, 'message' => 'Incident assigned to ' . $user->name]);
    //     return back()->with('success', 'Incident assigned to ' . $user->name);
    // }

    // ==========================================
    // REASSIGN
    // ==========================================
    // app/Http/Controllers/IncidentController.php

// public function assign(Request $request, Incident $incident)
// {
//     $this->authorize('assign-incident');

//     $request->validate([
//         'assigned_to' => 'required|exists:users,id',
//         'notes' => 'nullable|string|max:500',
//     ]);

//     $user = User::findOrFail($request->assigned_to);
//     $oldAssigneeId = $incident->assigned_to;

//     // Deactivate current assignment if exists
//     $currentAssignment = $incident->currentAssignment();
//     if ($currentAssignment) {
//         $currentAssignment->update([
//             'is_active' => false,
//             'unassigned_at' => now(),
//         ]);
//     }

//     // Create new assignment record
//     $incident->assignments()->create([
//         'assigned_by' => Auth::id(),
//         'assigned_to' => $user->id,
//         'notes' => $request->notes ?? ($oldAssigneeId ? 'Reassigned' : 'Assigned'),
//         'assigned_at' => now(),
//         'is_active' => true,
//     ]);

//     // Update incident
//     $incident->update([
//         'assigned_to' => $user->id,
//         'status' => $incident->status === 'open' ? 'acknowledged' : $incident->status,
//         'acknowledged_at' => $incident->status === 'open' ? now() : $incident->acknowledged_at,
//     ]);

//     // Log activity
//     $action = $oldAssigneeId ? 'reassigned' : 'assigned';
//     $incident->logActivity($action,
//         ['assigned_to' => $oldAssigneeId],
//         ['assigned_to' => $user->id, 'assigned_to_name' => $user->name]
//     );

//     // Send notification
//     $this->notificationService->notifyIncidentAssigned($incident, $user->id);

//     // Always return JSON for AJAX requests
//     if ($request->ajax() || $request->wantsJson()) {
//         return response()->json([
//             'success' => true,
//             'message' => $oldAssigneeId
//                 ? "Incident reassigned to {$user->name}."
//                 : "Incident assigned to {$user->name}."
//         ]);
//     }

//     return back()->with('success', $oldAssigneeId
//         ? "Incident reassigned to {$user->name}."
//         : "Incident assigned to {$user->name}."
//     );
// }

/**
 * Reassign incident - dedicated method with proper tracking
 */
// public function reassign(Request $request, Incident $incident)
// {
//     $this->authorize('assign-incident');

//     if (!in_array($incident->status, ['open', 'acknowledged', 'in_progress', 'escalated'])) {
//         if ($request->ajax() || $request->wantsJson()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'This incident cannot be reassigned in its current status.'
//             ], 400);
//         }
//         return back()->with('error', 'This incident cannot be reassigned in its current status.');
//     }

//     $request->validate([
//         'assigned_to' => 'required|exists:users,id',
//         'notes' => 'nullable|string|max:500',
//     ]);

//     $newUser = User::findOrFail($request->assigned_to);
//     $oldAssigneeId = $incident->assigned_to;

//     // Deactivate current assignment
//     $currentAssignment = $incident->currentAssignment();
//     if ($currentAssignment) {
//         $currentAssignment->update([
//             'is_active' => false,
//             'unassigned_at' => now(),
//         ]);
//     }

//     // Create new assignment with reassignment note
//     $incident->assignments()->create([
//         'assigned_by' => Auth::id(),
//         'assigned_to' => $newUser->id,
//         'notes' => $request->notes ?? 'Reassigned from ' . (User::find($oldAssigneeId)?->name ?? 'previous assignee'),
//         'assigned_at' => now(),
//         'is_active' => true,
//     ]);

//     // Update incident
//     $incident->update([
//         'assigned_to' => $newUser->id,
//         'status' => $incident->status === 'open' ? 'acknowledged' : $incident->status,
//         'acknowledged_at' => $incident->status === 'open' ? now() : $incident->acknowledged_at,
//     ]);

//     // Log the reassignment
//     $incident->logActivity('reassigned',
//         ['assigned_to' => $oldAssigneeId, 'assigned_to_name' => User::find($oldAssigneeId)?->name],
//         ['assigned_to' => $newUser->id, 'assigned_to_name' => $newUser->name]
//     );

//     // Notify new assignee
//     $this->notificationService->notifyIncidentAssigned($incident, $newUser->id);

//     if ($request->ajax() || $request->wantsJson()) {
//         return response()->json([
//             'success' => true,
//             'message' => "Incident reassigned to {$newUser->name} successfully."
//         ]);
//     }

//     return back()->with('success', "Incident reassigned to {$newUser->name}.");
// }


    /**
     * Assign or reassign incident to a user - OPTIMIZED
     */
    public function assign(Request $request, Incident $incident)
    {
        $this->authorize('assign-incident');

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $newUser = User::findOrFail($request->assigned_to);
        $oldAssigneeId = $incident->assigned_to;

        // PREVENT REASSIGNING TO THE SAME USER
        if ($oldAssigneeId == $newUser->id) {
            $message = "This incident is already assigned to {$newUser->name}.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        // Use direct DB update to avoid model events where possible
        DB::beginTransaction();
        try {
            // Deactivate current assignment - use query builder for speed
            DB::table('incident_assignments')
                ->where('incident_id', $incident->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'unassigned_at' => now(),
                ]);

            // Create new assignment record
            DB::table('incident_assignments')->insert([
                'incident_id' => $incident->id,
                'assigned_by' => Auth::id(),
                'assigned_to' => $newUser->id,
                'notes' => $request->notes ?? ($oldAssigneeId ? 'Reassigned' : 'Assigned'),
                'assigned_at' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // DETERMINE NEW STATUS
            $newStatus = $incident->status;
            $updateData = ['assigned_to' => $newUser->id];

            if ($incident->status === 'rejected') {
                $newStatus = 'open';
                $updateData['status'] = 'open';
                $updateData['rejection_reason'] = null;
                $updateData['resolved_at'] = null;
                $updateData['closed_at'] = null;
                $updateData['resolution_notes'] = null;
                $updateData['sla_due_at'] = now()->addMinutes($incident->category?->sla_minutes ?? 120);
                $updateData['sla_breach_count'] = 0;
            } elseif ($incident->status === 'open') {
                $newStatus = 'acknowledged';
                $updateData['status'] = 'acknowledged';
                $updateData['acknowledged_at'] = $incident->acknowledged_at ?? now();
            }

            // Update incident using query builder to avoid model events
            $updateData['updated_at'] = now();
            DB::table('incidents')->where('id', $incident->id)->update($updateData);

            // Log activity - quick insert
            $action = $oldAssigneeId ? 'reassigned' : 'assigned';
            DB::table('incident_logs')->insert([
                'incident_id' => $incident->id,
                'user_id' => Auth::id(),
                'action' => $action,
                'old_values' => json_encode(['assigned_to' => $oldAssigneeId, 'status' => $incident->status]),
                'new_values' => json_encode(['assigned_to' => $newUser->id, 'status' => $newStatus]),
                'description' => $oldAssigneeId
                    ? "Reassigned to {$newUser->name}. Status: {$incident->status} → {$newStatus}"
                    : "Assigned to {$newUser->name}. Status: {$incident->status} → {$newStatus}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Send notification asynchronously (don't wait for it)
            dispatch(function () use ($incident, $newUser) {
                try {
                    $freshIncident = Incident::find($incident->id);
                    app(NotificationService::class)->notifyIncidentAssigned($freshIncident, $newUser->id);
                } catch (\Exception $e) {
                    Log::error('Assign notification failed: ' . $e->getMessage());
                }
            })->onQueue('notifications');

            // RESPONSE
            $message = $oldAssigneeId
                ? "Incident reassigned to {$newUser->name}."
                : "Incident assigned to {$newUser->name}.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'new_status' => $newStatus,
                    'assigned_to' => $newUser->name,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Assign incident failed: ' . $e->getMessage(), [
                'incident_id' => $incident->id,
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to assign incident. Please try again.'
                ], 500);
            }
            return back()->with('error', 'Failed to assign incident.');
        }
    }

    /**
     * Reassign incident - dedicated method
     */
    public function reassign(Request $request, Incident $incident)
    {
        $this->authorize('assign-incident');

        // Check if incident can be reassigned
        $allowedStatuses = ['open', 'acknowledged', 'in_progress', 'escalated', 'rejected'];
        if (!in_array($incident->status, $allowedStatuses)) {
            $message = "Cannot reassign - current status: " . ucfirst(str_replace('_', ' ', $incident->status));
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Prevent self-reassignment
        if ($incident->assigned_to == $request->assigned_to) {
            $currentAssignee = User::find($incident->assigned_to);
            $message = "Already assigned to " . ($currentAssignee?->name ?? 'this user');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        return $this->assign($request, $incident);
    }

    // ==========================================
    // ESCALATE
    // ==========================================
    // public function escalate(Request $request, Incident $incident)
    // {
    //     $this->authorize('escalate-incident');
    //     $request->validate([
    //         'escalated_to' => 'required|exists:users,id',
    //         'to_department_id' => 'required|exists:departments,id',
    //         'reason' => 'required|string|max:500',
    //     ]);

    //     $escalation = $incident->escalations()->create([
    //         'escalated_by' => Auth::id(),
    //         'escalated_to' => $request->escalated_to,
    //         'from_department_id' => $incident->department_id,
    //         'to_department_id' => $request->to_department_id,
    //         'level' => $incident->escalations()->count() + 1,
    //         'reason' => $request->reason,
    //         'status' => 'pending',
    //     ]);

    //     $incident->update([
    //         'status' => 'escalated',
    //         'escalated_to' => $request->escalated_to,
    //         'escalated_at' => now(),
    //     ]);

    //     $incident->logActivity('escalated', null, ['escalated_to' => User::find($request->escalated_to)->name]);
    //     $this->notificationService->notifyIncidentEscalated($incident, $escalation);

    //     if ($request->ajax()) return response()->json(['success' => true, 'message' => 'Incident escalated']);
    //     return back()->with('success', 'Incident escalated');
    // }

    // app/Http/Controllers/IncidentController.php

    public function escalate(Request $request, Incident $incident)
    {
        $this->authorize('escalate-incident');

        $request->validate([
            'escalated_to' => 'required|exists:users,id',
            'to_department_id' => 'required|exists:departments,id',
            'reason' => 'required|string|max:500',
        ]);

        // Quick validation - don't escalate to yourself or same department unnecessarily
        if ($request->escalated_to == Auth::id()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'You cannot escalate to yourself.'], 422);
            }
            return back()->with('error', 'You cannot escalate to yourself.');
        }

        // Use a database transaction for data integrity
        return DB::transaction(function () use ($request, $incident) {

            // Get escalation level (count existing escalations + 1)
            $nextLevel = $incident->escalations()->count() + 1;

            // Create escalation record
            $escalation = $incident->escalations()->create([
                'escalated_by' => Auth::id(),
                'escalated_to' => $request->escalated_to,
                'from_department_id' => $incident->department_id,
                'to_department_id' => $request->to_department_id,
                'level' => $nextLevel,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            // Update incident status
            $incident->update([
                'status' => 'escalated',
                'escalated_to' => $request->escalated_to,
                'escalated_at' => now(),
            ]);

            // Log activity - use query builder directly to avoid model events
            $incident->logActivity('escalated', null, [
                'escalated_to' => $request->escalated_to,
                'level' => $nextLevel
            ]);

            // Dispatch notification asynchronously to avoid timeout
            dispatch(function () use ($incident, $escalation) {
                try {
                    app(NotificationService::class)->notifyIncidentEscalated($incident, $escalation);
                } catch (\Exception $e) {
                    Log::error('Escalation notification failed: ' . $e->getMessage());
                }
            })->onQueue('notifications');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Incident escalated to level ' . $nextLevel
                ]);
            }

            return back()->with('success', 'Incident escalated successfully.');
        });
    }

    // ==========================================
    // RESOLVE
    // ==========================================
    // public function resolve(Request $request, Incident $incident)
    // {
    //     $this->authorize('resolve-incident');
    //     $request->validate(['resolution_notes' => 'required|string|max:1000']);

    //     $incident->update([
    //         'status' => 'resolved',
    //         'resolved_at' => now(),
    //         'resolution_notes' => $request->resolution_notes,
    //     ]);

    //     $incident->logActivity('resolved', null, ['resolution_notes' => $request->resolution_notes]);
    //     $this->notificationService->notifyIncidentResolved($incident);

    //     if ($request->ajax()) return response()->json(['success' => true, 'message' => 'Incident resolved']);
    //     return back()->with('success', 'Incident resolved');
    // }

    // app/Http/Controllers/IncidentController.php

    public function resolve(Request $request, Incident $incident)
    {
        $this->authorize('resolve-incident');

        $request->validate([
            'resolution_notes' => 'required|string|max:1000',
            'files.*' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,pdf,doc,docx,xls,xlsx',
        ]);

        // Update incident status
        $incident->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $request->resolution_notes,
        ]);

        // Handle file uploads for resolution
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $this->storeIncidentFile($incident, $file);
            }
        }

        // Log activity
        $incident->logActivity('resolved', null, [
            'resolution_notes' => $request->resolution_notes,
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0
        ]);

        // Send notification
        $this->notificationService->notifyIncidentResolved($incident);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Incident resolved successfully.',
                'files_uploaded' => $request->hasFile('files') ? count($request->file('files')) : 0
            ]);
        }

        return back()->with('success', 'Incident resolved successfully.');
    }

    // ==========================================
    // CLOSE
    // ==========================================
    // public function close(Request $request, Incident $incident)
    // {
    //     $this->authorize('close-incident');
    //     $incident->update(['status' => 'closed', 'closed_at' => now()]);
    //     $incident->logActivity('closed', null, null);
    //     $this->notificationService->notifyIncidentClosed($incident);

    //     if ($request->ajax()) return response()->json(['success' => true, 'message' => 'Incident closed']);
    //     return back()->with('success', 'Incident closed');
    // }

    public function close(Request $request, Incident $incident)
    {
        $this->authorize('close-incident');

        $request->validate([
            'closing_remarks' => 'required|string|max:1000',
            'files.*' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,bmp,webp,pdf,doc,docx,xls,xlsx',
        ]);

        $incident->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        // Add closing remarks as a system comment
        $incident->comments()->create([
            'user_id' => Auth::id(),
            'content' => "🔒 **Incident Closed**\n" . $request->closing_remarks,
            'is_internal' => false,
        ]);

        // Handle file uploads
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $this->storeIncidentFile($incident, $file);
            }
        }

        $incident->logActivity('closed', null, ['remarks' => $request->closing_remarks]);
        $incident->increment('comments_count');

        $this->notificationService->notifyIncidentClosed($incident);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Incident closed successfully.']);
        }

        return back()->with('success', 'Incident closed successfully.');
    }

    // ==========================================
    // REOPEN
    // ==========================================
    public function reopen(Request $request, Incident $incident)
    {
        $this->authorize('reopen-incident');

        if (!in_array($incident->status, ['resolved', 'closed'])) {
            if ($request->ajax()) return response()->json(['success' => false, 'message' => 'Only resolved/closed incidents can be reopened.'], 400);
            return back()->with('error', 'Only resolved/closed incidents can be reopened.');
        }

        $oldStatus = $incident->status;
        $incident->update([
            'status' => 'open',
            'resolved_at' => null,
            'closed_at' => null,
            'resolution_notes' => null,
            'sla_due_at' => now()->addMinutes($incident->category?->sla_minutes ?? 120),
            'sla_breach_count' => 0,
        ]);

        $incident->logActivity('reopened', ['status' => $oldStatus], ['status' => 'open']);
        $this->notificationService->notifyIncidentReopened($incident);

        if ($request->ajax()) return response()->json(['success' => true, 'message' => 'Incident reopened.']);
        return back()->with('success', 'Incident reopened.');
    }

    // ==========================================
    // REJECT
    // ==========================================
    public function reject(Request $request, Incident $incident)
    {
        $this->authorize('close-incident');
        $request->validate(['rejection_reason' => 'required|string|max:1000']);

        $oldStatus = $incident->status;
        $incident->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        $incident->logActivity('rejected', ['status' => $oldStatus], ['status' => 'rejected', 'reason' => $request->rejection_reason]);
        $this->notificationService->notifyIncidentRejected($incident);

        if ($request->ajax()) return response()->json(['success' => true, 'message' => 'Incident rejected.']);
        return back()->with('success', 'Incident rejected. Reporter notified.');
    }

    // ==========================================
    // COMMENTS
    // ==========================================
    // public function addComment(Request $request, Incident $incident)
    // {
    //     $this->authorize('add-comment');
    //     $request->validate([
    //         'content' => 'required|string|max:2000',
    //         'parent_id' => 'nullable|exists:incident_comments,id',
    //         'files.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx',
    //     ]);

    //     // Extract @mentions and #tags
    //     $mentionedIds = $this->extractMentions($request->content);
    //     $extractedTags = $this->extractTags($request->content);

    //     // Handle file attachments
    //     $attachments = [];
    //     if ($request->hasFile('files')) {
    //         foreach ($request->file('files') as $file) {
    //             $path = $file->store('comments/' . $incident->id, 'public');
    //             $attachments[] = [
    //                 'name' => $file->getClientOriginalName(),
    //                 'path' => $path,
    //                 'type' => $file->getMimeType(),
    //                 'size' => $file->getSize(),
    //             ];
    //         }
    //     }

    //     $comment = $incident->comments()->create([
    //         'user_id' => Auth::id(),
    //         'content' => $request->content,
    //         'parent_id' => $request->parent_id,
    //         'mentions' => !empty($mentionedIds) ? $mentionedIds : null,
    //         'attachments' => !empty($attachments) ? $attachments : null,
    //         'is_internal' => $request->is_internal ?? false,
    //     ]);

    //     $incident->increment('comments_count');

    //     // Merge new tags into incident
    //     if (!empty($extractedTags)) {
    //         $existingTags = $incident->tags ?? [];
    //         $incident->tags = array_unique(array_merge($existingTags, $extractedTags));
    //         $incident->save();
    //     }

    //     $incident->logActivity('comment_added', null, ['comment' => Str::limit($request->content, 100)]);

    //     // Notify mentioned users
    //     if (!empty($mentionedIds)) {
    //         foreach ($mentionedIds as $uid) {
    //             $mentionedUser = User::find($uid);
    //             if ($mentionedUser && $mentionedUser->id !== Auth::id()) {
    //                 $this->notificationService->notifyMentionedUsers($incident, $comment);
    //             }
    //         }
    //     }

    //     $this->notificationService->notifyNewComment($incident, $comment);

    //     if ($request->ajax()) {
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Comment added',
    //             'data' => [
    //                 'comment' => $comment->load('user'),
    //                 'comments_count' => $incident->comments()->count(),
    //             ]
    //         ]);
    //     }

    //     return back()->with('success', 'Comment added.');
    // }

    // In IncidentController@addComment - update file handling section

// public function addComment(Request $request, Incident $incident)
// {
//     $this->authorize('add-comment');

//     $request->validate([
//         'content' => 'nullable|string|max:2000',
//         'parent_id' => 'nullable|exists:incident_comments,id',
//         'files.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx',
//     ]);

//     // Require either content or files
//     if (!$request->filled('content') && !$request->hasFile('files')) {
//         return response()->json(['success' => false, 'message' => 'Please enter a comment or attach a file.'], 422);
//     }

//     // Extract @mentions and #tags
//     $mentionedIds = $this->extractMentions($request->content ?? '');
//     $extractedTags = $this->extractTags($request->content ?? '');

//     // Handle file attachments
//     $attachments = [];
//     if ($request->hasFile('files')) {
//         foreach ($request->file('files') as $file) {
//             $path = $file->store('comments/' . $incident->id, 'public');
//             $attachments[] = [
//                 'name' => $file->getClientOriginalName(),
//                 'path' => $path,
//                 'type' => $file->getMimeType(),
//                 'size' => $file->getSize(),
//             ];
//         }
//     }

//     $comment = $incident->comments()->create([
//         'user_id' => Auth::id(),
//         'content' => $request->content ?? '',
//         'parent_id' => $request->parent_id,
//         'mentions' => !empty($mentionedIds) ? $mentionedIds : null,
//         'attachments' => !empty($attachments) ? $attachments : null,
//         'is_internal' => $request->is_internal ?? false,
//     ]);

//     $incident->increment('comments_count');

//     // Merge tags
//     if (!empty($extractedTags)) {
//         $existingTags = $incident->tags ?? [];
//         $incident->tags = array_unique(array_merge($existingTags, $extractedTags));
//         $incident->save();
//     }

//     $incident->logActivity('comment_added', null, ['comment' => Str::limit($request->content ?? 'Attachment', 100)]);

//     // Notify mentioned users
//     if (!empty($mentionedIds)) {
//         foreach ($mentionedIds as $uid) {
//             $mentionedUser = User::find($uid);
//             if ($mentionedUser && $mentionedUser->id !== Auth::id()) {
//                 $this->notificationService->notifyMentionedUsers($incident, $comment);
//             }
//         }
//     }

//     $this->notificationService->notifyNewComment($incident, $comment);

//     // Load relationships for response
//     $comment->load('user');
//     $comment->loadCount('replies');

//     // Format response
//     $responseComment = [
//         'id' => $comment->id,
//         'content' => $comment->content,
//         'user' => [
//             'name' => $comment->user?->name ?? 'Unknown',
//             'avatar_url' => $comment->user?->avatar_url ?? '/images/default-avatar.png',
//         ],
//         'attachments' => $comment->attachments,
//         'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
//         'created_at_diff' => $comment->created_at->diffForHumans(),
//         'replies' => [],
//         'replies_count' => 0,
//     ];

//     if ($request->ajax()) {
//         return response()->json([
//             'success' => true,
//             'message' => 'Comment added',
//             'data' => [
//                 'comment' => $responseComment,
//                 'comments_count' => $incident->comments()->count(),
//             ]
//         ]);
//     }

//     return back()->with('success', 'Comment added.');
// }

// app/Http/Controllers/IncidentController.php

public function addComment(Request $request, Incident $incident)
{
    $this->authorize('add-comment');

    // Validate - return JSON error if AJAX
    $validator = Validator::make($request->all(), [
        'content' => 'nullable|string|max:2000',
        'parent_id' => 'nullable|exists:incident_comments,id',
        'files.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,bmp,webp,pdf,doc,docx,xls,xlsx',
    ]);

    if ($validator->fails()) {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }
        return back()->withErrors($validator)->withInput();
    }

    // Require either content or files
    if (!$request->filled('content') && !$request->hasFile('files')) {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a comment or attach a file.'
            ], 422);
        }
        return back()->with('error', 'Please enter a comment or attach a file.');
    }

    try {
        // Extract @mentions and #tags
        $mentionedIds = $this->extractMentions($request->content ?? '');
        $extractedTags = $this->extractTags($request->content ?? '');

        // Handle file attachments
        $attachments = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('comments/' . $incident->id, 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        $comment = $incident->comments()->create([
            'user_id' => Auth::id(),
            'content' => $request->content ?? '',
            'parent_id' => $request->parent_id,
            'mentions' => !empty($mentionedIds) ? $mentionedIds : null,
            'attachments' => !empty($attachments) ? $attachments : null,
            'is_internal' => $request->is_internal ?? false,
        ]);

        $incident->increment('comments_count');

        // Merge tags
        if (!empty($extractedTags)) {
            $existingTags = $incident->tags ?? [];
            $incident->tags = array_unique(array_merge($existingTags, $extractedTags));
            $incident->save();
        }

        $incident->logActivity('comment_added', null, ['comment' => Str::limit($request->content ?? 'Attachment', 100)]);

        // Notify mentioned users
        if (!empty($mentionedIds)) {
            foreach ($mentionedIds as $uid) {
                $mentionedUser = User::find($uid);
                if ($mentionedUser && $mentionedUser->id !== Auth::id()) {
                    $this->notificationService->notifyMentionedUsers($incident, $comment);
                }
            }
        }

        // Notify other participants
        $this->notificationService->notifyNewComment($incident, $comment);

        // Build response
        $comment->load('user');

        $responseComment = [
            'id' => $comment->id,
            'content' => $comment->content,
            'is_internal' => $comment->is_internal,
            'user' => [
                'name' => $comment->user?->name ?? 'Unknown',
                'avatar_url' => $comment->user?->avatar_url ?? '/images/default-avatar.png',
            ],
            'attachments' => $comment->attachments,
            'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
            'created_at_diff' => $comment->created_at->diffForHumans(),
            'replies' => [],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => [
                'comment' => $responseComment,
                'comments_count' => $incident->comments()->count(),
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Comment creation failed: ' . $e->getMessage(), [
            'incident_id' => $incident->id,
            'user_id' => Auth::id(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to add comment. Please try again.'
        ], 500);
    }
}




/**
 * Edit a comment - only comment author or admin can edit
 * Time limit: 30 minutes after posting (except for admin)
 */
public function editComment(Request $request, Incident $incident, IncidentComment $comment)
{
    // Check ownership - only comment author or admin can edit
    if ($comment->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own comments.'
            ], 403);
        }
        return back()->with('error', 'You can only edit your own comments.');
    }

    // Time limit: 30 minutes (admins bypass this)
    if ($comment->created_at->diffInMinutes(now()) > 30 && !Auth::user()->isAdmin()) {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Comments can only be edited within 30 minutes of posting.'
            ], 403);
        }
        return back()->with('error', 'Comments can only be edited within 30 minutes.');
    }

    // Validate content
    $request->validate([
        'content' => 'required|string|max:2000',
    ]);

    // Update comment
    $comment->update([
        'content' => $request->content,
        'is_edited' => true,
        'edited_at' => now(),
    ]);

    // Log activity
    $incident->logActivity('comment_edited', null, [
        'comment_id' => $comment->id,
        'content_preview' => Str::limit($request->content, 100)
    ]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully.',
            'data' => [
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'is_edited' => $comment->is_edited,
                    'edited_at' => $comment->edited_at?->format('Y-m-d H:i:s'),
                    'edited_at_diff' => $comment->edited_at?->diffForHumans(),
                ]
            ]
        ]);
    }

    return back()->with('success', 'Comment updated successfully.');
}

/**
 * Delete a comment - only comment author or admin
 */
public function deleteComment(Request $request, Incident $incident, IncidentComment $comment)
{
    // Check ownership - only comment author or admin can delete
    if ($comment->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own comments.'
            ], 403);
        }
        return back()->with('error', 'You can only delete your own comments.');
    }

    // Store comment ID before deleting for logging
    $commentId = $comment->id;

    // Delete the comment
    $comment->delete();

    // Update incident comment count
    $totalComments = $incident->comments()->count();
    $incident->update(['comments_count' => $totalComments]);

    // Log activity
    $incident->logActivity('comment_deleted', null, ['comment_id' => $commentId]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
            'data' => [
                'comments_count' => $totalComments,
            ]
        ]);
    }

    return back()->with('success', 'Comment deleted successfully.');
}
    private function extractMentions(string $content): array
    {
        preg_match_all('/@(\w+)/', $content, $matches);
        if (empty($matches[1])) return [];
        return User::whereIn('username', $matches[1])->orWhereIn('name', $matches[1])->pluck('id')->toArray();
    }

    private function extractTags(string $content): array
    {
        preg_match_all('/#(\w+)/', $content, $matches);
        return $matches[1] ?? [];
    }

    // ==========================================
    // MEDIA
    // ==========================================
    public function uploadMedia(Request $request, Incident $incident)
    {
        $this->authorize('upload-media');
        $request->validate(['files.*' => 'required|file|max:20480|mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,pdf,doc,docx,xls,xlsx']);

        $mediaRecords = [];
        foreach ($request->file('files', []) as $file) {
            $mediaRecords[] = $this->storeIncidentFile($incident, $file);
        }

        if ($request->ajax()) return response()->json(['success' => true, 'message' => count($mediaRecords) . ' file(s) uploaded.']);
        return back()->with('success', 'Files uploaded.');
    }

    public function deleteMedia(Request $request, Incident $incident, $mediaId)
    {
        $this->authorize('delete-media');
        $media = $incident->media()->findOrFail($mediaId);
        $this->incidentService->deleteMedia($media);
        if ($request->ajax()) return response()->json(['success' => true, 'message' => 'Media deleted']);
        return back()->with('success', 'Media deleted.');
    }

    // ==========================================
    // SHARE
    // ==========================================
    public function shareData(Incident $incident)
    {
        return response()->json([
            'success' => true,
            'data' => $incident->getShareData(),
            'whatsapp_url' => $incident->getWhatsAppShareUrl(),
        ]);
    }

    // ==========================================
    // DESTROY
    // ==========================================
    public function destroy(Incident $incident)
    {
        $this->authorize('delete-incident');
        $incident->delete();
        if (request()->ajax()) return $this->successResponse(null, 'Incident deleted');
        return redirect()->route('incidents.index')->with('success', 'Incident deleted.');
    }
}
