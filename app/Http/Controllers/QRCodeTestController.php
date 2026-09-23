<?php

namespace App\Http\Controllers;

use App\Services\QRCodeServiceImproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QRCodeTestController extends Controller
{
    protected QRCodeServiceImproved $qrCodeService;

    public function __construct(QRCodeServiceImproved $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Test QR code generation for a specific charging point
     */
    public function testQRCode($id)
    {
        try {
            Log::info("🧪 Testing QR code generation for charging point ID: {$id}");

            // Test the QR code generation
            $testResults = $this->qrCodeService->testQRCodeGeneration($id);
            
            return response()->json([
                'success' => true,
                'charging_point_id' => $id,
                'test_results' => $testResults,
                'message' => 'QR code test completed'
            ]);

        } catch (\Exception $e) {
            Log::error("❌ QR code test failed for charging point ID {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'charging_point_id' => $id,
                'error' => $e->getMessage(),
                'message' => 'QR code test failed'
            ], 500);
        }
    }

    /**
     * Generate QR code for a specific charging point
     */
    public function generateQRCode($id)
    {
        try {
            Log::info("🔧 Generating QR code for charging point ID: {$id}");

            // Generate QR code as data URL
            $qrCodeDataUrl = $this->qrCodeService->generateAsDataUrl($id);
            
            // Generate QR code as file
            $qrCodeUrl = $this->qrCodeService->generateAndSaveQRCode($id);

            return response()->json([
                'success' => true,
                'charging_point_id' => $id,
                'qr_code_data_url' => $qrCodeDataUrl,
                'qr_code_url' => $qrCodeUrl,
                'message' => 'QR code generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("❌ QR code generation failed for charging point ID {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'charging_point_id' => $id,
                'error' => $e->getMessage(),
                'message' => 'QR code generation failed'
            ], 500);
        }
    }

    /**
     * Show QR code for a specific charging point
     */
    public function showQRCode($id)
    {
        try {
            // Check if QR code exists
            if ($this->qrCodeService->qrCodeExists($id)) {
                $qrCodeUrl = $this->qrCodeService->getQRCodeUrl($id);
            } else {
                // Generate new QR code
                $qrCodeUrl = $this->qrCodeService->generateAndSaveQRCode($id);
            }

            return view('qr-code.show', [
                'charging_point_id' => $id,
                'qr_code_url' => $qrCodeUrl
            ]);

        } catch (\Exception $e) {
            Log::error("❌ QR code display failed for charging point ID {$id}: " . $e->getMessage());
            
            return view('qr-code.error', [
                'charging_point_id' => $id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Regenerate QR code for a specific charging point
     */
    public function regenerateQRCode($id)
    {
        try {
            Log::info("🔄 Regenerating QR code for charging point ID: {$id}");

            // Regenerate QR code
            $qrCodeUrl = $this->qrCodeService->regenerateQRCode($id);

            return response()->json([
                'success' => true,
                'charging_point_id' => $id,
                'qr_code_url' => $qrCodeUrl,
                'message' => 'QR code regenerated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("❌ QR code regeneration failed for charging point ID {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'charging_point_id' => $id,
                'error' => $e->getMessage(),
                'message' => 'QR code regeneration failed'
            ], 500);
        }
    }

    /**
     * Delete QR code for a specific charging point
     */
    public function deleteQRCode($id)
    {
        try {
            Log::info("🗑️ Deleting QR code for charging point ID: {$id}");

            // Delete QR code
            $deleted = $this->qrCodeService->deleteQRCodes($id);

            return response()->json([
                'success' => $deleted,
                'charging_point_id' => $id,
                'message' => $deleted ? 'QR code deleted successfully' : 'No QR code found to delete'
            ]);

        } catch (\Exception $e) {
            Log::error("❌ QR code deletion failed for charging point ID {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'charging_point_id' => $id,
                'error' => $e->getMessage(),
                'message' => 'QR code deletion failed'
            ], 500);
        }
    }

    /**
     * Get QR code status for a specific charging point
     */
    public function getQRCodeStatus($id)
    {
        try {
            $exists = $this->qrCodeService->qrCodeExists($id);
            $url = $exists ? $this->qrCodeService->getQRCodeUrl($id) : null;

            return response()->json([
                'success' => true,
                'charging_point_id' => $id,
                'qr_code_exists' => $exists,
                'qr_code_url' => $url,
                'message' => $exists ? 'QR code exists' : 'QR code does not exist'
            ]);

        } catch (\Exception $e) {
            Log::error("❌ QR code status check failed for charging point ID {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'charging_point_id' => $id,
                'error' => $e->getMessage(),
                'message' => 'QR code status check failed'
            ], 500);
        }
    }
}