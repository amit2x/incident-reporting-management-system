<?php
// app/Http/Controllers/Api/IncidentController.php

namespace App\Http\Controllers\Api;

use App\Models\Incident;
use App\Services\IncidentService;
use App\Repositories\IncidentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class IncidentController extends BaseApiController
{
    protected IncidentService $incidentService;
    protected IncidentRepository $incidentRepository;

    public function __construct(
        IncidentService $incidentService,
        IncidentRepository $incidentRepository
    ) {
        $this->incidentService = $incidentService;
        $this->incidentRepository = $incidentRepository;
    }

    /**
     * Display a listing of incidents
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'department_id', 'category_id', 'severity', 'priority',
            'status', 'assigned_to', 'date_from', 'date_to', 'search', 'per_page'
        ]);

        // Apply role-based filters
        $user = $this->getUser();
        if (!$this->isAdmin()) {
            $filters['department_id'] = $user->department_id;
        }

        $perPage = $request->get('per_page', 15);
        $incidents = $this->incidentRepository->getFeedIncidents($filters, $perPage);

        return $this->paginatedResponse($incidents);
    }

    /**
     * Store a newly created incident
     */
    public function store(Request $request): JsonResponse
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
            'files.*' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,mp3,wav,pdf,doc,docx,xls,xlsx',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $incident = $this->incidentService->createIncident(
                $request->except('files'),
                $request->file('files', [])
            );

            return $this->successResponse(
                $incident->load(['reporter', 'department', 'category', 'media']),
                'Incident created successfully',
                201
            );
        } catch (\Exception $e) {
            \Log::error('API Incident creation failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to create incident: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified incident
     */
    public function show(Incident $incident): JsonResponse
    {
        $user = $this->getUser();

        if (!$user->canAccessIncident($incident)) {
            return $this->errorResponse('Unauthorized access', 403);
        }

        $incident->increment('views_count');
        $incident = $this->incidentRepository->getIncidentDetails($incident->id);

        return $this->successResponse($incident);
    }

    /**
     * Update the specified incident
     */
    public function update(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('edit-incident');

        $user = $this->getUser();
        if (!$user->canAccessIncident($incident)) {
            return $this->errorResponse('Unauthorized access', 403);
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
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $incident = $this->incidentService->updateIncident($incident, $request->all());
            return $this->successResponse($incident, 'Incident updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update incident: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified incident
     */
    public function destroy(Incident $incident): JsonResponse
    {
        $this->authorize('delete-incident');

        try {
            $incident->delete();
            return $this->successResponse(null, 'Incident deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete incident: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign incident to user
     */
    public function assign(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('assign-incident');

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
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

    /**
     * Escalate incident
     */
    public function escalate(Request $request, Incident $incident): JsonResponse
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

    /**
     * Resolve incident
     */
    public function resolve(Request $request, Incident $incident): JsonResponse
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

    /**
     * Close incident
     */
    public function close(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('close-incident');

        try {
            $this->incidentService->closeIncident($incident);
            return $this->successResponse(null, 'Incident closed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Closure failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reopen incident
     */
    public function reopen(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('reopen-incident');

        try {
            $this->incidentService->reopenIncident($incident);
            return $this->successResponse(null, 'Incident reopened successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Reopen failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add comment to incident
     */
    public function addComment(Request $request, Incident $incident): JsonResponse
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

    /**
     * Upload media to incident
     */
    public function uploadMedia(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('upload-media');

        $validator = Validator::make($request->all(), [
            'files.*' => 'required|file|max:20480|mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,mp3,wav,pdf,doc,docx,xls,xlsx',
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

    /**
     * Delete media from incident
     */
    public function deleteMedia(Incident $incident, $mediaId): JsonResponse
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

    /**
     * Get incident timeline
     */
    public function timeline(Incident $incident): JsonResponse
    {
        return $this->successResponse($incident->timeline);
    }

    /**
     * Get incident logs
     */
    public function logs(Incident $incident): JsonResponse
    {
        $logs = $incident->logs()->with('user')->latest()->paginate(30);
        return $this->paginatedResponse($logs);
    }

    /**
     * Search incidents
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $filters = ['search' => $request->q];

        if (!$this->isAdmin()) {
            $filters['department_id'] = $this->getUser()->department_id;
        }

        $incidents = $this->incidentRepository->getFeedIncidents($filters);

        return $this->paginatedResponse($incidents);
    }
}