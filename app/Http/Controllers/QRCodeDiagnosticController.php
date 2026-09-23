<?php

namespace App\Http\Controllers;

use App\Services\QRCodeDiagnosticService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QRCodeDiagnosticController extends Controller
{
    private QRCodeDiagnosticService $diagnosticService;

    public function __construct(QRCodeDiagnosticService $diagnosticService)
    {
        $this->diagnosticService = $diagnosticService;
    }

    /**
     * Run QR code diagnostics
     */
    public function diagnose(): JsonResponse
    {
        try {
            $diagnostics = $this->diagnosticService->diagnose();
            
            return response()->json([
                'success' => true,
                'diagnostics' => $diagnostics,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Generate a test QR code
     */
    public function generateTest(): JsonResponse
    {
        try {
            $result = $this->diagnosticService->generateTestQRCode();
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
