<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Traits\IntegratorDataIsolation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class SystemAuditController extends Controller
{
    use IntegratorDataIsolation;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la liste des audits système pour l'intégrateur
     */
    public function index(Request $request)
    {
        Gate::authorize('view_system_audit');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Récupérer les logs d'audit liés à l'intégrateur et ses opérateurs
        $query = AuditLog::where(function($q) use ($integrator) {
                $q->where('user_id', $integrator->id)
                  ->orWhereHas('user', function($userQuery) use ($integrator) {
                      $userQuery->where('integrator_id', $integrator->id);
                  });
            })
            ->with(['user'])
            ->orderBy('created_at', 'desc');

        // Filtrage par date si spécifié
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filtrage par type d'action
        if ($request->filled('action_type')) {
            $query->where('action', $request->action_type);
        }

        $auditLogs = $query->paginate(20);

        return view('integrator.system-audit.index', compact('auditLogs'));
    }

    /**
     * Affiche les détails d'un audit
     */
    public function show(AuditLog $audit)
    {
        Gate::authorize('view_system_audit');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Vérifier que l'audit appartient à l'intégrateur
        $hasAccess = $audit->user_id === $integrator->id || 
                    ($audit->user && $audit->user->integrator_id === $integrator->id);
        
        if (!$hasAccess) {
            abort(403, 'Accès non autorisé à cet audit.');
        }

        $audit->load(['user']);

        return view('integrator.system-audit.show', compact('audit'));
    }

    /**
     * Exporte les logs d'audit
     */
    public function export(Request $request)
    {
        Gate::authorize('export_system_audit');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Récupérer les logs d'audit liés à l'intégrateur
        $query = AuditLog::where(function($q) use ($integrator) {
                $q->where('user_id', $integrator->id)
                  ->orWhereHas('user', function($userQuery) use ($integrator) {
                      $userQuery->where('integrator_id', $integrator->id);
                  });
            })
            ->with(['user']);

        // Appliquer les filtres
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('action_type')) {
            $query->where('action', $request->action_type);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->get();

        // Générer le CSV
        $filename = 'audit_logs_' . $integrator->id . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($auditLogs) {
            $file = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($file, [
                'Date',
                'Utilisateur',
                'Action',
                'Modèle',
                'ID Modèle',
                'Anciennes Valeurs',
                'Nouvelles Valeurs',
                'IP',
                'User Agent'
            ]);

            // Données
            foreach ($auditLogs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user ? $log->user->name : 'N/A',
                    $log->action,
                    $log->auditable_type,
                    $log->auditable_id,
                    $log->old_values ? json_encode($log->old_values) : '',
                    $log->new_values ? json_encode($log->new_values) : '',
                    $log->ip_address,
                    $log->user_agent
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}