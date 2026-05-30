<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Escalation;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentComment;
use App\Models\IncidentMedia;
use App\Models\User;
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
    // public function index(Request $request)
    // {
    //     $filters = $request->only([
    //         'department_id', 'category_id', 'severity', 'priority',
    //         'status', 'assigned_to', 'date_from', 'date_to', 'search',
    //     ]);

    //     $user = Auth::user();

    //     // Apply role-based filters
    //     if (! $user->isAdmin()) {
    //         $filters['department_id'] = $user->department_id;
    //     }

    //     // Get stats
    //     $stats = $this->getQuickStats($filters);

    //     // Get incidents
    //     $incidents = $this->incidentRepository->getFeedIncidents($filters, 15);

    //     if ($request->ajax()) {
    //         return $this->paginatedResponse($incidents);
    //     }

    //     return view('incidents.index', compact('incidents', 'filters', 'stats'));
    // }

    // last-version
    // public function index(Request $request)
    // {
    //     $filters = $request->only([
    //         'department_id', 'category_id', 'severity', 'priority',
    //         'status', 'assigned_to', 'date_from', 'date_to', 'search',
    //     ]);

    //     $user = Auth::user();
    //     $tab = $request->input('tab', 'all');

    //     // Apply role-based filters for non-admin
    //     if (! $user->isAdmin()) {
    //         $filters['department_id'] = $user->department_id;
    //     }

    //     // Get stats (always for all incidents in user's scope)
    //     $stats = $this->getQuickStats($filters);

    //     // Get incidents based on tab
    //     switch ($tab) {
    //         case 'escalated':
    //             // Show incidents escalated to current user
    //             $incidents = Incident::where('escalated_to', $user->id)
    //                 ->where('status', 'escalated')
    //                 ->with(['department', 'category', 'reporter', 'assignedTo'])
    //                 ->latest('escalated_at')
    //                 ->paginate(15);
    //             break;

    //         case 'assigned':
    //             // Show incidents assigned to current user
    //             $incidents = Incident::where('assigned_to', $user->id)
    //                 ->whereIn('status', ['open', 'acknowledged', 'in_progress'])
    //                 ->with(['department', 'category', 'reporter'])
    //                 ->latest()
    //                 ->paginate(15);
    //             break;

    //         default:
    //             // Show all incidents (filtered)
    //             $incidents = $this->incidentRepository->getFeedIncidents($filters, 15);
    //             break;
    //     }

    //     if ($request->ajax()) {
    //         return $this->paginatedResponse($incidents);
    //     }

    //     return view('incidents.index', compact('incidents', 'filters', 'stats', 'tab'));
    // }

    // app/Http/Controllers/IncidentController.php

    public function index(Request $request)
    {
        $filters = $request->only([
            'department_id', 'category_id', 'severity', 'priority',
            'status', 'assigned_to', 'date_from', 'date_to', 'search',
        ]);

        $user = Auth::user();
        $tab = $request->get('tab', 'all');

        // Apply role-based filters for non-admin
        if (! $user->isAdmin()) {
            $filters['department_id'] = $user->department_id;
        }

        // Get stats (always for all incidents in user's scope)
        $stats = $this->getQuickStats($filters);

        // Get incidents based on tab
        switch ($tab) {
            case 'escalated':
                $incidents = Incident::where('escalated_to', $user->id)
                    ->where('status', 'escalated')
                    ->with(['department', 'category', 'reporter', 'assignedTo'])
                    ->latest('escalated_at')
                    ->paginate(15);
                break;

            case 'assigned':
                $incidents = Incident::where('assigned_to', $user->id)
                    ->whereIn('status', ['open', 'acknowledged', 'in_progress'])
                    ->with(['department', 'category', 'reporter'])
                    ->latest()
                    ->paginate(15);
                break;

            case 'history':
                // Show ALL incidents where user had any involvement
                $incidents = Incident::where(function ($query) use ($user) {
                    $query->where('reported_by', $user->id)
                        ->orWhere('assigned_to', $user->id)
                        ->orWhere('escalated_to', $user->id)
                        ->orWhereHas('escalations', function ($q) use ($user) {
                            $q->where('escalated_to', $user->id)
                                ->orWhere('escalated_by', $user->id);
                        })
                        ->orWhereHas('assignments', function ($q) use ($user) {
                            $q->where('assigned_to', $user->id)
                                ->orWhere('assigned_by', $user->id);
                        })
                        ->orWhereHas('comments', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                })
                    ->with(['department', 'category', 'assignedTo', 'reporter', 'escalatedTo'])
                    ->withCount('comments')
                    ->latest()
                    ->paginate(15);
                break;

            case 'reported':
                $incidents = Incident::where('reported_by', $user->id)
                    ->with(['department', 'category', 'assignedTo'])
                    ->latest()
                    ->paginate(15);
                break;

            default:
                $incidents = $this->incidentRepository->getFeedIncidents($filters, 15);
                break;
        }

        if ($request->ajax()) {
            return $this->paginatedResponse($incidents);
        }

        return view('incidents.index', compact('incidents', 'filters', 'stats', 'tab'));
    }

    // ==========================================
    // QUICK STATS HELPER
    // ==========================================
    private function getQuickStats(array $filters): array
    {
        $query = DB::table('incidents')
            ->whereNull('deleted_at');

        if (! empty($filters['department_id'])) {
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
                ->with('success', 'Incident #'.$incident->incident_id.' reported successfully!');

        } catch (\Exception $e) {
            Log::error('Incident creation failed: '.$e->getMessage(), [
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
        if (! $department) {
            return null;
        }

        // For critical/high, try HOD first
        if (in_array($severity, ['critical', 'high'])) {
            $hod = $department->getHeadOfDepartment();
            if ($hod && $hod->status === 'active') {
                return $hod;
            }
        }

        // Get supervisor with least workload
        $supervisors = $department->getSupervisors();
        if ($supervisors->isNotEmpty()) {
            return $supervisors->filter(fn ($u) => $u->status === 'active')
                ->sortBy(fn ($u) => $u->assignedIncidents()->open()->count())
                ->first();
        }

        // Get any staff with least workload
        $staff = $department->users()->active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['staff', 'supervisor']))
            ->withCount(['assignedIncidents as open_count' => fn ($q) => $q->open()])
            ->orderBy('open_count')
            ->first();

        if ($staff) {
            return $staff;
        }

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
                Log::warning('Thumbnail failed: '.$e->getMessage());
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
        if (! Auth::user()->canAccessIncident($incident)) {
            abort(403, 'Unauthorized access to this incident.');
        }

        // Use the broader view check instead of canAccessIncident
        if (! $incident->canBeViewedBy(Auth::user())) {
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
                ],
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
        if (! Auth::user()->canAccessIncident($incident)) {
            abort(403);
        }

        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();

        return view('incidents.edit', compact('incident', 'departments', 'categories'));
    }

    // app/Http/Controllers/IncidentController.php

    /**
     * Update the specified incident in storage.
     */
    // app/Http/Controllers/IncidentController.php

    /**
     * Update the specified incident in storage.
     */
    public function update(Request $request, Incident $incident)
    {
        $this->authorize('edit-incident');

        if (! Auth::user()->canAccessIncident($incident)) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:5000',
            'category_id' => 'sometimes|exists:incident_categories,id',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'location' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:500',
            'files.*' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,pdf,doc,docx,xls,xlsx',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Update basic incident data
            $updateData = ['updated_at' => now()];

            if ($request->has('title')) {
                $updateData['title'] = $request->title;
            }
            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }
            if ($request->has('category_id')) {
                $updateData['category_id'] = $request->category_id;
            }
            if ($request->has('severity')) {
                $updateData['severity'] = $request->severity;
            }
            if ($request->has('priority')) {
                $updateData['priority'] = $request->priority;
            }
            if ($request->has('location')) {
                $updateData['location'] = $request->location;
            }

            // Handle tags
            if ($request->filled('tags')) {
                $tags = array_filter(array_map('trim', explode(',', $request->tags)));
                $updateData['tags'] = json_encode(array_values($tags));
            }

            DB::table('incidents')->where('id', $incident->id)->update($updateData);

            // Handle file uploads - FIXED: Get sort_order separately
            if ($request->hasFile('files')) {
                // Get current max sort_order for this incident (separate query, not subquery)
                $maxSortOrder = DB::table('incident_media')
                    ->where('incident_id', $incident->id)
                    ->max('sort_order') ?? 0;

                foreach ($request->file('files') as $index => $file) {
                    $mediaType = $this->getMediaType($file);
                    $path = 'incidents/'.$incident->id.'/'.date('Y/m');
                    $fileName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                    $filePath = $file->storeAs($path, $fileName, 'public');

                    // Generate thumbnail for images
                    $thumbnailPath = null;
                    if ($mediaType === 'image' && $file->getSize() < 10485760) {
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
                            Log::warning('Thumbnail failed: '.$e->getMessage());
                        }
                    }

                    // Insert with incremented sort order (no subquery on same table)
                    DB::table('incident_media')->insert([
                        'incident_id' => $incident->id,
                        'uploaded_by' => Auth::id(),
                        'media_type' => $mediaType,
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'thumbnail_path' => $thumbnailPath,
                        'sort_order' => $maxSortOrder + $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Log activity if changes were made
            $hasChanges = false;
            foreach ($updateData as $key => $value) {
                if ($key !== 'updated_at' && $value != $incident->$key) {
                    $hasChanges = true;
                    break;
                }
            }

            if ($hasChanges || $request->hasFile('files')) {
                DB::table('incident_logs')->insert([
                    'incident_id' => $incident->id,
                    'user_id' => Auth::id(),
                    'action' => 'updated',
                    'old_values' => json_encode(['title' => $incident->title, 'description' => Str::limit($incident->description, 100)]),
                    'new_values' => json_encode($request->hasFile('files') ? ['files_added' => count($request->file('files'))] : $updateData),
                    'description' => 'Incident details updated'.($request->hasFile('files') ? ' with '.count($request->file('files')).' new attachment(s)' : ''),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            // Reload incident with relationships
            $incident->refresh();
            $incident->load(['department', 'category', 'media', 'reporter', 'assignedTo']);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Incident updated successfully.',
                    'files_added' => $request->hasFile('files') ? count($request->file('files')) : 0,
                ]);
            }

            return redirect()->route('incidents.show', $incident)
                ->with('success', 'Incident updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update failed: '.$e->getMessage(), [
                'incident_id' => $incident->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update incident. Please try again.',
                ], 500);
            }

            return back()->with('error', 'Update failed. Please try again.')->withInput();
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
                    Log::error('Assign notification failed: '.$e->getMessage());
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
            Log::error('Assign incident failed: '.$e->getMessage(), [
                'incident_id' => $incident->id,
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to assign incident. Please try again.',
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
        if (! in_array($incident->status, $allowedStatuses)) {
            $message = 'Cannot reassign - current status: '.ucfirst(str_replace('_', ' ', $incident->status));
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
            $message = 'Already assigned to '.($currentAssignee?->name ?? 'this user');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        return $this->assign($request, $incident);
    }

    // // ==========================================
    // // ESCALATE
    // // ==========================================
    // working one
    // public function escalate(Request $request, Incident $incident)
    // {
    //     $this->authorize('escalate-incident');
    //     $request->validate([
    //         'escalated_to' => 'required|exists:users,id',
    //         'to_department_id' => 'required|exists:departments,id',
    //         'reason' => 'required|string|max:500',
    //     ]);

    //     // Quick validation - don't escalate to yourself or already escalated user unnecessarily

    //     if ($request->escalated_to == Auth::id()) {
    //             if ($request->ajax()) {
    //                     return response()->json(['success' => false, 'message' => 'You cannot escalate to yourself.'], 422);
    //                 }
    //         return back()->with('error', 'You cannot escalate to yourself.');
    //     }

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

    /**
     * Escalate incident - OPTIMIZED with duplicate prevention
     */
    public function escalate(Request $request, Incident $incident)
    {
        $this->authorize('escalate-incident');

        $request->validate([
            'escalated_to' => 'required|exists:users,id',
            'to_department_id' => 'required|exists:departments,id',
            'reason' => 'required|string|max:500',
        ]);

        $escalatedToId = $request->escalated_to;
        $toDepartmentId = $request->to_department_id;
        $reason = $request->reason;

        // ==========================================
        // VALIDATION 1: Cannot escalate to yourself
        // ==========================================
        if ($escalatedToId == Auth::id()) {
            $message = 'You cannot escalate the incident to yourself.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // ==========================================
        // VALIDATION 2: Cannot escalate to currently assigned user
        // ==========================================
        if ($incident->assigned_to == $escalatedToId) {
            $assignedUser = User::find($incident->assigned_to);
            $message = 'This incident is already assigned to '.($assignedUser?->name ?? 'this user').'. Please escalate to a different person.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // ==========================================
        // VALIDATION 3: Cannot escalate if already escalated to same user
        // ==========================================
        $alreadyEscalatedToUser = $incident->escalations()
            ->where('escalated_to', $escalatedToId)
            ->where('status', '!=', 'rejected') // Only check non-rejected escalations
            ->exists();

        if ($alreadyEscalatedToUser) {
            $escalatedUser = User::find($escalatedToId);
            $message = 'This incident has already been escalated to '.($escalatedUser?->name ?? 'this user').'. Please select a different person or wait for their response.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // ==========================================
        // VALIDATION 4: Cannot escalate if already in escalated status to same department
        // (Prevents circular escalation)
        // ==========================================
        if ($incident->status === 'escalated' && $incident->escalated_to == $escalatedToId) {
            $message = 'This incident is already escalated to this user.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // ==========================================
        // VALIDATION 5: Cannot escalate to same department without higher authority
        // ==========================================
        if ($toDepartmentId == $incident->department_id && $incident->status === 'escalated') {
            $message = 'This incident is already in the same department. Please escalate to a different department for higher authority.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // ==========================================
        // PROCESS ESCALATION
        // ==========================================
        DB::beginTransaction();
        try {
            // Get next escalation level
            $nextLevel = $incident->escalations()->count() + 1;

            // Create escalation record using direct insert (faster)
            DB::table('escalations')->insert([
                'incident_id' => $incident->id,
                'escalated_by' => Auth::id(),
                'escalated_to' => $escalatedToId,
                'from_department_id' => $incident->department_id,
                'to_department_id' => $toDepartmentId,
                'level' => $nextLevel,
                'reason' => $reason,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get the escalation ID for notification
            $escalationId = DB::getPdo()->lastInsertId();

            // Update incident status
            DB::table('incidents')
                ->where('id', $incident->id)
                ->update([
                    'status' => 'escalated',
                    'escalated_to' => $escalatedToId,
                    'escalated_at' => now(),
                    'updated_at' => now(),
                ]);

            // Log activity
            $escalatedToUser = User::find($escalatedToId);
            $toDepartment = Department::find($toDepartmentId);

            DB::table('incident_logs')->insert([
                'incident_id' => $incident->id,
                'user_id' => Auth::id(),
                'action' => 'escalated',
                'new_values' => json_encode([
                    'escalated_to' => $escalatedToId,
                    'escalated_to_name' => $escalatedToUser?->name,
                    'to_department' => $toDepartment?->name,
                    'level' => $nextLevel,
                    'reason' => $reason,
                ]),
                'description' => "Escalated to Level {$nextLevel}: ".($escalatedToUser?->name ?? 'N/A').' ('.($toDepartment?->name ?? 'N/A').')',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Send notification asynchronously
            dispatch(function () use ($incident, $escalationId) {
                try {
                    $freshIncident = Incident::find($incident->id);
                    $escalation = Escalation::find($escalationId);
                    if ($escalation) {
                        app(NotificationService::class)->notifyIncidentEscalated($freshIncident, $escalation);
                    }
                } catch (\Exception $e) {
                    Log::error('Escalation notification failed: '.$e->getMessage());
                }
            })->onQueue('notifications');

            // Response
            $message = "Incident escalated to Level {$nextLevel}: ".($escalatedToUser?->name ?? 'User').' ('.($toDepartment?->name ?? 'Dept').')';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'level' => $nextLevel,
                    'escalated_to' => $escalatedToUser?->name,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Escalation failed: '.$e->getMessage(), [
                'incident_id' => $incident->id,
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to escalate incident. Please try again.',
                ], 500);
            }

            return back()->with('error', 'Failed to escalate incident.');
        }
    }

    /**
     * Respond to escalation (accept/reject/return) - OPTIMIZED
     */
    public function respondToEscalation(Request $request, Incident $incident)
    {
        $user = Auth::user();

        // Check if user is the escalated person
        if ($incident->escalated_to !== $user->id || $incident->status !== 'escalated') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to respond to this escalation.',
                ], 403);
            }

            return back()->with('error', 'You are not authorized to respond to this escalation.');
        }

        $request->validate([
            'response_type' => 'required|in:accept,reject,return',
            'response_note' => 'nullable|string|max:1000',
        ]);

        // Get the latest pending escalation - use query builder for speed
        $escalation = DB::table('escalations')
            ->where('incident_id', $incident->id)
            ->where('escalated_to', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $escalation) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No pending escalation found.'], 404);
            }

            return back()->with('error', 'No pending escalation found.');
        }

        $responseType = $request->response_type;
        $responseNote = $request->response_note;

        DB::beginTransaction();
        try {

            // Update escalation record - direct DB update
            DB::table('escalations')
                ->where('id', $escalation->id)
                ->update([
                    'status' => ($responseType === 'return') ? 'rejected' : $responseType.'ed',
                    'response' => $responseNote,
                    'responded_by' => $user->id,
                    'responded_at' => now(),
                    'response_type' => $responseType,
                    'updated_at' => now(),
                ]);

            // Determine new incident status and assignee
            $updateData = [];

            switch ($responseType) {
                case 'accept':
                    $updateData = [
                        'status' => 'in_progress',
                        'assigned_to' => $user->id,
                        'escalated_to' => null,
                        'escalated_at' => null,
                        'acknowledged_at' => now(),
                    ];
                    $message = 'Escalation accepted. Incident is now in progress and assigned to you.';
                    break;

                case 'reject':
                    $updateData = [
                        'status' => 'open',
                        'escalated_to' => null,
                        'escalated_at' => null,
                    ];
                    $message = 'Escalation rejected. Incident returned to open status.';
                    break;

                case 'return':
                    // Return to previous escalation level or original assignee
                    $previousEscalation = DB::table('escalations')
                        ->where('incident_id', $incident->id)
                        ->where('id', '!=', $escalation->id)
                        ->latest()
                        ->first();

                    if ($previousEscalation) {
                        $updateData = [
                            'escalated_to' => $previousEscalation->escalated_to,
                            'status' => 'escalated',
                        ];
                    } else {
                        $updateData = [
                            'escalated_to' => null,
                            'escalated_at' => null,
                            'status' => 'open',
                        ];
                    }
                    $message = 'Escalation returned to previous level.';
                    break;
            }

            // Update incident
            $updateData['updated_at'] = now();
            DB::table('incidents')
                ->where('id', $incident->id)
                ->update($updateData);

            // Log activity
            DB::table('incident_logs')->insert([
                'incident_id' => $incident->id,
                'user_id' => $user->id,
                'action' => 'escalation_'.$responseType.'ed',
                'new_values' => json_encode([
                    'response_type' => $responseType,
                    'response_note' => $responseNote,
                    'escalation_id' => $escalation->id,
                ]),
                'description' => "Escalation {$responseType}ed by {$user->name}".($responseNote ? ': '.$responseNote : ''),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Send notification asynchronously - DON'T WAIT
            dispatch(function () use ($incident, $escalation, $responseType, $user) {
                try {
                    $freshIncident = Incident::find($incident->id);
                    $escalatedByUser = User::find($escalation->escalated_by);

                    if ($escalatedByUser && $escalatedByUser->id !== $user->id) {
                        // Notify the person who originally escalated
                        $escalatedByUser->notify(new NewIncidentNotification($freshIncident));
                    }

                    // If accepted, notify department HOD
                    if ($responseType === 'accept' && $freshIncident->department) {
                        $hod = $freshIncident->department->getHeadOfDepartment();
                        if ($hod && $hod->id !== $user->id) {
                            $hod->notify(new NewIncidentNotification($freshIncident));
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Escalation response notification failed: '.$e->getMessage());
                }
            })->onQueue('notifications');

            // Return response immediately - don't wait for notification
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'response_type' => $responseType,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Escalation response failed: '.$e->getMessage(), [
                'incident_id' => $incident->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process response. Please try again.',
                ], 500);
            }

            return back()->with('error', 'Failed to process response. Please try again.');
        }
    }

    // app/Http/Controllers/IncidentController.php

    // public function escalate(Request $request, Incident $incident)
    // {
    //     $this->authorize('escalate-incident');

    //     $request->validate([
    //         'escalated_to' => 'required|exists:users,id',
    //         'to_department_id' => 'required|exists:departments,id',
    //         'reason' => 'required|string|max:500',
    //     ]);

    //     // Quick validation - don't escalate to yourself or same department unnecessarily
    //     if ($request->escalated_to == Auth::id()) {
    //         if ($request->ajax()) {
    //             return response()->json(['success' => false, 'message' => 'You cannot escalate to yourself.'], 422);
    //         }
    //         return back()->with('error', 'You cannot escalate to yourself.');
    //     }

    //     // Use a database transaction for data integrity
    //     return DB::transaction(function () use ($request, $incident) {

    //         // Get escalation level (count existing escalations + 1)
    //         $nextLevel = $incident->escalations()->count() + 1;

    //         // Create escalation record
    //         $escalation = $incident->escalations()->create([
    //             'escalated_by' => Auth::id(),
    //             'escalated_to' => $request->escalated_to,
    //             'from_department_id' => $incident->department_id,
    //             'to_department_id' => $request->to_department_id,
    //             'level' => $nextLevel,
    //             'reason' => $request->reason,
    //             'status' => 'pending',
    //         ]);

    //         // Update incident status
    //         $incident->update([
    //             'status' => 'escalated',
    //             'escalated_to' => $request->escalated_to,
    //             'escalated_at' => now(),
    //         ]);

    //         // Log activity - use query builder directly to avoid model events
    //         $incident->logActivity('escalated', null, [
    //             'escalated_to' => $request->escalated_to,
    //             'level' => $nextLevel
    //         ]);

    //         // Dispatch notification asynchronously to avoid timeout
    //         // dispatch(function () use ($incident, $escalation) {
    //         //     try {
    //         //         app(NotificationService::class)->notifyIncidentEscalated($incident, $escalation);
    //         //     } catch (\Exception $e) {
    //         //         Log::error('Escalation notification failed: ' . $e->getMessage());
    //         //     }
    //         // })->onQueue('notifications');

    //         if ($request->ajax() || $request->wantsJson()) {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Incident escalated to level ' . $nextLevel
    //             ]);
    //         }

    //         return back()->with('success', 'Incident escalated successfully.');
    //     });
    // }

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
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
        ]);

        // Send notification
        $this->notificationService->notifyIncidentResolved($incident);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Incident resolved successfully.',
                'files_uploaded' => $request->hasFile('files') ? count($request->file('files')) : 0,
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
            'content' => "🔒 **Incident Closed**\n".$request->closing_remarks,
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

        if (! in_array($incident->status, ['resolved', 'closed'])) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only resolved/closed incidents can be reopened.'], 400);
            }

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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Incident reopened.']);
        }

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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Incident rejected.']);
        }

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
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        // Require either content or files
        if (! $request->filled('content') && ! $request->hasFile('files')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a comment or attach a file.',
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
                    $path = $file->store('comments/'.$incident->id, 'public');
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
                'mentions' => ! empty($mentionedIds) ? $mentionedIds : null,
                'attachments' => ! empty($attachments) ? $attachments : null,
                'is_internal' => $request->is_internal ?? false,
            ]);

            $incident->increment('comments_count');

            // Merge tags
            if (! empty($extractedTags)) {
                $existingTags = $incident->tags ?? [];
                $incident->tags = array_unique(array_merge($existingTags, $extractedTags));
                $incident->save();
            }

            $incident->logActivity('comment_added', null, ['comment' => Str::limit($request->content ?? 'Attachment', 100)]);

            // Notify mentioned users
            if (! empty($mentionedIds)) {
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
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Comment creation failed: '.$e->getMessage(), [
                'incident_id' => $incident->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment. Please try again.',
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
        if ($comment->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only edit your own comments.',
                ], 403);
            }

            return back()->with('error', 'You can only edit your own comments.');
        }

        // Time limit: 30 minutes (admins bypass this)
        if ($comment->created_at->diffInMinutes(now()) > 30 && ! Auth::user()->isAdmin()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comments can only be edited within 30 minutes of posting.',
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
            'content_preview' => Str::limit($request->content, 100),
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
                    ],
                ],
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
        if ($comment->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete your own comments.',
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
                ],
            ]);
        }

        return back()->with('success', 'Comment deleted successfully.');
    }

    private function extractMentions(string $content): array
    {
        preg_match_all('/@(\w+)/', $content, $matches);
        if (empty($matches[1])) {
            return [];
        }

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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => count($mediaRecords).' file(s) uploaded.']);
        }

        return back()->with('success', 'Files uploaded.');
    }

    // public function deleteMedia(Request $request, Incident $incident, $mediaId)
    // {
    //     $this->authorize('delete-media');
    //     $media = $incident->media()->findOrFail($mediaId);
    //     $this->incidentService->deleteMedia($media);
    //     if ($request->ajax()) {
    //         return response()->json(['success' => true, 'message' => 'Media deleted']);
    //     }

    //     return back()->with('success', 'Media deleted.');
    // }
    // app/Http/Controllers/IncidentController.php

    /**
     * Delete media from incident.
     */
    public function deleteMedia(Request $request, Incident $incident, $mediaId)
    {
        $this->authorize('delete-media');

        try {
            $media = $incident->media()->findOrFail($mediaId);

            // Delete physical files
            if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }
            if ($media->thumbnail_path && Storage::disk('public')->exists($media->thumbnail_path)) {
                Storage::disk('public')->delete($media->thumbnail_path);
            }

            // Delete database record
            $media->delete();

            // Log activity
            $incident->logActivity('media_deleted', null, [
                'media_id' => $mediaId,
                'file_name' => $media->original_name,
            ]);

            // Always return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attachment removed successfully.',
                    'media_id' => $mediaId,
                ]);
            }

            return back()->with('success', 'Attachment removed successfully.');

        } catch (\Exception $e) {
            Log::error('Delete media failed: '.$e->getMessage(), [
                'incident_id' => $incident->id,
                'media_id' => $mediaId,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove attachment.',
                ], 500);
            }

            return back()->with('error', 'Failed to remove attachment.');
        }
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
        if (request()->ajax()) {
            return $this->successResponse(null, 'Incident deleted');
        }

        return redirect()->route('incidents.index')->with('success', 'Incident deleted.');
    }
}
