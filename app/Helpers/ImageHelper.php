<?php

namespace App\Helpers;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageHelper
{
    /**
     * Image processing configuration.
     */
    protected array $config;

    /**
     * Image manager instance.
     */
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->config = [
            'webp_quality' => 82,
            'max_width' => 1920,
            'max_height' => 1920,
            'thumbnail_width' => 300,
            'thumbnail_height' => 300,
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'],
            'max_file_size' => 20 * 1024 * 1024, // 20MB
        ];

        $this->imageManager = new ImageManager(new Driver);
    }

    /**
     * Process an uploaded image file.
     */
    public function processImage(UploadedFile $file, array $options = []): array
    {
        $startTime = microtime(true);
        $metadata = [];

        try {
            // Merge options with defaults
            $options = array_merge($this->config, $options);

            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $storedFilename = $this->generateFilename($file);

            // Get original metadata
            $metadata['file'] = $this->getFileMetadata($file);

            // Get image metadata
            $imageInfo = $this->getImageInfo($file);
            $metadata['image'] = $imageInfo;

            // Get EXIF data
            $exifData = $this->extractExifData($file);
            if (! empty($exifData)) {
                $metadata['exif'] = $exifData;
            }

            // Calculate optimal dimensions
            $dimensions = $this->calculateDimensions($imageInfo, $options);

            // Convert to WebP
            $webpData = $this->convertToWebP($file, $dimensions, $options['webp_quality']);
            $metadata['image']['converted_to_webp'] = true;
            $metadata['image']['webp_quality'] = $options['webp_quality'];
            $metadata['image']['width'] = $dimensions['width'];
            $metadata['image']['height'] = $dimensions['height'];
            $metadata['image']['aspect_ratio'] = $this->calculateAspectRatio($dimensions['width'], $dimensions['height']);

            // Generate hashes
            $metadata['hash'] = $this->generateHashes($webpData['data']);

            // Store the file
            $storedPath = $this->storeFile($webpData['data'], $storedFilename, $options);

            // Generate thumbnail if needed
            $thumbnailPath = null;
            if ($options['generate_thumbnail'] ?? true) {
                $thumbnailPath = $this->generateThumbnail($webpData['data'], $storedFilename, $options);
            }

            // Processing metadata
            $metadata['processing'] = [
                'processed_at' => now()->toIso8601String(),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'pipeline_version' => '1.0.0',
                'client_compressed' => false,
                'server_converted' => true,
            ];

            // File metadata update
            $metadata['file']['stored_filename'] = $storedFilename;
            $metadata['file']['compressed_size'] = strlen($webpData['data']);
            $metadata['file']['compression_ratio'] = $metadata['file']['original_size'] > 0
                ? round((1 - ($metadata['file']['compressed_size'] / $metadata['file']['original_size'])) * 100, 2)
                : 0;

            return [
                'success' => true,
                'stored_path' => $storedPath,
                'thumbnail_path' => $thumbnailPath,
                'stored_filename' => $storedFilename,
                'metadata' => $metadata,
                'webp_data' => $webpData['data'],
            ];

        } catch (Exception $e) {
            Log::error('Image processing failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'metadata' => $metadata,
            ];
        }
    }

    /**
     * Validate the uploaded file.
     */
    protected function validateFile(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new Exception('Invalid file upload.');
        }

        if ($file->getSize() > $this->config['max_file_size']) {
            throw new Exception(sprintf(
                'File size exceeds the maximum allowed size of %s.',
                $this->formatBytes($this->config['max_file_size'])
            ));
        }

        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, $this->config['allowed_mime_types'])) {
            throw new Exception("Unsupported file type: {$mimeType}.");
        }
    }

    /**
     * Get file metadata.
     */
    protected function getFileMetadata(UploadedFile $file): array
    {
        return [
            'original_filename' => $file->getClientOriginalName(),
            'original_extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'original_size' => $file->getSize(),
        ];
    }

    /**
     * Get image information.
     */
    protected function getImageInfo(UploadedFile $file): array
    {
        try {
            $image = $this->imageManager->read($file);
            $width = $image->width();
            $height = $image->height();

            return [
                'original_width' => $width,
                'original_height' => $height,
                'orientation' => $width > $height ? 'Landscape' : ($width < $height ? 'Portrait' : 'Square'),
                'aspect_ratio' => $this->calculateAspectRatio($width, $height),
            ];
        } catch (Exception $e) {
            Log::warning('Failed to read image dimensions', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            return [
                'original_width' => 0,
                'original_height' => 0,
                'orientation' => 'Unknown',
                'aspect_ratio' => 'Unknown',
            ];
        }
    }

    /**
     * Calculate aspect ratio.
     */
    protected function calculateAspectRatio(int $width, int $height): string
    {
        $gcd = $this->gcd($width, $height);
        if ($gcd === 0) {
            return 'Unknown';
        }

        return ($width / $gcd).':'.($height / $gcd);
    }

    /**
     * Calculate greatest common divisor.
     */
    protected function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        if ($a === 0 || $b === 0) {
            return max($a, $b);
        }

        while ($b > 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return $a;
    }

    /**
     * Calculate optimal dimensions for image.
     */
    protected function calculateDimensions(array $imageInfo, array $options): array
    {
        $width = $imageInfo['original_width'];
        $height = $imageInfo['original_height'];
        $maxWidth = $options['max_width'];
        $maxHeight = $options['max_height'];

        // If image is smaller than max dimensions, keep original size
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return ['width' => $width, 'height' => $height];
        }

        // Calculate scaling factor
        $scaleFactor = min($maxWidth / $width, $maxHeight / $height);

        return [
            'width' => (int) round($width * $scaleFactor),
            'height' => (int) round($height * $scaleFactor),
        ];
    }

    /**
     * Convert image to WebP format.
     */
    protected function convertToWebP(UploadedFile $file, array $dimensions, int $quality): array
    {
        try {
            $image = $this->imageManager->read($file);

            // Resize if needed
            if ($dimensions['width'] !== $image->width() || $dimensions['height'] !== $image->height()) {
                $image->resize($dimensions['width'], $dimensions['height']);
            }

            // Encode as WebP
            $encoded = $image->toWebp($quality);

            return [
                'data' => $encoded->toString(),
                'size' => strlen($encoded->toString()),
            ];

        } catch (Exception $e) {
            throw new Exception('Failed to convert image to WebP: '.$e->getMessage());
        }
    }

    /**
     * Generate thumbnail.
     */
    protected function generateThumbnail(string $imageData, string $filename, array $options): ?string
    {
        try {
            $image = $this->imageManager->read($imageData);

            $width = $options['thumbnail_width'] ?? 300;
            $height = $options['thumbnail_height'] ?? 300;

            $image->cover($width, $height);

            $thumbnailData = $image->toWebp(70)->toString();

            $thumbFilename = 'thumb_'.$filename;
            $thumbPath = 'thumbnails/'.$thumbFilename;

            Storage::disk('public')->put($thumbPath, $thumbnailData);

            return $thumbPath;

        } catch (Exception $e) {
            Log::warning('Thumbnail generation failed', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Store the processed image.
     */
    protected function storeFile(string $imageData, string $filename, array $options): string
    {
        $path = $options['storage_path'] ?? 'incidents';
        $subPath = date('Y/m');

        $fullPath = $path.'/'.$subPath.'/'.$filename;

        Storage::disk('public')->put($fullPath, $imageData);

        return $fullPath;
    }

    /**
     * Extract EXIF data from image.
     */
    // protected function extractExifData(UploadedFile $file): array
    // {
    //     try {
    //         $exif = @exif_read_data($file->getRealPath());

    //         if ($exif === false) {
    //             return ['available' => false];
    //         }

    //         $data = ['available' => true];

    //         // DateTime
    //         if (isset($exif['DateTimeOriginal'])) {
    //             $data['taken_at'] = $this->normalizeExifDate($exif['DateTimeOriginal']);
    //         } elseif (isset($exif['DateTime'])) {
    //             $data['taken_at'] = $this->normalizeExifDate($exif['DateTime']);
    //         }

    //         // Camera Info
    //         if (isset($exif['Make'])) {
    //             $data['camera_make'] = trim($exif['Make']);
    //         }
    //         if (isset($exif['Model'])) {
    //             $data['camera_model'] = trim($exif['Model']);
    //         }
    //         if (isset($exif['LensModel'])) {
    //             $data['lens_model'] = trim($exif['LensModel']);
    //         }

    //         // Camera Settings
    //         if (isset($exif['ISOSpeedRatings'])) {
    //             $data['iso'] = (int) $exif['ISOSpeedRatings'];
    //         }
    //         if (isset($exif['FNumber'])) {
    //             $data['aperture'] = $this->normalizeAperture($exif['FNumber']);
    //         }
    //         if (isset($exif['ExposureTime'])) {
    //             $data['shutter_speed'] = $this->normalizeShutterSpeed($exif['ExposureTime']);
    //         }
    //         if (isset($exif['Flash'])) {
    //             $data['flash_used'] = ($exif['Flash'] & 1) === 1;
    //         }

    //         // GPS Data
    //         if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])) {
    //             $data['gps'] = [
    //                 'latitude' => $this->convertGpsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N'),
    //                 'longitude' => $this->convertGpsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E'),
    //                 'altitude' => isset($exif['GPSAltitude']) ? (float) $exif['GPSAltitude'] : null,
    //             ];
    //         }

    //         return $data;

    //     } catch (Exception $e) {
    //         Log::debug('EXIF extraction failed', [
    //             'file' => $file->getClientOriginalName(),
    //             'error' => $e->getMessage(),
    //         ]);

    //         return ['available' => false, 'error' => $e->getMessage()];
    //     }
    // }

    /**
     * Extract EXIF data from image - Enhanced version.
     */
    protected function extractExifData(UploadedFile $file): array
    {
        try {
            $exif = @exif_read_data($file->getRealPath());

            if ($exif === false) {
                return ['available' => false];
            }

            $data = ['available' => true];

            // DateTime
            if (isset($exif['DateTimeOriginal'])) {
                $data['taken_at'] = $this->normalizeExifDate($exif['DateTimeOriginal']);
            } elseif (isset($exif['DateTime'])) {
                $data['taken_at'] = $this->normalizeExifDate($exif['DateTime']);
            }

            // Camera Info
            if (isset($exif['Make'])) {
                $data['camera_make'] = trim($exif['Make']);
            }
            if (isset($exif['Model'])) {
                $data['camera_model'] = trim($exif['Model']);
            }
            if (isset($exif['LensModel'])) {
                $data['lens_model'] = trim($exif['LensModel']);
            }

            // Camera Settings
            if (isset($exif['ISOSpeedRatings'])) {
                $data['iso'] = (int) $exif['ISOSpeedRatings'];
            }
            if (isset($exif['FNumber'])) {
                $data['aperture'] = $this->normalizeAperture($exif['FNumber']);
            }
            if (isset($exif['ExposureTime'])) {
                $data['shutter_speed'] = $this->normalizeShutterSpeed($exif['ExposureTime']);
            }
            if (isset($exif['ExposureProgram'])) {
                $data['exposure_program'] = $this->getExposureProgram($exif['ExposureProgram']);
            }
            if (isset($exif['WhiteBalance'])) {
                $data['white_balance'] = $exif['WhiteBalance'] == 0 ? 'Auto' : 'Manual';
            }
            if (isset($exif['FocalLength'])) {
                $focalLength = $exif['FocalLength'];
                if (is_array($focalLength) && isset($focalLength[0]) && isset($focalLength[1])) {
                    $data['focal_length'] = round($focalLength[0] / $focalLength[1], 1).'mm';
                } else {
                    $data['focal_length'] = $focalLength.'mm';
                }
            }
            if (isset($exif['Flash'])) {
                $data['flash_used'] = ($exif['Flash'] & 1) === 1;
                $data['flash_mode'] = $this->getFlashMode($exif['Flash']);
            }

            // GPS Data
            if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])) {
                $data['gps'] = [
                    'latitude' => $this->convertGpsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N'),
                    'longitude' => $this->convertGpsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E'),
                    'altitude' => isset($exif['GPSAltitude']) ? (float) $exif['GPSAltitude'] : null,
                    'timestamp' => isset($exif['GPSTimeStamp']) ? $this->normalizeGpsTimestamp($exif['GPSTimeStamp']) : null,
                ];
            }

            // Software
            if (isset($exif['Software'])) {
                $data['software'] = trim($exif['Software']);
            }

            // Copyright
            if (isset($exif['Copyright'])) {
                $data['copyright'] = trim($exif['Copyright']);
            }

            // Artist
            if (isset($exif['Artist'])) {
                $data['artist'] = trim($exif['Artist']);
            }

            return $data;

        } catch (Exception $e) {
            Log::debug('EXIF extraction failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            return ['available' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get exposure program name.
     */
    protected function getExposureProgram(int $program): string
    {
        $programs = [
            0 => 'Not Defined',
            1 => 'Manual',
            2 => 'Normal Program',
            3 => 'Aperture Priority',
            4 => 'Shutter Priority',
            5 => 'Creative Program',
            6 => 'Action Program',
            7 => 'Portrait Mode',
            8 => 'Landscape Mode',
        ];

        return $programs[$program] ?? 'Unknown';
    }

    /**
     * Get flash mode description.
     */
    protected function getFlashMode(int $flash): string
    {
        $modes = [
            0 => 'No Flash',
            1 => 'Fired',
            5 => 'Fired, Return not detected',
            7 => 'Fired, Return detected',
            9 => 'Compulsory Flash',
            13 => 'Compulsory Flash, Return not detected',
            15 => 'Compulsory Flash, Return detected',
            16 => 'Auto',
            24 => 'Auto, No Flash',
            25 => 'Auto, Fired',
            29 => 'Auto, Fired, Return not detected',
            31 => 'Auto, Fired, Return detected',
        ];

        return $modes[$flash] ?? 'Unknown';
    }

    /**
     * Normalize GPS timestamp.
     */
    protected function normalizeGpsTimestamp(array $timestamp): string
    {
        if (isset($timestamp[0]) && isset($timestamp[1]) && isset($timestamp[2])) {
            $hours = $this->gpsToFloat($timestamp[0]);
            $minutes = $this->gpsToFloat($timestamp[1]);
            $seconds = $this->gpsToFloat($timestamp[2]);

            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return 'N/A';
    }

    /**
     * Convert GPS coordinates to decimal format.
     */
    protected function convertGpsToDecimal(array $coords, string $ref): float
    {
        $degrees = $this->gpsToFloat($coords[0]);
        $minutes = $this->gpsToFloat($coords[1]);
        $seconds = $this->gpsToFloat($coords[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if (in_array($ref, ['S', 'W', 'South', 'West'])) {
            $decimal = -$decimal;
        }

        return round($decimal, 6);
    }

    /**
     * Convert GPS coordinate component to float.
     */
    protected function gpsToFloat($coord): float
    {
        if (is_array($coord)) {
            return (float) ($coord[0] / $coord[1]);
        }

        return (float) $coord;
    }

    /**
     * Normalize EXIF date format.
     */
    protected function normalizeExifDate(string $date): string
    {
        // Convert "2024:01:15 14:30:25" to "2024-01-15T14:30:25"
        return str_replace(' ', 'T', str_replace(':', '-', $date, 2));
    }

    /**
     * Normalize aperture value.
     */
    protected function normalizeAperture($value): string
    {
        if (is_array($value) && isset($value[0]) && isset($value[1])) {
            return 'f/'.round($value[0] / $value[1], 1);
        }

        return (string) $value;
    }

    /**
     * Normalize shutter speed.
     */
    protected function normalizeShutterSpeed($value): string
    {
        if (is_array($value) && isset($value[0]) && isset($value[1])) {
            if ($value[0] < $value[1]) {
                return '1/'.round($value[1] / $value[0]);
            }

            return round($value[0] / $value[1], 2).'s';
        }

        return (string) $value;
    }

    /**
     * Generate hashes for the image data.
     */
    protected function generateHashes(string $data): array
    {
        return [
            'sha256' => hash('sha256', $data),
            'md5' => hash('md5', $data),
        ];
    }

    /**
     * Generate unique filename for stored image.
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $timestamp = now()->timestamp;
        $random = uniqid();

        return $timestamp.'_'.$random.'.webp';
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Get upload metadata for the database.
     */
    public function getUploadMetadata(array $processedData, int $userId, array $requestInfo = []): array
    {
        $metadata = $processedData['metadata'];

        // Add upload information
        $metadata['upload'] = [
            'uploaded_by' => $userId,
            'source' => $requestInfo['source'] ?? 'Web',
            'browser' => $requestInfo['browser'] ?? null,
            'os' => $requestInfo['os'] ?? null,
            'device' => $requestInfo['device'] ?? null,
            'ip' => $requestInfo['ip'] ?? null,
        ];

        // Add audit information
        $metadata['audit'] = [
            'duplicate' => false,
            'duplicate_of' => null,
            'remarks' => null,
        ];

        return $metadata;
    }

    /**
     * Check if two images are duplicates (by hash comparison).
     */
    public function isDuplicate(string $hash1, string $hash2): bool
    {
        return $hash1 === $hash2;
    }

    /**
     * Get image dimensions from storage.
     */
    public function getImageDimensions(string $path): ?array
    {
        try {
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }

            $image = $this->imageManager->read(Storage::disk('public')->get($path));

            return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to get image dimensions', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
