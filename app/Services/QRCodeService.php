<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Support\Facades\Log;

class QRCodeService
{
    /**
     * Storage directory for QR codes
     */
    protected string $storageDir = 'qrcodes';

    /**
     * Normalize URL to use HTTPS scheme to avoid mixed-content errors
     */
    protected function normalizeUrlToHttps(string $url): string
    {
        // Force HTTPS scheme to avoid mixed-content errors
        if (Str::startsWith($url, 'http://')) {
            $url = preg_replace('/^http:/i', 'https:', $url);
        }
        return $url;
    }

    /**
     * Generate and save QR code for a charging point
     */
    public function generateAndSaveQRCode(int $chargingPointId): string
    {
        Log::info('Début génération QR code pour la borne ID: ' . $chargingPointId);

        // Générer l'URL de l'offre de réservation
        try {
            $offerUrl = route('public.charging-point.offer.reservation', $chargingPointId);
            Log::info("QR code content URL: {$offerUrl}");
        } catch (\Exception $routeException) {
            Log::error("Failed to generate offer URL for charging point ID {$chargingPointId}: " . $routeException->getMessage());
            throw new \RuntimeException("Impossible de générer l'URL de réservation pour la borne: " . $routeException->getMessage(), 0, $routeException);
        }

        // Delete existing QR codes first
        $this->deleteExistingQRCodes($chargingPointId);

        // Ensure qrcodes directory exists and is writable
        try {
            if (!Storage::disk('public')->exists($this->storageDir)) {
                Storage::disk('public')->makeDirectory($this->storageDir, 0755, true);
                Log::info("Created qrcodes directory: {$this->storageDir}");
            }
            
            // Verify directory is writable
            $storagePath = Storage::disk('public')->path($this->storageDir);
            if (!is_writable($storagePath)) {
                Log::error("QR code directory is not writable: {$storagePath}");
                throw new \RuntimeException("Le répertoire de stockage des QR codes n'est pas accessible en écriture: {$storagePath}");
            }
        } catch (\Exception $dirException) {
            Log::error("Failed to create or verify QR code directory: " . $dirException->getMessage());
            throw new \RuntimeException("Erreur lors de la création du répertoire de stockage: " . $dirException->getMessage(), 0, $dirException);
        }

        // Generate unique filename (default to SVG)
        $fileBase = 'charging_point_' . $chargingPointId . '_' . Str::random(8);
        $filePath = $this->storageDir . '/' . $fileBase . '.svg';
        Log::info("QR code file path (SVG): {$filePath}");

        try {
            // Generate QR code (SVG) using Endroid v5 Builder API
            $result = Builder::create()
                ->data($offerUrl)
                ->writer(new SvgWriter())
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(300)
                ->margin(10)
                ->build();
            $qrCodeImage = $result->getString();
            Log::info("QR code SVG image data generated successfully.");

            // Save QR code to storage with public visibility
            Storage::disk('public')->put($filePath, $qrCodeImage);
            Storage::disk('public')->setVisibility($filePath, 'public');
            Log::info("QR code SVG image saved to storage: {$filePath}");

            // Return public URL with HTTPS normalization
            $publicUrl = Storage::disk('public')->url($filePath);
            $publicUrl = $this->normalizeUrlToHttps($publicUrl);
            Log::info("Generated public QR code URL: {$publicUrl}");

            return $publicUrl;
        } catch (\Exception $e) {
            Log::warning("SVG generation failed for charging point ID {$chargingPointId}: " . $e->getMessage() . ". Trying PNG fallback.");

            // Fallback to PNG generation
            try {
                $offerUrl = route('public.charging-point.offer.reservation', $chargingPointId);

                $pngResult = Builder::create()
                    ->data($offerUrl)
                    ->writer(new PngWriter())
                    ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                    ->size(300)
                    ->margin(10)
                    ->build();
                $pngData = $pngResult->getString();

                $pngPath = $this->storageDir . '/' . $fileBase . '.png';
                Storage::disk('public')->put($pngPath, $pngData);
                Storage::disk('public')->setVisibility($pngPath, 'public');
                Log::info("QR code PNG image saved to storage: {$pngPath}");

                $publicUrl = Storage::disk('public')->url($pngPath);
                $publicUrl = $this->normalizeUrlToHttps($publicUrl);
                Log::info("Generated public QR code PNG URL: {$publicUrl}");

                return $publicUrl;
            } catch (\Exception $pngException) {
                Log::error("Both SVG and PNG QR code generation failed for charging point ID {$chargingPointId}: " . $pngException->getMessage());
                throw new \RuntimeException('Failed to generate or save QR code (SVG and PNG): ' . $pngException->getMessage(), 0, $pngException);
            }
        }
    }

