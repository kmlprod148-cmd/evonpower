<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\TransactionReportingService;
use App\Services\UnifiedTransactionProcessingService;
use App\Enums\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    protected TransactionReportingService $reportingService;
    protected UnifiedTransactionProcessingService $processingService;

    public function __construct(
        TransactionReportingService $reportingService,
        UnifiedTransactionProcessingService $processingService
    ) {
        $this->reportingService = $reportingService;
        $this->processingService = $processingService;
    }

    /**
     * Obtenir la liste des types de transactions disponibles
     * avec leurs libellés traduits et codes internes
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionTypes()
    {
        $types = TransactionType::formatForApi();
        
        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Obtenir les catégories de transactions
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionCategories()
    {
        $categories = [];
        
        foreach (TransactionType::CATEGORY_LABELS as $key => $label) {
            $categories[] = [
                'code' => $key,
                'label' => $label,
                'types' => array_keys(TransactionType::TYPE_TO_CATEGORY, $key),
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Obtenir les statistiques de transactions
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionStats(Request $request)
    {
        $filters = $request->validate([
            'transaction_type' => 'nullable|string',
            'transaction_category' => 'nullable|string',
            'status' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $stats = $this->processingService->getTransactionStats($filters);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Obtenir les données du tableau de bord Administrateur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdminDashboard(Request $request)
    {
        $period = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $data = $this->reportingService->getAdminDashboardData($period);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Obtenir les données du tableau de bord Intégrateur/Partenaire
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIntegratorPartnerDashboard(Request $request)
    {
        $user = Auth::user();
        $period = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $data = $this->reportingService->getIntegratorPartnerDashboardData($user, $period);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Obtenir les transactions avec filtres
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactions(Request $request)
    {
        $filters = $request->validate([
            'transaction_type' => 'nullable|string',
            'transaction_category' => 'nullable|string',
            'status' => 'nullable|string',
            'collect_status' => 'nullable|string',
            'integrator_id' => 'nullable|integer',
            'partner_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = $filters['per_page'] ?? 20;
        
        $transactions = $this->reportingService->getFilteredTransactions($filters)
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Exporter les transactions en CSV
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportCsv(Request $request)
    {
        $filters = $request->validate([
            'transaction_type' => 'nullable|string',
            'transaction_category' => 'nullable|string',
            'status' => 'nullable|string',
            'collect_status' => 'nullable|string',
            'integrator_id' => 'nullable|integer',
            'partner_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        return $this->reportingService->exportTransactionsCsv($filters);
    }

    /**
     * Exporter les transactions en Excel
     * 
     * @param Request $request
     * @return \Maatwebsite\Excel\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function exportExcel(Request $request)
    {
        $filters = $request->validate([
            'transaction_type' => 'nullable|string',
            'transaction_category' => 'nullable|string',
            'status' => 'nullable|string',
            'collect_status' => 'nullable|string',
            'integrator_id' => 'nullable|integer',
            'partner_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        try {
            return $this->reportingService->exportTransactionsExcel($filters);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'export Excel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour le type d'une transaction
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTransactionType(Request $request, int $id)
    {
        $data = $request->validate([
            'transaction_type' => 'required|string',
        ]);

        if (!TransactionType::isValidType($data['transaction_type'])) {
            return response()->json([
                'success' => false,
                'error' => 'Type de transaction invalide',
            ], 400);
        }

        $transaction = \App\Models\Transaction::find($id);
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'error' => 'Transaction non trouvée',
            ], 404);
        }

        $updatedTransaction = $this->processingService->updateTransactionType(
            $transaction, 
            $data['transaction_type']
        );

        return response()->json([
            'success' => true,
            'data' => $updatedTransaction,
        ]);
    }
}
