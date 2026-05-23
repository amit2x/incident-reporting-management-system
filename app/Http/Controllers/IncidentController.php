<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Services\IncidentService;
use App\Repositories\IncidentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

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
            'status', 'assigned_to', 'date_from', 'date_to', 'search'
        ]);

        // Apply role-based filters
        if (!Auth::user()->isAdmin()) {
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
        if (!Auth::user()->canAccessIncident($incident)) {
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

        $departments = \App\Models\Department::active()->ordered()->get();
        $categories = \App\Models\IncidentCategory::active()->get();

        return view('incidents.create', compact('departments', 'categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create-incident');

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:incident_categories,id',
            'severity' => 'required|in:low,medium,high,critical',
            'priority' => 'required|in:low,medium,high,critical',
            'department_id' => 'required|exists:departments,id',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'tags' => 'nullable|array',
            'is_anonymous' => 'nullable|boolean',
            'files.*' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,wmv,flv,webm,mp3,wav,pdf,doc,docx,xls,xlsx,txt,csv,zip',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $files = $request->file('files', []);
            $incident = $this->incidentService->createIncident($request->all(), $files);

            if ($request->ajax()) {
                return $this->successResponse([
                    'incident' => $incident,
                    'redirect' => route('incidents.show', $incident),
                ], 'Incident reported successfully');
            }

            return redirect()->route('incidents.show', $incident)
                ->with('success', 'Incident reported successfully!');
        } catch (\Exception $e) {
            \Log::error('Incident creation failed: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return $this->errorResponse('Failed to create incident', 500);
            }
            
            return back()->with('error', 'Failed to create incident. Please try again.')->withInput();
        }
    }

    public function edit(Incident $incident)
    {
        $this->authorize('edit-incident');
        
        if (!Auth::user()->canAccessIncident($incident)) {
            abort(403);
        }

        $departments = \App\Models\Department::active()->ordered()->get();
        $categories = \App\Models\IncidentCategory::active()->get();

        return view('incidents.edit', compact('incident', 'departments', 'categories'));
    }

    public function update(Request $request, Incident $incident)
    {
        $this->authorize('edit-incident');
        
        if (!Auth::user()->canAccessIncident($incident)) {
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
            \Log::error('Incident update failed: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return $this->errorResponse('Failed to update incident', 500);
            }
            
            return back()->with('error', 'Failed to update incident.')->withInput();
        }
    }

    public function assign(Request $request, Incident $incident)
    {
        $this->authorize('assign-incident');

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $this->incidentService->assignIncident(
                $incident,
                $request->assigned_to,
                $request->notes
            );

            return $this->successResponse(null, 'Incident assigned successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Assignment failed: ' . $e->getMessage(), 500);
        }
    }

    public function escalate(Request $request, Incident $incident)
    {
        $this->authorize('escalate-incident');

        $validator = Validator::make($request->all(), [
            'escalated_to' => 'required|exists:users,id',
            'to_department_id' => 'required|exists:departments,id',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $this->incidentService->escalateIncident($incident, $request->all());
            return $this->successResponse(null, 'Incident escalated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Escalation failed: ' . $e->getMessage(), 500);
        }
    }

    public function resolve(Request $request, Incident $incident)
    {
        $this->authorize('resolve-incident');

        $validator = Validator::make($request->all(), [
            'resolution_notes' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $this->incidentService->resolveIncident($incident, $request->resolution_notes);
            return $this->successResponse(null, 'Incident resolved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Resolution failed: ' . $e->getMessage(), 500);
        }
    }

    public function close(Request $request, Incident $incident)
    {
        $this->authorize('close-incident');

        try {
            $this->incidentService->closeIncident($incident);
            return $this->successResponse(null, 'Incident closed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Closure failed: ' . $e->getMessage(), 500);
        }
    }

    public function reopen(Request $request, Incident $incident)
    {
        $this->authorize('reopen-incident');

        try {
            $this->incidentService->reopenIncident($incident);
            return $this->successResponse(null, 'Incident reopened successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Reopen failed: ' . $e->getMessage(), 500);
        }
    }

    public function addComment(Request $request, Incident $incident)
    {
        $this->authorize('add-comment');

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:incident_comments,id',
            'mentions' => 'nullable|array',
            'is_internal' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $this->incidentService->addComment($incident, $request->all());
            $incident = $this->incidentRepository->getIncidentDetails($incident->id);
            return $this->successResponse([
                'comments' => $incident->comments,
                'comments_count' => $incident->comments_count,
            ], 'Comment added successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add comment: ' . $e->getMessage(), 500);
        }
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
            return $this->errorResponse('Media upload failed: ' . $e->getMessage(), 500);
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
            return $this->errorResponse('Failed to delete media: ' . $e->getMessage(), 500);
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