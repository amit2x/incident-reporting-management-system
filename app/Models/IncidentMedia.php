<?php

namespace App\Models;

use App\Traits\HasAirport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class IncidentMedia extends Model
{
    use HasFactory, SoftDeletes, HasAirport;

    protected $fillable = [
        'incident_id',
        'airport_id',
        'uploaded_by',
        'media_type',
        'file_path',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'description',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sort_order' => 'integer',
        'file_size' => 'integer',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the user who uploaded the media.
     */
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : $this->url;
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    public function isDocument(): bool
    {
        return $this->media_type === 'document';
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get compression ratio from metadata.
     */
    public function getCompressionRatioAttribute(): ?float
    {
        return $this->metadata['file']['compression_ratio'] ?? null;
    }

    /**
     * Get image dimensions from metadata.
     */
    public function getDimensionsAttribute(): ?array
    {
        return [
            'width' => $this->metadata['image']['width'] ?? null,
            'height' => $this->metadata['image']['height'] ?? null,
        ];
    }

    /**
     * Get EXIF data from metadata.
     */
    public function getExifDataAttribute(): ?array
    {
        return $this->metadata['exif'] ?? null;
    }

    /**
     * Get compression percentage.
     */
    public function getCompressionPercentageAttribute(): ?float
    {
        return $this->metadata['file']['compression_ratio'] ?? null;
    }

    /**
     * Get image dimensions as string.
     */
    public function getDimensionsStringAttribute(): string
    {
        $width = $this->metadata['image']['width'] ?? 0;
        $height = $this->metadata['image']['height'] ?? 0;

        return $width.' × '.$height;
    }

    /**
     * Get aspect ratio.
     */
    public function getAspectRatioAttribute(): string
    {
        return $this->metadata['image']['aspect_ratio'] ?? 'Unknown';
    }

    /**
     * Get processing time.
     */
    public function getProcessingTimeAttribute(): string
    {
        $ms = $this->metadata['processing']['processing_time_ms'] ?? 0;
        if ($ms > 1000) {
            return round($ms / 1000, 2).'s';
        }

        return round($ms, 2).'ms';
    }

    /**
     * Get camera info.
     */
    public function getCameraInfoAttribute(): ?string
    {
        $make = $this->metadata['exif']['camera_make'] ?? null;
        $model = $this->metadata['exif']['camera_model'] ?? null;

        if ($make && $model) {
            return $make.' '.$model;
        }
        if ($model) {
            return $model;
        }
        if ($make) {
            return $make;
        }

        return null;
    }

    /**
     * Check if image has EXIF data.
     */
    public function hasExifData(): bool
    {
        return ($this->metadata['exif']['available'] ?? false) === true;
    }

    /**
     * Get EXIF data as formatted string.
     */
    public function getExifSummaryAttribute(): ?string
    {
        if (! $this->hasExifData()) {
            return null;
        }

        $parts = [];
        $exif = $this->metadata['exif'];

        if (isset($exif['camera_make']) || isset($exif['camera_model'])) {
            $camera = trim(($exif['camera_make'] ?? '').' '.($exif['camera_model'] ?? ''));
            if ($camera) {
                $parts[] = '📷 '.$camera;
            }
        }

        if (isset($exif['iso'])) {
            $parts[] = 'ISO '.$exif['iso'];
        }

        if (isset($exif['aperture'])) {
            $parts[] = $exif['aperture'];
        }

        if (isset($exif['shutter_speed'])) {
            $parts[] = $exif['shutter_speed'];
        }

        if (isset($exif['taken_at'])) {
            $parts[] = '📅 '.$exif['taken_at'];
        }

        return implode(' · ', $parts);
    }

    /**
     * Get GPS location data from metadata.
     */
    public function getGpsDataAttribute(): ?array
    {
        return $this->metadata['exif']['gps'] ?? null;
    }

    /**
     * Check if image has GPS data.
     */
    public function hasGpsData(): bool
    {
        $gps = $this->getGpsDataAttribute();

        return $gps && isset($gps['latitude']) && isset($gps['longitude']);
    }

    /**
     * Get formatted GPS coordinates as string.
     */
    public function getGpsCoordinatesAttribute(): ?string
    {
        if (! $this->hasGpsData()) {
            return null;
        }

        $gps = $this->getGpsDataAttribute();
        $lat = $gps['latitude'];
        $lng = $gps['longitude'];

        $latDirection = $lat >= 0 ? 'N' : 'S';
        $lngDirection = $lng >= 0 ? 'E' : 'W';

        return sprintf(
            '%s° %s, %s° %s',
            number_format(abs($lat), 6),
            $latDirection,
            number_format(abs($lng), 6),
            $lngDirection
        );
    }

    /**
     * Get Google Maps URL for the location.
     */
    public function getGoogleMapsUrlAttribute(): ?string
    {
        if (! $this->hasGpsData()) {
            return null;
        }

        $gps = $this->getGpsDataAttribute();

        return sprintf(
            'https://www.google.com/maps?q=%s,%s',
            $gps['latitude'],
            $gps['longitude']
        );
    }

    /**
     * Get OpenStreetMap URL for the location.
     */
    public function getOpenStreetMapUrlAttribute(): ?string
    {
        if (! $this->hasGpsData()) {
            return null;
        }

        $gps = $this->getGpsDataAttribute();

        return sprintf(
            'https://www.openstreetmap.org/?mlat=%s&mlon=%s&zoom=15',
            $gps['latitude'],
            $gps['longitude']
        );
    }

    /**
     * Get the altitude from GPS data.
     */
    public function getAltitudeAttribute(): ?float
    {
        $gps = $this->getGpsDataAttribute();

        return $gps['altitude'] ?? null;
    }

    /**
     * Get formatted altitude with unit.
     */
    public function getFormattedAltitudeAttribute(): ?string
    {
        $altitude = $this->getAltitudeAttribute();
        if ($altitude === null) {
            return null;
        }

        return number_format($altitude, 1).'m';
    }

    /**
     * Get GPS timestamp.
     */
    public function getGpsTimestampAttribute(): ?string
    {
        $gps = $this->getGpsDataAttribute();

        return $gps['timestamp'] ?? null;
    }

    /**
     * Get complete location summary.
     */
    public function getLocationSummaryAttribute(): ?string
    {
        if (! $this->hasGpsData()) {
            return null;
        }

        $parts = [];
        $gps = $this->getGpsDataAttribute();

        // Coordinates
        $parts[] = '📍 '.$this->getGpsCoordinatesAttribute();

        // Altitude
        if ($this->getFormattedAltitudeAttribute()) {
            $parts[] = '🏔️ '.$this->getFormattedAltitudeAttribute();
        }

        // Timestamp
        if ($this->getGpsTimestampAttribute()) {
            $parts[] = '🕐 '.$this->getGpsTimestampAttribute();
        }

        return implode(' · ', $parts);
    }

    /**
     * Check if image has location data.
     */
    public function hasLocationData(): bool
    {
        return $this->hasGpsData();
    }

    /**
     * Get reverse geocoded address (if you have a geocoding service).
     * Note: This requires a geocoding API like Google Maps Geocoding API.
     */
    public function getReverseGeocodeAttribute(): ?string
    {
        if (! $this->hasGpsData()) {
            return null;
        }

        // You would implement this with a geocoding service
        // Example using Google Maps Geocoding API:
        // $gps = $this->getGpsDataAttribute();
        // $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$gps['latitude']},{$gps['longitude']}&key=" . config('services.google.maps_api_key');
        // ... fetch and parse response

        return null;
    }

    /**
     * Get distance from a specific coordinate (in meters).
     */
    public function getDistanceFrom(float $lat, float $lng): ?float
    {
        if (! $this->hasGpsData()) {
            return null;
        }

        $gps = $this->getGpsDataAttribute();

        return $this->calculateHaversineDistance(
            $gps['latitude'],
            $gps['longitude'],
            $lat,
            $lng
        );
    }

    /**
     * Calculate Haversine distance between two points.
     */
    protected function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get formatted distance.
     */
    public function getFormattedDistanceFrom(float $lat, float $lng): string
    {
        $distance = $this->getDistanceFrom($lat, $lng);

        if ($distance === null) {
            return 'N/A';
        }

        if ($distance >= 1000) {
            return number_format($distance / 1000, 2).' km';
        }

        return number_format($distance, 0).' m';
    }
}
