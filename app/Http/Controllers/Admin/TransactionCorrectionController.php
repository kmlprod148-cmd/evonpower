<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TransactionCorrectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TransactionCorrectionController extends Controller
{
    protected $correctionService;

    public function __construct(TransactionCorrectionService $correctionService)
    {
        $this->correctionService = $correctionService;
    }

    /**
     * Afficher la page de correction des transactions
     */
    public function index(): View
    {
        $report = $this->correctionService->generateCorrectionReport();
        
        return view('admin.transactions.correction', [
            'report' => $report
        ]);
    }

    /**
     * Corriger toutes les anciennes transactions
     */
    public function correctAll(Request $request): JsonResponse
    {
        try {
            $results = $this->correctionService->correctOldTransactions();

            if (isset($results['success']) && !$results['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la correction: ' . $results['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Correction terminée avec succès',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Corriger les transactions d'un business profile spécifique
     */
    public function correctByBusinessProfile(Request $request, int $businessProfileId): JsonResponse
    {
        try {
            $results = $this->correctionService->correctTransactionsByBusinessProfile($businessProfileId);

            if (isset($results['success']) && !$results['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la correction: ' . $results['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Correction du business profile terminée avec succès',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un rapport de correction
     */
    public function generateReport(): JsonResponse
    {
        try {
            $report = $this->correctionService->generateCorrectionReport();

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport: ' . $e->getMessage()
            ], 500);
        }
    }
}
