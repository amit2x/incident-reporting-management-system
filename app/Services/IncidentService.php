<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentMedia;
use App\Repositories\IncidentRepository;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class IncidentService
{
    protected IncidentRepository $incidentRepository;
    protected NotificationService $notificationService;
    protected ImageManager $imageManager;

    public function __construct(
        IncidentRepository $incidentRepository,
        NotificationService $notificationService
    ) {
        $this->incidentRepository = $incidentRepository;
        $this->notificationService = $notificationService;
        $this->imageManager = new ImageManager(new Driver());
    }

    public function createIncident(array $data, array $files = []): Incident
    {
        return DB::transaction(function () use ($data, $files) {
            // Merge additional data
            $data['reported_by'] = auth()->id();

            if (empty($data['department_id']) && auth()->user()->department_id) {
                $data['department_id'] = auth()->user()->department_id;
            }

            // Create incident
            $incident = $this->incidentRepository->create($data);

            // Handle file uploads
            if (!empty($files)) {
                $this->handleFileUploads($incident, $files);
            }

            // Send notifications
            $this->notificationService->notifyNewIncident($incident);

            // Log activity
            $incident->logActivity('created', null, $incident->toArray());

            return $incident->load(['reporter', 'department', 'category', 'media']);
        });
    }

    public function updateIncident(Incident $incident, array $data): Incident
    {
        $oldData = $incident->toArray();

        $incident->update($data);

        // Log activity
        $incident->logActivity('updated', $oldData, $incident->fresh()->toArray());

        return $incident->fresh(['reporter', 'department', 'category', 'media']);
    }

    public function assignIncident(Incident $incident, int $userId, string $notes = null): void
    {
        $oldAssignee = $incident->assigned_to;

        $incident->update([
            'assigned_to' => $userId,
            'status' => $incident->status === 'open' ? 'acknowledged' : $incident->status,
            'acknowledged_at' => $incident->status === 'open' ? now() : $incident->acknowledged_at,
        ]);

        // Create assignment record
        $incident->assignments()->create([
            'assigned_by' => auth()->id(),
            'assigned_to' => $userId,
            'notes' => $notes,
            'assigned_at' => now(),
        ]);

        // Send notification
        $this->notificationService->notifyIncidentAssigned($incident, $userId);

        // Log activity
        $incident->logActivity('assigned',
            ['assigned_to' => $oldAssignee],
            ['assigned_to' => $userId]
        );
    }

    public function escalateIncident(Incident $incident, array $escalationData): void
    {
        $incident->escalate(
            $escalationData['escalated_to'],
            $escalationData['reason']
        );

        // Create escalation record
        $escalation = $incident->escalations()->create([
            'escalated_by' => auth()->id(),
            'escalated_to' => $escalationData['escalated_to'],
            'from_department_id' => $incident->department_id,
            'to_department_id' => $escalationData['to_department_id'],
            'level' => $incident->escalations()->count() + 1,
            'reason' => $escalationData['reason'],
        ]);

        // Send notifications
        $this->notificationService->notifyIncidentEscalated($incident, $escalation);
    }

    public function resolveIncident(Incident $incident, string $resolutionNotes): void
    {
        $incident->resolve($resolutionNotes);

        // Send notification
        $this->notificationService->notifyIncidentResolved($incident);
    }

    public function closeIncident(Incident $incident): void
    {
        $incident->close();

        // Send notification
        $this->notificationService->notifyIncidentClosed($incident);
    }

    public function reopenIncident(Incident $incident): void
    {
        $incident->reopen();

        // Send notification
        $this->notificationService->notifyIncidentReopened($incident);
    }

    public function addComment(Incident $incident, array $commentData): void
    {
        $comment = $incident->comments()->create([
            'user_id' => auth()->id(),
            'content' => $commentData['content'],
            'parent_id' => $commentData['parent_id'] ?? null,
            'mentions' => $commentData['mentions'] ?? [],
            'is_internal' => $commentData['is_internal'] ?? false,
        ]);

        // Update comment count
        $incident->increment('comments_count');

        // Log activity
        $incident->logActivity('comment_added', null, ['comment_id' => $comment->id]);

        // Notify mentioned users
        if (!empty($commentData['mentions'])) {
            $this->notificationService->notifyMentionedUsers($incident, $comment);
        }

        // Notify incident followers
        $this->notificationService->notifyNewComment($incident, $comment);
    }

    public function uploadMedia(Incident $incident, array $files): array
    {
        return $this->handleFileUploads($incident, $files);
    }

    public function deleteMedia(IncidentMedia $media): void
    {
        // Delete physical files
        Storage::delete($media->file_path);
        if ($media->thumbnail_path) {
            Storage::delete($media->thumbnail_path);
        }

        // Delete record
        $media->delete();
    }

    protected function handleFileUploads(Incident $incident, array $files): array
    {
        $mediaRecords = [];

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $mediaRecord = $this->processAndStoreFile($incident, $file, $index);
                $mediaRecords[] = $mediaRecord;
            }
        }

        return $mediaRecords;
    }

    protected function processAndStoreFile(Incident $incident, UploadedFile $file, int $sortOrder): IncidentMedia
    {
        $mediaType = $this->getMediaType($file);
        $path = $this->generateStoragePath($incident, $mediaType);

        // Store original file
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs($path, $fileName, 'public');

        $thumbnailPath = null;

        // Generate thumbnail for images
        if ($mediaType === 'image' && $file->getSize() < 10485760) { // Less than 10MB
            $thumbnailPath = $this->generateThumbnail($file, $path, $fileName);
        }

        // Compress large images
        if ($mediaType === 'image' && $file->getSize() > 5242880) { // Greater than 5MB
            $filePath = $this->compressImage($file, $path, $fileName);
        }

        // Create media record
        return IncidentMedia::create([
            'incident_id' => $incident->id,
            'uploaded_by' => auth()->id(),
            'media_type' => $mediaType,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'thumbnail_path' => $thumbnailPath,
            'sort_order' => $sortOrder,
            'metadata' => $this->extractFileMetadata($file),
        ]);
    }

    protected function getMediaType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } else {
            return 'document';
        }
    }

    protected function generateStoragePath(Incident $incident, string $mediaType): string
    {
        return sprintf(
            'incidents/%s/%s/%s',
            $incident->id,
            $mediaType,
            date('Y/m')
        );
    }

    protected function generateThumbnail(UploadedFile $file, string $path, string $fileName): string
    {
        try {
            $thumbnailPath = $path . '/thumbnails/' . $fileName;

            $image = $this->imageManager->read($file);
            $image->resize(300, 300, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            Storage::disk('public')->put($thumbnailPath, $image->toJpeg(80));

            return $thumbnailPath;
        } catch (\Exception $e) {
            \Log::error('Thumbnail generation failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function compressImage(UploadedFile $file, string $path, string $fileName): string
    {
        try {
            $image = $this->imageManager->read($file);
            $compressedPath = $path . '/' . $fileName;

            Storage::disk('public')->put($compressedPath, $image->toJpeg(75));

            return $compressedPath;
        } catch (\Exception $e) {
            \Log::error('Image compression failed: ' . $e->getMessage());
            return $file->storeAs($path, $fileName, 'public');
        }
    }

    protected function extractFileMetadata(UploadedFile $file): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ];

        // Extract EXIF data for images
        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                $exif = @exif_read_data($file->getPathname());
                if ($exif) {
                    $metadata['exif'] = array_intersect_key($exif, array_flip([
                        'Make', 'Model', 'DateTimeOriginal', 'GPSLatitude', 'GPSLongitude'
                    ]));
                }
            } catch (\Exception $e) {
                // Ignore EXIF extraction errors
            }
        }

        return $metadata;
    }
}
