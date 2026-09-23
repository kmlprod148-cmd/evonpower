<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QrCodeStorageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Ensure QR code storage directory exists
        $this->ensureQrCodeStorageExists();
    }

    /**
     * Ensure QR code storage directory exists
     */
    private function ensureQrCodeStorageExists(): void
    {
        try {
            // Check if fileinfo extension is available
            if (!extension_loaded('fileinfo')) {
                Log::warning('PHP fileinfo extension not loaded. QR code storage creation skipped.');
                return;
            }
            
            $qrcodesDir = 'qrcodes';
            
            // Check if qrcodes directory exists in public storage
            if (!Storage::disk('public')->exists($qrcodesDir)) {
                Storage::disk('public')->makeDirectory($qrcodesDir);
                Log::info('QR code storage directory created: ' . $qrcodesDir);
            }
            
            // Also ensure the physical directory exists
            $physicalPath = storage_path('app/public/' . $qrcodesDir);
            if (!is_dir($physicalPath)) {
                mkdir($physicalPath, 0755, true);
                Log::info('QR code physical directory created: ' . $physicalPath);
            }
            
        } catch (\Throwable $e) {
            // Catch both Exceptions and Errors (like "Class finfo not found")
            Log::error('Failed to ensure QR code storage exists: ' . $e->getMessage());
            // Don't re-throw - allow application to continue
        }
    }
}
