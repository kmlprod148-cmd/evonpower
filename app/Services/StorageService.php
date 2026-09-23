<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StorageService
{
    /**
     * Ensure a directory exists in storage
     */
    public function ensureDirectoryExists(string $path, string $disk = 'public'): bool
    {
        try {
            if (!Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->makeDirectory($path);
                Log::info("Directory created: {$path}");
                return true;
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create directory {$path}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store a file in storage
     */
    public function storeFile(string $path, $content, string $disk = 'public'): bool
    {
        try {
            Storage::disk($disk)->put($path, $content);
            Log::info("File stored: {$path}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to store file {$path}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file URL from storage
     */
    public function getFileUrl(string $path, string $disk = 'public'): string
    {
        try {
            return Storage::disk($disk)->url($path);
        } catch (\Exception $e) {
            Log::error("Failed to get file URL {$path}: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Delete a file from storage
     */
    public function deleteFile(string $path, string $disk = 'public'): bool
    {
        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                Log::info("File deleted: {$path}");
                return true;
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete file {$path}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * List files in a directory
     */
    public function listFiles(string $path, string $disk = 'public'): array
    {
        try {
            return Storage::disk($disk)->files($path);
        } catch (\Exception $e) {
            Log::error("Failed to list files in {$path}: " . $e->getMessage());
            return [];
        }
    }
}
