<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Illuminate\Support\Facades\Log;

class QRCodeServiceFixed
{
    protected string $storageDir = 'qrcodes';

    /**
     * Generate and save QR code for a charging point
     */
    public function generateAndSaveQRCode(int $chargingPointId): string
    {
        Log::info('🔧 Début génération QR code pour la borne ID: ' . $chargingPointId);

        try {
            // Générer l'URL de l'offre de réservation
            $offerUrl = route('public.charging-point.offer.reservation', $chargingPointId);
            Log::info("📱 QR code content URL: {$offerUrl}");

            // Vérifier que la route existe
            if (!$offerUrl) {
                throw new \RuntimeException('Route not found for charging point offer');
            }

            // Delete existing QR codes first
            $this->deleteExistingQRCodes($chargingPointId);

            // Ensure qrcodes directory exists
            if (!Storage::disk('public')->exists($this->storageDir)) {
                Storage::disk('public')->makeDirectory($this->storageDir);
                Log::info("📁 Created qrcodes directory: {$this->storageDir}");
            }

            // Generate unique filename
            $fileName = 'charging_point_' . $chargingPointId . '_' . Str::random(8) . '.svg';
            $filePath = $this->storageDir . '/' . $fileName;
            Log::info("📄 QR code file path: {$filePath}");

            // Generate QR code using Endroid library with SVG backend
            $qrCode = QrCode::create($offerUrl)
                ->setSize(300)
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setMargin(10)
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $writer = new SvgWriter();
            $result = $writer->write($qrCode);
            $qrCodeImage = $result->getString();
            
            Log::info("✅ QR code SVG generated successfully");

            // Save QR code to storage
            Storage::disk('public')->put($filePath, $qrCodeImage);
            Log::info("💾 QR code saved to storage: {$filePath}");

            // Return public URL with HTTPS normalization
            $publicUrl = Storage::disk('public')->url($filePath);
            $publicUrl = $this->normalizeUrlToHttps($publicUrl);
            Log::info("🌐 Generated public QR code URL: {$publicUrl}");

            return $publicUrl;

        } catch (\Exception $e) {
            Log::error("❌ Error generating QR code for charging point ID {$chargingPointId}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            // Fallback: generate a simple QR code using external service
            return $this->generateFallbackQRCode($chargingPointId);
        }
    }

    /**
     * Generate QR code as data URL (base64 encoded)
     */
    public function generateAsDataUrl(int $chargingPointId): string
    {
        try {
            $offerUrl = route('public.charging-point.offer.reservation', $chargingPointId);
            
            if (!$offerUrl) {
                throw new \RuntimeException('Route not found for charging point offer');
            }
            
            $qrCode = QrCode::create($offerUrl)
                ->setSize(300)
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setMargin(10)
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $writer = new SvgWriter();
            $result = $writer->write($qrCode);
            $qrCodeImage = $result->getString();

            return 'data:image/svg+xml;base64,' . base64_encode($qrCodeImage);
        } catch (\Exception $e) {
            Log::error("❌ Error generating QR code data URL for charging point ID {$chargingPointId}: " . $e->getMessage());
            
            // Fallback: use external QR code service
            $offerUrl = route('public.charging-point.offer.reservation', $chargingPointId);
            return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($offerUrl);
        }
    }

    /**
     * Fallback QR code generation using external service
     */
    protected function generateFallbackQRCode(int $chargingPointId): string
    {
        try {
            $offerUrl = route('public.charging-point.offer.reservation', $chargingPointId);
            $fallbackUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($offerUrl);
            
            Log::info("🔄 Using fallback QR code service: {$fallbackUrl}");
            return $fallbackUrl;
        } catch (\Exception $e) {
            Log::error("❌ Fallback QR code generation failed: " . $e->getMessage());
            return "https://via.placeholder.com/300x300?text=QR+Code+Error";
        }
    }

    /**
     * Regenerate QR code for a charging point
     */
    public function regenerateQRCode(int $chargingPointId): string
    {
        Log::info("🔄 Regenerating QR code for charging point ID: {$chargingPointId}");
        return $this->generateAndSaveQRCode($chargingPointId);
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
                return $this->normalizeUrlToHttps($url);
            }
        }

        return null;
    }

    /**
     * Check if QR code exists for a charging point
     */
    public function qrCodeExists(int $chargingPointId): bool
    {
        return $this->getQRCodeUrl($chargingPointId) !== null;
    }

    /**
     * Delete existing QR codes for a charging point
     */
    protected function deleteExistingQRCodes(int $chargingPointId): void
    {
        $pattern = 'charging_point_' . $chargingPointId . '_*.svg';
        $files = Storage::disk('public')->files($this->storageDir);

        foreach ($files as $file) {
            $basename = basename($file);
            if (Str::is($pattern, $basename)) {
                Storage::disk('public')->delete($file);
                Log::info("🗑️ Deleted existing QR code file: {$file}");
            }
        }
    }

    /**
     * Public method to delete all QR codes associated with a charging point
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
                        Log::info("🗑️ Successfully deleted QR code file: {$file}");
                    } else {
                        Log::warning("⚠️ Failed to delete QR code file: {$file}");
                    }
                }
            }
            
            if ($deletedCount > 0) {
                Log::info("✅ Total {$deletedCount} QR code files deleted for charging point ID: {$chargingPointId}");
                return true;
            } else {
                Log::info("ℹ️ No QR code files found to delete for charging point ID: {$chargingPointId}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("❌ Error deleting QR codes for charging point ID {$chargingPointId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalize URL to use HTTPS scheme to avoid mixed-content errors
     */
    protected function normalizeUrlToHttps(string $url): string
    {
        if (Str::startsWith($url, 'http://')) {
            $url = preg_replace('/^http:/i', 'https:', $url);
        }
        return $url;
    }

    /**
     * Test QR code generation
     */
    public function testQRCodeGeneration(int $chargingPointId): array
    {
        $results = [
            'charging_point_id' => $chargingPointId,
            'route_exists' => false,
            'url_generated' => false,
            'qr_code_created' => false,
            'file_saved' => false,
            'public_url' => null,
            'errors' => []
        ];

        try {
            // Test route
            $offerUrl = route('public.charging-point.offer.reservation', $chargingPointId);
            $results['route_exists'] = !empty($offerUrl);
            $results['url_generated'] = $offerUrl;

            // Test QR code generation
            $qrCode = QrCode::create($offerUrl)
                ->setSize(300)
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setMargin(10)
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $writer = new SvgWriter();
            $result = $writer->write($qrCode);
            $qrCodeImage = $result->getString();
            $results['qr_code_created'] = !empty($qrCodeImage);

            // Test file saving
            $fileName = 'test_charging_point_' . $chargingPointId . '_' . Str::random(8) . '.svg';
            $filePath = $this->storageDir . '/' . $fileName;
            
            if (!Storage::disk('public')->exists($this->storageDir)) {
                Storage::disk('public')->makeDirectory($this->storageDir);
            }
            
            $saved = Storage::disk('public')->put($filePath, $qrCodeImage);
            $results['file_saved'] = $saved;
            
            if ($saved) {
                $results['public_url'] = Storage::disk('public')->url($filePath);
                // Clean up test file
                Storage::disk('public')->delete($filePath);
            }

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }
}
