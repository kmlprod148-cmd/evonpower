<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\BillingService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;

class BillingReportController extends Controller
{
    protected BillingService $billingService;
    protected ReportService $reportService;

    public function __construct(BillingService $billingService, ReportService $reportService)
    {
        $this->billingService = $billingService;
        $this->reportService = $reportService;
    }

    /**
     * Afficher le tableau de bord des rapports
     */
    public function dashboard(): View
    {
        $stats = $this->billingService->getBillingStats();
        
        return view('billing.reports.dashboard', compact('stats'));
    }

    /**
     * Générer un rapport personnalisé
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:csv,pdf,json,xml',
            'filters' => 'nullable|array'
        ]);

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $format = $request->format;
            $filters = $request->get('filters', []);

            // Ajouter les dates aux filtres
            $filters['start_date'] = $startDate;
            $filters['end_date'] = $endDate;

            // Générer le rapport
            $report = $this->reportService->generateCustomReport($filters);

            // Exporter le rapport
            $filePath = $this->reportService->exportReport($report, $format);

            return response()->json([
                'success' => true,
                'message' => 'Rapport généré avec succès',
                'data' => [
                    'file_path' => $filePath,
                    'file_name' => basename($filePath),
                    'file_size' => filesize($filePath),
                    'download_url' => route('billing.reports.download', ['file' => basename($filePath)])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger un rapport
     */
    public function download(string $filename)
    {
        $filePath = storage_path("app/reports/{$filename}");
        
        if (!file_exists($filePath)) {
            abort(404, 'Fichier non trouvé');
        }

        return response()->download($filePath);
    }

    /**
     * Afficher les rapports disponibles
     */
    public function index(): View
    {
        $reportsPath = storage_path('app/reports');
        $reports = [];

        if (is_dir($reportsPath)) {
            $files = glob($reportsPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $reports[] = [
                        'name' => basename($file),
                        'size' => filesize($file),
                        'created' => date('Y-m-d H:i:s', filemtime($file)),
                        'type' => pathinfo($file, PATHINFO_EXTENSION)
                    ];
                }
            }
        }

        // Trier par date de création (plus récent en premier)
        usort($reports, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });

        return view('billing.reports.index', compact('reports'));
    }

    /**
     * Générer un rapport de revenus
     */
    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'required|in:day,week,month,year'
        ]);

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $groupBy = $request->group_by;

            $report = $this->reportService->generateRevenueReport($startDate, $endDate, $groupBy);

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport de revenus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un rapport par entité
     */
    public function byEntity(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            $report = $this->reportService->generateBillingByEntityReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport par entité: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un rapport
     */
    public function destroy(string $filename): JsonResponse
    {
        $filePath = storage_path("app/reports/{$filename}");
        
        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier non trouvé'
            ], 404);
        }

        unlink($filePath);

        return response()->json([
            'success' => true,
            'message' => 'Rapport supprimé avec succès'
        ]);
    }
}
