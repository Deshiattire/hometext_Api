<?php

namespace App\Services;

use App\Models\CategoryImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

/**
 * ImageService
 * 
 * Handles image upload, processing, and storage for categories
 * Follows industry best practices: single responsibility, dependency injection, error handling
 */
class ImageService
{
    /**
     * Default image configurations
     */
    private const DEFAULT_WIDTH = 800;
    private const DEFAULT_HEIGHT = 800;
    private const DEFAULT_QUALITY = 85;
    
    private const THUMBNAIL_WIDTH = 200;
    private const THUMBNAIL_HEIGHT = 200;
    private const THUMBNAIL_QUALITY = 75;

    /**
     * Storage disk
     */
    private string $disk;

    /**
     * Base upload path
     */
    private string $basePath;

    /**
     * Constructor
     *
     * @param string $disk
     * @param string $basePath
     */
    public function __construct(string $disk = 'public', string $basePath = 'categories')
    {
        $this->disk = $disk;
        $this->basePath = $basePath;
    }

    /**
     * Upload and process a single image
     *
     * @param UploadedFile $file
     * @param string $name
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function uploadImage(
        UploadedFile $file,
        string $name,
        array $options = []
    ): array {
        $width = $options['width'] ?? self::DEFAULT_WIDTH;
        $height = $options['height'] ?? self::DEFAULT_HEIGHT;
        $quality = $options['quality'] ?? self::DEFAULT_QUALITY;
        $generateThumbnail = $options['thumbnail'] ?? true;

        // Generate unique filename
        $filename = $this->generateFilename($name, $file->getClientOriginalExtension());
        $path = $this->getPath($filename);

        // Process and save main image
        $image = Image::make($file);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Resize maintaining aspect ratio
        $image->fit($width, $height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Save to storage
        Storage::disk($this->disk)->put($path, (string) $image->encode('jpg', $quality));

        $result = [
            'image_path' => $path,
            'width' => $image->width(),
            'height' => $image->height(),
            'original_width' => $originalWidth,
            'original_height' => $originalHeight,
            'file_size' => Storage::disk($this->disk)->size($path),
            'mime_type' => $image->mime(),
            'storage_disk' => $this->disk,
        ];

        // Generate thumbnail if requested
        if ($generateThumbnail) {
            $thumbnail = $this->generateThumbnail($file, $name);
            $result['thumbnail'] = $thumbnail;
        }

        return $result;
    }

    /**
     * Upload multiple images
     *
     * @param array $files
     * @param string $baseName
     * @param array $options
     * @return array
     */
    public function uploadMultipleImages(
        array $files,
        string $baseName,
        array $options = []
    ): array {
        $results = [];

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $name = $baseName . '-' . ($index + 1);
                $results[] = $this->uploadImage($file, $name, $options);
            }
        }

        return $results;
    }

    /**
     * Generate thumbnail
     *
     * @param UploadedFile $file
     * @param string $name
     * @return array
     */
    private function generateThumbnail(UploadedFile $file, string $name): array
    {
        $filename = 'thumb-' . $this->generateFilename($name, $file->getClientOriginalExtension());
        $path = $this->getPath($filename);

        $image = Image::make($file);
        $image->fit(
            self::THUMBNAIL_WIDTH,
            self::THUMBNAIL_HEIGHT,
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );

        Storage::disk($this->disk)->put(
            $path,
            (string) $image->encode('jpg', self::THUMBNAIL_QUALITY)
        );

        return [
            'image_path' => $path,
            'width' => $image->width(),
            'height' => $image->height(),
            'file_size' => Storage::disk($this->disk)->size($path),
        ];
    }

    /**
     * Delete an image
     *
     * @param string $imagePath
     * @return bool
     */
    public function deleteImage(string $imagePath): bool
    {
        if (Storage::disk($this->disk)->exists($imagePath)) {
            return Storage::disk($this->disk)->delete($imagePath);
        }

        return false;
    }

    /**
     * Delete multiple images
     *
     * @param array $imagePaths
     * @return int Number of deleted images
     */
    public function deleteMultipleImages(array $imagePaths): int
    {
        $deleted = 0;

        foreach ($imagePaths as $path) {
            if ($this->deleteImage($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get image URL
     *
     * @param string $imagePath
     * @return string
     */
    public function getImageUrl(string $imagePath): string
    {
        if ($this->disk === 'public' || $this->disk === 'local') {
            return Storage::disk($this->disk)->url($imagePath);
        }

        return Storage::disk($this->disk)->url($imagePath);
    }

    /**
     * Save category image to database
     *
     * @param int $categoryId
     * @param array $imageData
     * @param array $options
     * @return CategoryImage
     */
    public function saveCategoryImage(
        int $categoryId,
        array $imageData,
        array $options = []
    ): CategoryImage {
        // If there's a primary image, unset others
        if ($options['is_primary'] ?? false) {
            CategoryImage::where('category_id', $categoryId)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        return CategoryImage::create([
            'category_id' => $categoryId,
            'image_path' => $imageData['image_path'],
            'image_type' => $options['image_type'] ?? CategoryImage::TYPE_PRIMARY,
            'alt_text' => $options['alt_text'] ?? null,
            'width' => $imageData['width'] ?? null,
            'height' => $imageData['height'] ?? null,
            'file_size' => $imageData['file_size'] ?? null,
            'position' => $options['position'] ?? 0,
            'is_primary' => $options['is_primary'] ?? false,
            'storage_disk' => $imageData['storage_disk'] ?? $this->disk,
            'mime_type' => $imageData['mime_type'] ?? null,
        ]);
    }

    /**
     * Generate unique filename
     *
     * @param string $name
     * @param string $extension
     * @return string
     */
    private function generateFilename(string $name, string $extension = 'jpg'): string
    {
        $slug = Str::slug($name);
        $timestamp = now()->format('YmdHis');
        $random = Str::random(6);

        return "{$slug}-{$timestamp}-{$random}.{$extension}";
    }

    /**
     * Get full path for image
     *
     * @param string $filename
     * @return string
     */
    private function getPath(string $filename): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');

        return "{$this->basePath}/{$year}/{$month}/{$filename}";
    }

    /**
     * Validate image file
     *
     * @param UploadedFile $file
     * @param array $rules
     * @return bool
     */
    public function validateImage(UploadedFile $file, array $rules = []): bool
    {
        $maxSize = $rules['max_size'] ?? 5120; // 5MB default
        $allowedMimes = $rules['mimes'] ?? ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if ($file->getSize() > $maxSize * 1024) {
            return false;
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        return true;
    }
}


