<?php

namespace App\Http\Controllers;

use App\Services\QRCodeServiceImproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QRCodeTestImprovedController extends Controller
{
    protected QRCodeServiceImproved $qrCodeService;

    public function __construct(QRCodeServiceImproved $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Test QR code generation with improved diagnostics
     */
    public function testGeneration($id)
    {
        try {
            Log::info("🧪 Test génération QR code amélioré pour borne ID: {$id}");

            // Run comprehensive test
            $testResults = $this->qrCodeService->testQRCodeGeneration($id);
            
            // Add additional diagnostics
            $diagnostics = [
                'charging_point_exists' => \App\Models\ChargingPoint::find($id) !== null,
                'storage_writable' => is_writable(storage_path('app/public')),
                'qrcodes_dir_exists' => \Illuminate\Support\Facades\Storage::disk('public')->exists('qrcodes'),
                'app_url' => config('app.url'),
                'environment' => config('app.env'),
            ];

            return response()->json([
                'success' => true,
                'charging_point_id' => $id,
                'test_results' => $testResults,
                'diagnostics' => $diagnostics,
                'message' => 'Test QR code amélioré terminé'
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Test QR code amélioré échoué pour borne ID {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'charging_point_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'message' => 'Test QR code amélioré échoué'
            ], 500);
        }
    }

    /**
     * Check QR code status
     */
    public function checkStatus($id)
    {
        try {
            $exists = $this->qrCodeService->qrCodeExists($id);
            $url = $exists ? $this->qrCodeService->getQRCodeUrl($id) : null;

            return response()->json([
                'success' => true,
                'charging_point_id' => $id,
                'qr_code_exists' => $exists,
                'qr_code_url' => $url,
                'message' => $exists ? 'QR code existe' : 'QR code n\'existe pas'
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Vérification statut QR code échouée pour borne ID {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'charging_point_id' => $id,
                'error' => $e->getMessage(),
                'message' => 'Vérification statut QR code échouée'
            ], 500);
        }
    }

    /**
     * Show QR code with improved error handling
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
                'qr_code_url' => $qrCodeUrl,
                'improved' => true
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Affichage QR code amélioré échoué pour borne ID {$id}: " . $e->getMessage());
            
            return view('qr-code.error', [
                'charging_point_id' => $id,
                'error' => $e->getMessage(),
                'improved' => true
            ]);
        }
    }

    /**
     * Generate QR code with improved error handling
     */
    public function generateQRCode($id)
    {
        try {
            Log::info("🔧 Génération QR code amélioré pour borne ID: {$id}");

            // Generate QR code as data URL
            $qrCodeDataUrl = $this->qrCodeService->generateAsDataUrl($id);
            
            // Generate QR code as file
            $qrCodeUrl = $this->qrCodeService->generateAndSaveQRCode($id);

            return response()->json([
                'success' => true,
                'charging_point_id' => $id,
                'qr_code_data_url' => $qrCodeDataUrl,
                'qr_code_url' => $qrCodeUrl,
                'message' => 'QR code amélioré généré avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Génération QR code amélioré échouée pour borne ID {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'charging_point_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'message' => 'Génération QR code amélioré échouée'
            ], 500);
        }
    }
}