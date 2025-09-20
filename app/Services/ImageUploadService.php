<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImageUploadService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const IMAGE_DIRECTORY = 'teacher-images';

    /**
     * Upload and store teacher profile image.
     *
     * @param UploadedFile $file
     * @param string|null $oldImagePath
     * @return string The stored image path
     * @throws InvalidArgumentException
     */
    public function uploadTeacherImage(UploadedFile $file, ?string $oldImagePath = null): string
    {
        $this->validateImage($file);

        // Delete old image if exists
        if ($oldImagePath) {
            $this->deleteImage($oldImagePath);
        }

        // Generate unique filename
        $filename = $this->generateUniqueFilename($file);
        
        // Store the image
        $path = $file->storeAs(self::IMAGE_DIRECTORY, $filename, 'public');

        return $path;
    }

    /**
     * Delete an image from storage.
     *
     * @param string $imagePath
     * @return bool
     */
    public function deleteImage(string $imagePath): bool
    {
        if (Storage::disk('public')->exists($imagePath)) {
            return Storage::disk('public')->delete($imagePath);
        }

        return true; // Consider it successful if file doesn't exist
    }

    /**
     * Get the full URL for an image.
     *
     * @param string|null $imagePath
     * @return string|null
     */
    public function getImageUrl(?string $imagePath): ?string
    {
        if (!$imagePath) {
            return null;
        }

        return Storage::disk('public')->url($imagePath);
    }

    /**
     * Validate uploaded image file.
     *
     * @param UploadedFile $file
     * @throws InvalidArgumentException
     */
    private function validateImage(UploadedFile $file): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new InvalidArgumentException('Invalid file upload.');
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('File size exceeds maximum allowed size of 5MB.');
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new InvalidArgumentException(
                'Invalid file type. Allowed types: ' . implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!str_starts_with($mimeType, 'image/')) {
            throw new InvalidArgumentException('File must be an image.');
        }
    }

    /**
     * Generate unique filename for the uploaded image.
     *
     * @param UploadedFile $file
     * @return string
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $randomString = Str::random(8);
        
        return "teacher_{$timestamp}_{$randomString}.{$extension}";
    }

    /**
     * Check if image exists in storage.
     *
     * @param string $imagePath
     * @return bool
     */
    public function imageExists(string $imagePath): bool
    {
        return Storage::disk('public')->exists($imagePath);
    }
}