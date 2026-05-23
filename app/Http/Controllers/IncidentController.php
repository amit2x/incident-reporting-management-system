<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentMedia;
use App\Models\User;
use App\Notifications\IncidentAssignedNotification;
use App\Notifications\NewIncidentNotification;
use App\Repositories\IncidentRepository;
use App\Services\IncidentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class IncidentController extends Controller
{
    protected IncidentService $incidentService;

    protected IncidentRepository $incidentRepository;

    public function __construct(
        IncidentService $incidentService,
        IncidentRepository $incidentRepository
    ) {
        $this->incidentService = $incidentService;
        $this->incidentRepository = $incidentRepository;
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'department_id', 'category_id', 'severity', 'priority',
            'status', 'assigned_to', 'date_from', 'date_to', 'search',
        ]);

        // Apply role-based filters
        if (! Auth::user()->isAdmin()) {
            $filters['department_id'] = Auth::user()->department_id;
        }

        $incidents = $this->incidentRepository->getFeedIncidents($filters, 15);

        if ($request->ajax()) {
            return $this->paginatedResponse($incidents);
        }

        return view('incidents.index', compact('incidents', 'filters'));
    }

    public function show(Request $request, Incident $incident)
    {
        // Check access
        if (! Auth::user()->canAccessIncident($incident)) {
            abort(403, 'Unauthorized access to this incident.');
        }

        // Increment views
        $incident->increment('views_count');

        // Load full incident details
        $incident = $this->incidentRepository->getIncidentDetails($incident->id);

        if ($request->ajax()) {
            return $this->successResponse($incident);
        }

        return view('incidents.show', compact('incident'));
    }

    public function create()
    {
        $this->authorize('create-incident');

        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();

        return view('incidents.create', compact('departments', 'categories'));
    }

    //  public function store(Request $request)
    // {
    //     $this->authorize('create-incident');

    //     $validator = Validator::make($request->all(), [
    //         'title' => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'category_id' => 'required|exists:incident_categories,id',
    //         'severity' => 'required|in:low,medium,high,critical',
    //         'priority' => 'required|in:low,medium,high,critical',
    //         'department_id' => 'required|exists:departments,id',
    //         'location' => 'nullable|string|max:255',
    //         'latitude' => 'nullable|numeric',
    //         'longitude' => 'nullable|numeric',
    //         'tags' => 'nullable|string|max:500',
    //         'is_anonymous' => 'nullable|boolean',
    //         'files.*' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,wmv,flv,webm,mp3,wav,pdf,doc,docx,xls,xlsx,txt,csv,zip',
    //     ]);

    //     if ($validator->fails()) {
    //         if ($request->ajax() || $request->wantsJson()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Validation failed',
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }
    //         return back()->withErrors($validator)->withInput();
    //     }

    //     try {
    //         // Prepare only the data that belongs to the Incident model
    //         $incidentData = $request->only([
    //             'title',
    //             'description',
    //             'category_id',
    //             'severity',
    //             'priority',
    //             'department_id',
    //             'location',
    //             'latitude',
    //             'longitude',
    //             'is_anonymous',
    //         ]);

    //         // Handle tags - convert comma-separated string to array
    //         if ($request->filled('tags')) {
    //             $tags = explode(',', $request->tags);
    //             $incidentData['tags'] = array_map('trim', $tags);
    //         }

    //         // Get files
    //         $files = $request->file('files', []);

    //         // Create incident using service
    //         $incident = $this->incidentService->createIncident($incidentData, $files);

    //         if ($request->ajax() || $request->wantsJson()) {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Incident reported successfully',
    //                 'data' => [
    //                     'incident' => $incident,
    //                     'redirect' => route('incidents.show', $incident),
    //                 ]
    //             ], 201);
    //         }

    //         return redirect()->route('incidents.show', $incident)
    //             ->with('success', 'Incident reported successfully!');

    //     } catch (\Exception $e) {
    //         \Log::error('Incident creation failed: ' . $e->getMessage(), [
    //             'user_id' => Auth::id(),
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //         ]);

    //         if ($request->ajax() || $request->wantsJson()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Failed to create incident. Please try again.',
    //             ], 500);
    //         }

    //         return back()
    //             ->with('error', 'Failed to create incident. Please try again.')
    //             ->withInput();
    //     }
    // }

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
            // Auto-assign logic: Find appropriate assignee
            $assignedTo = $this->autoAssignIncident($request->department_id, $request->severity);

            // Create incident
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

            // Log assignment if auto-assigned
            if ($assignedTo) {
                $incident->assignments()->create([
                    'assigned_by' => Auth::id(),
                    'assigned_to' => $assignedTo->id,
                    'notes' => 'Auto-assigned based on department and severity',
                    'assigned_at' => now(),
                    'is_active' => true,
                ]);

                $incident->logActivity('assigned', null, ['assigned_to' => $assignedTo->name]);
            }

            // Handle file uploads
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $this->storeIncidentFile($incident, $file);
                }
            }

            // Send notifications
            $this->sendNewIncidentNotifications($incident);

            return redirect()->route('incidents.show', $incident)
                ->with('success', 'Incident #'.$incident->incident_id.' reported successfully!');

        } catch (\Exception $e) {
            \Log::error('Incident creation failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Failed to create incident. Please try again.')
                ->withInput();
        }
    }

    /**
     * Auto-assign incident based on department and severity
     */
    private function autoAssignIncident($departmentId, $severity): ?User
    {
        // Priority order for assignment:
        // 1. Department supervisor with least workload
        // 2. Any department staff with least workload
        // 3. Department HOD

        $department = Department::find($departmentId);
        if (! $department) {
            return null;
        }

        // Get supervisors first
        $supervisors = $department->getSupervisors();
        if ($supervisors->isNotEmpty()) {
            // Assign to supervisor with least open incidents
            return $supervisors->sortBy(function ($user) {
                return $user->assignedIncidents()->open()->count();
            })->first();
        }

        // Get active staff in department
        $staff = $department->users()->active()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['staff', 'supervisor']);
            })
            ->get();

        if ($staff->isNotEmpty()) {
            return $staff->sortBy(function ($user) {
                return $user->assignedIncidents()->open()->count();
            })->first();
        }

        // Fallback to HOD
        return $department->getHeadOfDepartment();
    }

    /**
     * Store uploaded file for incident
     */
    private function storeIncidentFile($incident, $file): void
    {
        $mediaType = $this->getMediaType($file);
        $path = 'incidents/'.$incident->id.'/'.date('Y/m');
        $fileName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

        $filePath = $file->storeAs($path, $fileName, 'public');

        $thumbnailPath = null;
        if ($mediaType === 'image') {
            try {
                $manager = new ImageManager(new Driver);
                $image = $manager->read($file);
                $image->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $thumbPath = $path.'/thumbnails/'.$fileName;
                Storage::disk('public')->put($thumbPath, $image->toJpeg(80));
                $thumbnailPath = $thumbPath;
            } catch (\Exception $e) {
                Log::warning('Thumbnail generation failed: '.$e->getMessage());
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
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    private function sendNewIncidentNotifications($incident): void
    {
        try {
            $hod = $incident->department->getHeadOfDepartment();
            if ($hod) {
                $hod->notify(new NewIncidentNotification($incident));
            }

            if ($incident->assignedTo) {
                $incident->assignedTo->notify(new IncidentAssignedNotification($incident));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send incident notification: '.$e->getMessage());
        }
    }

    public function edit(Incident $incident)
    {
        $this->authorize('edit-incident');

        if (! Auth::user()->canAccessIncident($incident)) {
            abort(403);
        }

        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();

        return view('incidents.edit', compact('incident', 'departments', 'categories'));
    }

    public function update(Request $request, Incident $incident)
    {
        $this->authorize('edit-incident');

        if (! Auth::user()->canAccessIncident($incident)) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:incident_categories,id',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'location' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            return back()->withErrors($validator)->withInput();
        }

        try {
            $incident = $this->incidentService->updateIncident($incident, $request->all());

            if ($request->ajax()) {
                return $this->successResponse($incident, 'Incident updated successfully');
            }

            return redirect()->route('incidents.show', $incident)
                ->with('success', 'Incident updated successfully');
        } catch (\Exception $e) {
            \Log::error('Incident update failed: '.$e->getMessage());

            if ($request->ajax()) {
                return $this->errorResponse('Failed to update incident', 500);
            }

            return back()->with('error', 'Failed to update incident.')->withInput();
        }
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


    // public function reopen(Request $request, Incident $incident)
    // {
    //     $this->authorize('reopen-incident');

    //     try {
    //         $this->incidentService->reopenIncident($incident);

    //         return $this->successResponse(null, 'Incident reopened successfully');
    //     } catch (\Exception $e) {
    //         return $this->errorResponse('Reopen failed: '.$e->getMessage(), 500);
    //     }
    // }

    // In app/Http/Controllers/IncidentController.php

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

        // Load the user relationship for the response
        $comment->load('user');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => [
                    // Return the single new comment
                    'comment' => [
                        'id' => $comment->id,
                        'content' => $comment->content,
                        'user' => [
                            'name' => $comment->user?->name ?? 'Unknown',
                            'avatar_url' => $comment->user?->avatar_url ?? '/images/default-avatar.png',
                        ],
                        'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                        'created_at_diff' => $comment->created_at->diffForHumans(),
                    ],
                    'comments_count' => $incident->comments()->count(),
                ],
            ]);
        }

        return back()->with('success', 'Comment added successfully');
    }

    public function uploadMedia(Request $request, Incident $incident)
    {
        $this->authorize('upload-media');

        $validator = Validator::make($request->all(), [
            'files.*' => 'required|file|max:20480|mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,wmv,flv,webm,mp3,wav,pdf,doc,docx,xls,xlsx,txt,csv,zip',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $media = $this->incidentService->uploadMedia($incident, $request->file('files', []));

            return $this->successResponse($media, 'Media uploaded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Media upload failed: '.$e->getMessage(), 500);
        }
    }

    public function deleteMedia(Request $request, Incident $incident, $mediaId)
    {
        $this->authorize('delete-media');

        $media = $incident->media()->findOrFail($mediaId);

        try {
            $this->incidentService->deleteMedia($media);

            return $this->successResponse(null, 'Media deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete media: '.$e->getMessage(), 500);
        }
    }

    public function destroy(Incident $incident)
    {
        $this->authorize('delete-incident');

        try {
            $incident->delete();

            if (request()->ajax()) {
                return $this->successResponse(null, 'Incident deleted successfully');
            }

            return redirect()->route('incidents.index')
                ->with('success', 'Incident deleted successfully');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return $this->errorResponse('Failed to delete incident', 500);
            }

            return back()->with('error', 'Failed to delete incident.');
        }
    }
}
