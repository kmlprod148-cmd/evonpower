<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QRCodeServiceImproved
{
    private string $storageDir = 'qrcodes';

    /**
     * Generate and save QR code with improved error handling
     */
    public function generateAndSaveQRCode(int $chargingPointId): string
    {
        Log::info("🚀 Début génération QR code pour borne ID: {$chargingPointId}");

        try {
            // 1. Validate charging point exists
            $chargingPoint = \App\Models\ChargingPoint::find($chargingPointId);
            if (!$chargingPoint) {
                throw new \InvalidArgumentException("Borne de recharge ID {$chargingPointId} non trouvée");
            }

            // 2. Generate offer URL with proper domain
            $offerUrl = $this->generateOfferUrl($chargingPointId);
            Log::info("📋 URL générée: {$offerUrl}");

            // 3. Clean up existing QR codes
            $this->deleteExistingQRCodes($chargingPointId);

            // 4. Ensure directory exists
            if (!Storage::disk('public')->exists($this->storageDir)) {
                Storage::disk('public')->makeDirectory($this->storageDir);
                Log::info("📁 Dossier créé: {$this->storageDir}");
            }

            // 5. Generate unique filename
            $fileName = 'charging_point_' . $chargingPointId . '_' . Str::random(8) . '.svg';
            $filePath = $this->storageDir . '/' . $fileName;
            Log::info("📄 Fichier: {$filePath}");

            // 6. Generate QR code with multiple fallbacks
            $qrCodeImage = $this->generateQRCodeImage($offerUrl);

            // 7. Save to storage
            Storage::disk('public')->put($filePath, $qrCodeImage);
            Log::info("💾 QR code sauvegardé: {$filePath}");

            // 8. Generate public URL
            $publicUrl = Storage::disk('public')->url($filePath);
            $publicUrl = $this->normalizeUrlToHttps($publicUrl);
            Log::info("🌐 URL publique: {$publicUrl}");

            // 9. Update charging point with QR code URL
            $chargingPoint->update(['qr_code_url' => $publicUrl]);
            Log::info("✅ Borne mise à jour avec QR code URL");

            return $publicUrl;

        } catch (\Exception $e) {
            Log::error("❌ Erreur génération QR code pour borne {$chargingPointId}: " . $e->getMessage());
            Log::error("📋 Stack trace: " . $e->getTraceAsString());
            
            // Try fallback generation
            return $this->generateFallbackQRCode($chargingPointId);
        }
    }

    /**
     * Generate QR code image with multiple fallback methods
     */
    private function generateQRCodeImage(string $content): string
    {
        try {
            // Method 1: Standard SVG generation
            $qrCode = QrCode::create($content)
                ->setSize(300)
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setMargin(10)
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $writer = new SvgWriter();
            $result = $writer->write($qrCode);
            $qrCodeImage = $result->getString();
            
            Log::info("✅ QR code SVG généré avec succès");
            return $qrCodeImage;

        } catch (\Exception $e) {
            Log::error("❌ Erreur génération SVG: " . $e->getMessage());
            
            // Method 2: Try with different settings
            try {
                $qrCode = QrCode::create($content)
                    ->setSize(200)
                    ->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium)
                    ->setMargin(5);

                $writer = new SvgWriter();
                $result = $writer->write($qrCode);
                $qrCodeImage = $result->getString();
                
                Log::info("✅ QR code SVG généré avec paramètres alternatifs");
                return $qrCodeImage;

            } catch (\Exception $e2) {
                Log::error("❌ Erreur génération alternative: " . $e2->getMessage());
                throw new \RuntimeException("Impossible de générer le QR code: " . $e->getMessage());
            }
        }
    }

    /**
     * Generate offer URL with proper domain handling
     */
    private function generateOfferUrl(int $chargingPointId): string
    {
        try {
            // Try to generate route normally
            $offerUrl = route('public.charging-point.offer.reservation', $chargingPointId);
            
            // If URL contains localhost, replace with proper domain
            if (str_contains($offerUrl, 'localhost')) {
                $baseUrl = config('app.url', 'http://127.0.0.1:8000');
                $offerUrl = str_replace('http://localhost', $baseUrl, $offerUrl);
                $offerUrl = str_replace('https://localhost', $baseUrl, $offerUrl);
            }
            
            return $offerUrl;
            
        } catch (\Exception $e) {
            Log::warning("⚠️ Erreur génération route, utilisation URL manuelle");
            
            // Fallback: construct URL manually
            $baseUrl = config('app.url', 'http://127.0.0.1:8000');
            return rtrim($baseUrl, '/') . "/offer/{$chargingPointId}/reservation";
        }
    }

    /**
     * Fallback QR code generation using external service
     */
    private function generateFallbackQRCode(int $chargingPointId): string
    {
        try {
            $offerUrl = $this->generateOfferUrl($chargingPointId);
            $fallbackUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($offerUrl);
            
            Log::info("🔄 Utilisation service QR code externe: {$fallbackUrl}");
            
            // Update charging point with fallback URL
            $chargingPoint = \App\Models\ChargingPoint::find($chargingPointId);
            if ($chargingPoint) {
                $chargingPoint->update(['qr_code_url' => $fallbackUrl]);
            }
            
            return $fallbackUrl;
            
        } catch (\Exception $e) {
            Log::error("❌ Erreur service externe: " . $e->getMessage());
            return "https://via.placeholder.com/300x300?text=QR+Code+Error";
        }
    }

    /**
     * Delete existing QR codes for a charging point
     */
    private function deleteExistingQRCodes(int $chargingPointId): void
    {
        $pattern = 'charging_point_' . $chargingPointId . '_*.svg';
        $files = Storage::disk('public')->files($this->storageDir);

        foreach ($files as $file) {
            $basename = basename($file);
            if (Str::is($pattern, $basename)) {
                Storage::disk('public')->delete($file);
                Log::info("🗑️ QR code existant supprimé: {$file}");
            }
        }
    }

    /**
     * Normalize URL to use HTTPS scheme to avoid mixed-content errors
     */
    private function normalizeUrlToHttps(string $url): string
    {
        if (Str::startsWith($url, 'http://')) {
            $url = preg_replace('/^http:/i', 'https:', $url);
        }
        return $url;
    }

    /**
     * Generate QR code as data URL (base64 encoded)
     */
    public function generateAsDataUrl(int $chargingPointId): string
    {
        try {
            $offerUrl = $this->generateOfferUrl($chargingPointId);
            
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
            Log::error("❌ Erreur génération data URL pour borne {$chargingPointId}: " . $e->getMessage());
            
            // Fallback: use external QR code service
            $offerUrl = $this->generateOfferUrl($chargingPointId);
            return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($offerUrl);
        }
    }

    /**
     * Check if QR code exists for a charging point
     */
    public function qrCodeExists(int $chargingPointId): bool
    {
        return $this->getQRCodeUrl($chargingPointId) !== null;
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
            $offerUrl = $this->generateOfferUrl($chargingPointId);
            $results['route_exists'] = !empty($offerUrl);
            $results['url_generated'] = $offerUrl;

            // Test QR code generation
            $qrCodeImage = $this->generateQRCodeImage($offerUrl);
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