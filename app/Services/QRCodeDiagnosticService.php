<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QRCodeDiagnosticService
{
    private string $storageDir = 'qrcodes';

    /**
     * Diagnose QR code generation issues
     */
    public function diagnose(): array
    {
        $diagnostics = [];

        // 1. Check if Endroid QR Code library is available
        try {
            $qrCode = QrCode::create('test');
            $diagnostics['endroid_library'] = [
                'status' => 'OK',
                'message' => 'Endroid QR Code library is available'
            ];
        } catch (\Exception $e) {
            $diagnostics['endroid_library'] = [
                'status' => 'ERROR',
                'message' => 'Endroid QR Code library error: ' . $e->getMessage()
            ];
        }

        // 2. Check storage permissions
        try {
            $testFile = 'test_' . Str::random(8) . '.txt';
            $testPath = $this->storageDir . '/' . $testFile;
            
            if (!Storage::disk('public')->exists($this->storageDir)) {
                Storage::disk('public')->makeDirectory($this->storageDir);
            }
            
            Storage::disk('public')->put($testPath, 'test content');
            Storage::disk('public')->delete($testPath);
            
            $diagnostics['storage_permissions'] = [
                'status' => 'OK',
                'message' => 'Storage permissions are working'
            ];
        } catch (\Exception $e) {
            $diagnostics['storage_permissions'] = [
                'status' => 'ERROR',
                'message' => 'Storage permissions error: ' . $e->getMessage()
            ];
        }

        // 3. Check if route exists
        try {
            $testUrl = route('public.charging-point.offer.reservation', 1);
            $diagnostics['route_exists'] = [
                'status' => 'OK',
                'message' => 'Route exists: ' . $testUrl
            ];
        } catch (\Exception $e) {
            $diagnostics['route_exists'] = [
                'status' => 'ERROR',
                'message' => 'Route error: ' . $e->getMessage()
            ];
        }

        // 4. Test QR code generation
        try {
            $testUrl = 'https://example.com/test';
            $qrCode = QrCode::create($testUrl)
                ->setSize(300)
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setMargin(10)
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $writer = new SvgWriter();
            $result = $writer->write($qrCode);
            $qrCodeImage = $result->getString();
            
            $diagnostics['qr_generation'] = [
                'status' => 'OK',
                'message' => 'QR code generation works'
            ];
        } catch (\Exception $e) {
            $diagnostics['qr_generation'] = [
                'status' => 'ERROR',
                'message' => 'QR code generation error: ' . $e->getMessage()
            ];
        }

        // 5. Test file saving
        try {
            $testUrl = 'https://example.com/test';
            $qrCode = QrCode::create($testUrl)
                ->setSize(300)
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setMargin(10)
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $writer = new SvgWriter();
            $result = $writer->write($qrCode);
            $qrCodeImage = $result->getString();
            
            $fileName = 'diagnostic_test_' . Str::random(8) . '.svg';
            $filePath = $this->storageDir . '/' . $fileName;
            
            Storage::disk('public')->put($filePath, $qrCodeImage);
            $publicUrl = Storage::disk('public')->url($filePath);
            
            // Clean up test file
            Storage::disk('public')->delete($filePath);
            
            $diagnostics['file_saving'] = [
                'status' => 'OK',
                'message' => 'File saving works, test URL: ' . $publicUrl
            ];
        } catch (\Exception $e) {
            $diagnostics['file_saving'] = [
                'status' => 'ERROR',
                'message' => 'File saving error: ' . $e->getMessage()
            ];
        }

        return $diagnostics;
    }

    /**
     * Generate a simple test QR code
     */
    public function generateTestQRCode(): array
    {
        try {
            $testUrl = 'https://example.com/test-qr-code';
            
            // Generate QR code
            $qrCode = QrCode::create($testUrl)
                ->setSize(300)
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setMargin(10)
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $writer = new SvgWriter();
            $result = $writer->write($qrCode);
            $qrCodeImage = $result->getString();
            
            // Save to storage
            $fileName = 'test_qr_' . Str::random(8) . '.svg';
            $filePath = $this->storageDir . '/' . $fileName;
            
            Storage::disk('public')->put($filePath, $qrCodeImage);
            $publicUrl = Storage::disk('public')->url($filePath);
            
            return [
                'success' => true,
                'url' => $publicUrl,
                'file_path' => $filePath,
                'content' => $testUrl
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
}