    /**
     * Generate QR code as data URL (base64 encoded)
     */
    public function generateAsDataUrl(int $chargingPointId): string
    {
        try {
            $offerUrl = route('public.charging-point.offer.reservation', $chargingPointId);
            
            $result = Builder::create()
                ->data($offerUrl)
                ->writer(new SvgWriter())
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(300)
                ->margin(10)
                ->build();
            $qrCodeImage = $result->getString();

            // Correct MIME for SVG data URL
            return 'data:image/svg+xml;base64,' . base64_encode($qrCodeImage);
        } catch (\Exception $e) {
            Log::error("Error generating QR code data URL for charging point ID {$chargingPointId}: " . $e->getMessage());
            throw new \RuntimeException('Failed to generate QR code data URL: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Regenerate QR code for a charging point
     */
    public function regenerateQRCode(int $chargingPointId): string
    {
        // This will delete existing codes and generate new one
        return $this->generateAndSaveQRCode($chargingPointId);
    }

    /**
     * Get full path of QR code file
     */
    public function getQRCodeFullPath(int $chargingPointId): ?string
    {
        $pattern = 'charging_point_' . $chargingPointId . '_*.svg';
        $files = Storage::disk('public')->files($this->storageDir);

        foreach ($files as $file) {
            $basename = basename($file);
            if (Str::is($pattern, $basename)) {
                return Storage::disk('public')->path($file);
            }
        }

        return null;
    }

    /**
     * Check if QR code exists for a charging point
     */
    public function qrCodeExists(int $chargingPointId): bool
    {
        return $this->getQRCodeFullPath($chargingPointId) !== null;
    }

    /**
     * Get QR code URL from storage
     */
    public function getQRCodeUrl(int $chargingPointId): ?string
    {
        $pattern = 'charging_point_' . $chargingPointId . '_*.svg';
        $files = Storage::disk('public')->files($this->storageDir);

        foreach ($files as $file) {
            $basename = basename($file);
            if (Str::is($pattern, $basename)) {
                $url = Storage::disk('public')->url($file);
                // Normalize URL to use HTTPS scheme to avoid mixed-content errors
                return $this->normalizeUrlToHttps($url);
            }
        }

        return null;
    }

    /**
     * Delete existing QR codes for a charging point
     */
    /**
     * Delete existing QR codes for a charging point.
     * This method is protected as it's an internal helper for generation.
     */
    protected function deleteExistingQRCodes(int $chargingPointId): void
    {
        $pattern = 'charging_point_' . $chargingPointId . '_*.svg';
        $files = Storage::disk('public')->files($this->storageDir);

        foreach ($files as $file) {
            $basename = basename($file);
            if (Str::is($pattern, $basename)) {
                Storage::disk('public')->delete($file);
                Log::info("Deleted existing QR code file: {$file}");
            }
        }
    }

    /**
     * Public method to delete all QR codes associated with a charging point.
     */
    public function deleteQRCodes(int $chargingPointId): bool
    {
        try {
            $pattern = 'charging_point_' . $chargingPointId . '_*.svg';
            $files = Storage::disk('public')->files($this->storageDir);
            $deletedCount = 0;

            foreach ($files as $file) {
                $basename = basename($file);
                if (Str::is($pattern, $basename)) {
                    if (Storage::disk('public')->delete($file)) {
                        $deletedCount++;
                        Log::info("Successfully deleted QR code file: {$file}");
                    } else {
                        Log::warning("Failed to delete QR code file: {$file}");
                    }
                }
            }
            
            if ($deletedCount > 0) {
                Log::info("Total {$deletedCount} QR code files deleted for charging point ID: {$chargingPointId}");
                return true;
            } else {
                Log::info("No QR code files found to delete for charging point ID: {$chargingPointId}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error deleting QR codes for charging point ID {$chargingPointId}: " . $e->getMessage());
            return false;
        }
    }
}