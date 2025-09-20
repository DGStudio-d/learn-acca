<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FileOptimizationService
{
    const IMAGE_QUALITY = 85;
    const MAX_IMAGE_WIDTH = 800;
    const MAX_IMAGE_HEIGHT = 600;
    const THUMBNAIL_SIZE = 150;

    /**
     * Optimize and store an uploaded image.
     */
    public function optimizeAndStoreImage(UploadedFile $file, string $directory = 'images'): array
    {
        $filename = $this->generateUniqueFilename($file);
        $path = "{$directory}/{$filename}";
        $thumbnailPath = "{$directory}/thumbnails/{$filename}";

        // For now, store the original file and create a simple thumbnail
        // In production, you would use a package like Intervention Image
        Storage::putFileAs($directory, $file, $filename);
        
        // Create a simple thumbnail by copying the original
        // In production, this would be properly resized
        Storage::copy($path, $thumbnailPath);

        return [
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'size' => Storage::size($path),
            'mime_type' => $file->getMimeType()
        ];
    }

    /**
     * Store quiz file with compression if applicable.
     */
    public function storeQuizFile(UploadedFile $file, string $directory = 'quizzes'): array
    {
        $filename = $this->generateUniqueFilename($file);
        $path = "{$directory}/{$filename}";

        // For PDF files, store as-is but validate size
        if ($file->getMimeType() === 'application/pdf') {
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file->getSize() > $maxSize) {
                throw new \Exception('PDF file size exceeds 10MB limit');
            }
        }

        // Store the file
        Storage::putFileAs($directory, $file, $filename);

        return [
            'path' => $path,
            'size' => Storage::size($path),
            'mime_type' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName()
        ];
    }

    /**
     * Generate a unique filename while preserving extension.
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $hash = hash('sha256', $file->getContent());
        $timestamp = now()->format('YmdHis');
        
        return "{$timestamp}_{$hash}.{$extension}";
    }

    /**
     * Get optimized file URL with CDN support.
     */
    public function getOptimizedUrl(string $path, array $options = []): string
    {
        // If using S3 or CDN, add optimization parameters
        if (config('filesystems.default') === 's3') {
            return $this->getS3OptimizedUrl($path, $options);
        }

        return Storage::url($path);
    }

    /**
     * Get S3 URL with CloudFront optimization parameters.
     */
    private function getS3OptimizedUrl(string $path, array $options = []): string
    {
        $url = Storage::url($path);
        
        // Add CloudFront image optimization parameters if available
        if (!empty($options)) {
            $params = [];
            
            if (isset($options['width'])) {
                $params[] = "w={$options['width']}";
            }
            
            if (isset($options['height'])) {
                $params[] = "h={$options['height']}";
            }
            
            if (isset($options['quality'])) {
                $params[] = "q={$options['quality']}";
            }
            
            if (!empty($params)) {
                $url .= '?' . implode('&', $params);
            }
        }
        
        return $url;
    }

    /**
     * Clean up old files based on age.
     */
    public function cleanupOldFiles(string $directory, int $daysOld = 30): int
    {
        $files = Storage::files($directory);
        $deletedCount = 0;
        $cutoffDate = now()->subDays($daysOld);

        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            
            if ($lastModified < $cutoffDate->timestamp) {
                Storage::delete($file);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get file size in human readable format.
     */
    public function getHumanReadableSize(string $path): string
    {
        $bytes = Storage::size($path);
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Validate file type and size.
     */
    public function validateFile(UploadedFile $file, array $allowedTypes, int $maxSize): bool
    {
        // Check file type
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }

        // Check file size
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size of ' . $this->formatBytes($maxSize));
        }

        return true;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}