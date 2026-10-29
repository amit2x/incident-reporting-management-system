<?php

// app/Services/Attendance/GeofenceService.php

namespace App\Services\Attendance;

use App\Models\Airport;

class GeofenceService
{
    /**
     * Calculate distance (meters) between two coordinates using Haversine.
     */
    public function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(sqrt($a));
    }

    /**
     * Check whether user is inside the airport's geofence.
     */
    public function check(Airport $airport, float $userLat, float $userLng): array
    {
        $settings = $airport->attendanceSettings();

        $distance = $this->distanceInMeters(
            $userLat,
            $userLng,
            (float) $settings['geofence_lat'],
            (float) $settings['geofence_lng']
        );

        return [
            'is_within' => $distance <= $settings['geofence_radius_meters'],
            'distance' => (int) round($distance),
            'radius' => $settings['geofence_radius_meters'],
        ];
    }
}
