<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Services\IncidentService;
use App\Repositories\IncidentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentController extends Controller
{
    protected $incidentService;
    protected $incidentRepository;

    public function __construct(
        IncidentService $incidentService,
        IncidentRepository $incidentRepository
    ) {
        $this->incidentService = $incidentService;
        $this->incidentRepository = $incidentRepository;
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'department_id', 'category_id', 'severity', 'priority',
            'status', 'assigned_to', 'date_from', 'date_to', 'search'
        ]);

        if (!Auth::user()->isAdmin()) {
            $filters['department_id'] = Auth::user()->department_id;
        }

        $incidents = $this->incidentRepository->getFeedIncidents($filters);

        return $this->paginatedResponse($incidents);
    }

    public function show(Incident $incident)
    {
        if (!Auth::user()->canAccessIncident($incident)) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $incident->increment('views_count');
        $incident = $this->incidentRepository->getIncidentDetails($incident->id);

        return $this->successResponse($incident);
    }

    public function store(Request $request)
    {
        $this->authorize('create-incident');

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:incident_categories,id',
            'severity' => 'required|in:low,medium,high,critical',
            'priority' => 'required|in:low,medium,high,critical',
            'department_id' => 'required|exists:departments,id',
            'location' => 'nullable|string|max:255',
            'files.*' => 'nullable|file|max:20480',
        ]);

        $incident = $this->incidentService->createIncident(
            $request->all(),
            $request->file('files', [])
        );

        return $this->successResponse($incident, 'Incident created successfully', 201);
    }

    public function update(Request $request, Incident $incident)
    {
        $this->authorize('edit-incident');

        $incident = $this->incidentService->updateIncident($incident, $request->all());

        return $this->successResponse($incident, 'Incident updated successfully');
    }

    public function destroy(Incident $incident)
    {
        $this->authorize('delete-incident');

        $incident->delete();

        return $this->successResponse(null, 'Incident deleted successfully');
    }

    public function assign(Request $request, Incident $incident)
    {
        $this->authorize('assign-incident');

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $this->incidentService->assignIncident(
            $incident,
            $request->assigned_to,
            $request->notes
        );

        return $this->successResponse(null, 'Incident assigned successfully');
    }

    public function escalate(Request $request, Incident $incident)
    {
        $this->authorize('escalate-incident');

        $request->validate([
            'escalated_to' => 'required|exists:users,id',
            'to_department_id' => 'required|exists:departments,id',
            'reason' => 'required|string|max:500',
        ]);

        $this->incidentService->escalateIncident($incident, $request->all());

        return $this->successResponse(null, 'Incident escalated successfully');
    }

    public function resolve(Request $request, Incident $incident)
    {
        $this->authorize('resolve-incident');

        $request->validate([
            'resolution_notes' => 'required|string|max:1000',
        ]);

        $this->incidentService->resolveIncident($incident, $request->resolution_notes);

        return $this->successResponse(null, 'Incident resolved successfully');
    }

    public function close(Request $request, Incident $incident)
    {
        $this->authorize('close-incident');

        $this->incidentService->closeIncident($incident);

        return $this->successResponse(null, 'Incident closed successfully');
    }

    public function reopen(Request $request, Incident $incident)
    {
        $this->authorize('reopen-incident');

        $this->incidentService->reopenIncident($incident);

        return $this->successResponse(null, 'Incident reopened successfully');
    }

    public function addComment(Request $request, Incident $incident)
    {
        $this->authorize('add-comment');

        $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:incident_comments,id',
            'mentions' => 'nullable|array',
        ]);

        $this->incidentService->addComment($incident, $request->all());

        $incident = $this->incidentRepository->getIncidentDetails($incident->id);

        return $this->successResponse([
            'comments' => $incident->comments,
            'comments_count' => $incident->comments_count,
        ], 'Comment added successfully');
    }

    public function uploadMedia(Request $request, Incident $incident)
    {
        $this->authorize('upload-media');

        $request->validate([
            'files.*' => 'required|file|max:20480',
        ]);

        $media = $this->incidentService->uploadMedia($incident, $request->file('files', []));

        return $this->successResponse($media, 'Media uploaded successfully');
    }

    public function deleteMedia(Incident $incident, $mediaId)
    {
        $this->authorize('delete-media');

        $media = $incident->media()->findOrFail($mediaId);
        $this->incidentService->deleteMedia($media);

        return $this->successResponse(null, 'Media deleted successfully');
    }

    public function timeline(Incident $incident)
    {
        return $this->successResponse($incident->timeline);
    }

    public function logs(Incident $incident)
    {
        return $this->successResponse($incident->logs()->with('user')->get());
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $incidents = $this->incidentRepository->getFeedIncidents([
            'search' => $request->q,
        ]);

        return $this->paginatedResponse($incidents);
    }
}