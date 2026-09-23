<?php

namespace App\Http\Controllers;

use App\Models\CommissionPlan;
use App\Models\Transaction;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CommissionPlanController extends Controller
{
    protected $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
        $this->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_commission_settings');
        $this->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':manage_commissions', ['only' => ['create', 'store', 'edit', 'update', 'destroy', 'toggleActive', 'setDefault', 'recalculateCommissions']]);
    }

    /**
     * Affiche la liste des plans de commission.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = CommissionPlan::query();
        
        // Filtres
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->has('applies_to_type')) {
            $query->where('applies_to_type', $request->input('applies_to_type'));
            
            if ($request->has('applies_to_id')) {
                $query->where('applies_to_id', $request->input('applies_to_id'));
            }
        }
        
        // Tri
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $plans = $query->paginate($perPage);
        
        // Déterminer si on est dans le contexte des paramètres admin
        $isAdminSettings = $request->route()->getName() === 'settings.commission-plans';
        
        if ($isAdminSettings) {
            // Vue pour les paramètres admin
            return view('settings.commission-plans', compact('plans', 'isAdminSettings'));
        } else {
            // Vue standard
            return view('commission-plans.index', compact('plans', 'isAdminSettings'));
        }
    }

    /**
     * Affiche le formulaire de création d'un plan de commission.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('commission-plans.create');
    }

    /**
     * Enregistre un nouveau plan de commission.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'admin_percentage' => 'required|numeric|min:0|max:100',
            'integrator_percentage' => 'required|numeric|min:0|max:100',
            'partner_percentage' => 'required|numeric|min:0|max:100',
            'min_transaction_value' => 'nullable|numeric|min:0',
            'max_transaction_value' => 'nullable|numeric|min:0',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'applies_to_type' => ['required', Rule::in(['global', 'integrator', 'partner', 'group'])],
            'applies_to_id' => 'nullable|required_unless:applies_to_type,global|integer',
            'priority' => 'nullable|integer',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'transaction_manager' => ['required', Rule::in(['admin', 'integrator', 'partner', 'shared'])],
            'requires_approval' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Si ce plan est défini comme par défaut, désactiver les autres plans par défaut
        if ($request->boolean('is_default')) {
            CommissionPlan::where('is_default', true)->update(['is_default' => false]);
        }
        
        // Créer le plan de commission
        $plan = new CommissionPlan($request->all());
        
        // Définir le créateur du plan
        $user = $request->user();
        if ($user->role === 'admin') {
            $plan->created_by_type = 'admin';
            $plan->created_by_id = $user->id;
        } elseif ($user->integrator_id) {
            $plan->created_by_type = 'integrator';
            $plan->created_by_id = $user->integrator_id;
        }
        
        // Traiter les permissions partagées si nécessaire
        if ($request->input('transaction_manager') === 'shared' && $request->has('permissions')) {
            $permissions = [];
            foreach ($request->input('permissions', []) as $entity => $perms) {
                $permissions[$entity] = $perms;
            }
            $plan->transaction_permissions = $permissions;
        }
        
        // Traiter le flux d'approbation si nécessaire
        if ($request->boolean('requires_approval')) {
            $approvalWorkflow = [
                'actions' => $request->input('approval_actions', []),
                'default_approvers' => $request->input('default_approvers', [])
            ];
            $plan->approval_workflow = $approvalWorkflow;
        }
        
        // Enregistrer le plan
        $plan->save();
        
        return redirect()->route('commission-plans.index')
            ->with('success', 'Plan de commission créé avec succès.');
    }

    /**
     * Affiche les détails d'un plan de commission.
     *
     * @param CommissionPlan $commissionPlan
     * @return \Illuminate\Http\Response
     */
    public function show(CommissionPlan $commissionPlan)
    {
        $this->authorize('view', $commissionPlan);
        // Récupérer les transactions associées à ce plan
        $transactions = Transaction::where('commission_plan_id', $commissionPlan->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('commission-plans.show', compact('commissionPlan', 'transactions'));
    }

    /**
     * Affiche le formulaire d'édition d'un plan de commission.
     *
     * @param CommissionPlan $commissionPlan
     * @return \Illuminate\Http\Response
     */
    public function edit(CommissionPlan $commissionPlan)
    {
        $this->authorize('update', $commissionPlan);
        return view('commission-plans.edit', compact('commissionPlan'));
    }

    /**
     * Met à jour un plan de commission.
     *
     * @param Request $request
     * @param CommissionPlan $commissionPlan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CommissionPlan $commissionPlan)
    {
        $this->authorize('update', $commissionPlan);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'admin_percentage' => 'required|numeric|min:0|max:100',
            'integrator_percentage' => 'required|numeric|min:0|max:100',
            'partner_percentage' => 'required|numeric|min:0|max:100',
            'min_transaction_value' => 'nullable|numeric|min:0',
            'max_transaction_value' => 'nullable|numeric|min:0',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'applies_to_type' => ['required', Rule::in(['global', 'integrator', 'partner', 'group'])],
            'applies_to_id' => 'nullable|required_unless:applies_to_type,global|integer',
            'priority' => 'nullable|integer',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'transaction_manager' => ['required', Rule::in(['admin', 'integrator', 'partner', 'shared'])],
            'requires_approval' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Si ce plan est défini comme par défaut, désactiver les autres plans par défaut
        if ($request->boolean('is_default') && !$commissionPlan->is_default) {
            CommissionPlan::where('is_default', true)->update(['is_default' => false]);
        }
        
        // Préparer les données pour la mise à jour
        $data = $request->all();
        
        // Traiter les permissions partagées si nécessaire
        if ($request->input('transaction_manager') === 'shared' && $request->has('permissions')) {
            $permissions = [];
            foreach ($request->input('permissions', []) as $entity => $perms) {
                $permissions[$entity] = $perms;
            }
            $data['transaction_permissions'] = $permissions;
        } else {
            $data['transaction_permissions'] = null;
        }
        
        // Traiter le flux d'approbation si nécessaire
        if ($request->boolean('requires_approval')) {
            $approvalWorkflow = [
                'actions' => $request->input('approval_actions', []),
                'default_approvers' => $request->input('default_approvers', [])
            ];
            $data['approval_workflow'] = $approvalWorkflow;
        } else {
            $data['approval_workflow'] = null;
        }
        
        // Mettre à jour le plan
        $commissionPlan->update($data);
        
        return redirect()->route('commission-plans.index')
            ->with('success', 'Plan de commission mis à jour avec succès.');
    }

    /**
     * Supprime un plan de commission.
     *
     * @param CommissionPlan $commissionPlan
     * @return \Illuminate\Http\Response
     */
    public function destroy(CommissionPlan $commissionPlan)
    {
        $this->authorize('delete', $commissionPlan);
        // Vérifier si le plan est utilisé par des transactions
        $transactionCount = Transaction::where('commission_plan_id', $commissionPlan->id)->count();
        
        if ($transactionCount > 0) {
            return redirect()->back()
                ->with('error', "Ce plan ne peut pas être supprimé car il est utilisé par {$transactionCount} transactions.");
        }
        
        // Supprimer le plan
        $commissionPlan->delete();
        
        return redirect()->route('commission-plans.index')
            ->with('success', 'Plan de commission supprimé avec succès.');
    }

    /**
     * Active ou désactive un plan de commission.
     *
     * @param CommissionPlan $commissionPlan
     * @return \Illuminate\Http\Response
     */
    public function toggleActive(CommissionPlan $commissionPlan)
    {
        $this->authorize('update', $commissionPlan);
        $commissionPlan->is_active = !$commissionPlan->is_active;
        $commissionPlan->save();
        
        $status = $commissionPlan->is_active ? 'activé' : 'désactivé';
        
        return redirect()->back()
            ->with('success', "Plan de commission {$status} avec succès.");
    }

    /**
     * Définit un plan comme plan par défaut.
     *
     * @param CommissionPlan $commissionPlan
     * @return \Illuminate\Http\Response
     */
    public function setDefault(CommissionPlan $commissionPlan)
    {
        $this->authorize('update', $commissionPlan);
        // Désactiver tous les plans par défaut
        CommissionPlan::where('is_default', true)->update(['is_default' => false]);
        
        // Définir ce plan comme par défaut
        $commissionPlan->is_default = true;
        $commissionPlan->save();
        
        return redirect()->back()
            ->with('success', 'Plan de commission défini comme plan par défaut.');
    }

    /**
     * Recalcule les commissions pour les transactions existantes en utilisant ce plan.
     *
     * @param Request $request
     * @param CommissionPlan $commissionPlan
     * @return \Illuminate\Http\Response
     */
    public function recalculateCommissions(Request $request, CommissionPlan $commissionPlan)
    {
        $this->authorize('update', $commissionPlan);
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'apply_to' => ['required', Rule::in(['plan_transactions', 'all_transactions', 'filtered_transactions'])],
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $query = Transaction::query();
        
        // Filtrer par date
        if ($request->filled('start_date')) {
            $query->where('start_timestamp', '>=', $request->input('start_date') . ' 00:00:00');
        }
        
        if ($request->filled('end_date')) {
            $query->where('start_timestamp', '<=', $request->input('end_date') . ' 23:59:59');
        }
        
        // Appliquer le filtre selon l'option choisie
        $applyTo = $request->input('apply_to');
        
        if ($applyTo === 'plan_transactions') {
            // Appliquer uniquement aux transactions qui utilisent déjà ce plan
            $query->where('commission_plan_id', $commissionPlan->id);
        } elseif ($applyTo === 'filtered_transactions') {
            // Appliquer aux transactions qui correspondent aux critères du plan
            // (Cette logique dépend de la structure de vos données et des règles du plan)
            // Exemple simplifié:
            if ($commissionPlan->min_transaction_value) {
                $query->where('price_total', '>=', $commissionPlan->min_transaction_value);
            }
            
            if ($commissionPlan->max_transaction_value) {
                $query->where('price_total', '<=', $commissionPlan->max_transaction_value);
            }
            
            if ($commissionPlan->applies_to_type === 'integrator' && $commissionPlan->applies_to_id) {
                $query->whereHas('chargingPoint', function ($q) use ($commissionPlan) {
                    $q->where('integrator_id', $commissionPlan->applies_to_id);
                });
            } elseif ($commissionPlan->applies_to_type === 'partner' && $commissionPlan->applies_to_id) {
                $query->whereHas('chargingPoint', function ($q) use ($commissionPlan) {
                    $q->where('partner_id', $commissionPlan->applies_to_id);
                });
            } elseif ($commissionPlan->applies_to_type === 'group' && $commissionPlan->applies_to_id) {
                $query->whereHas('chargingPoint', function ($q) use ($commissionPlan) {
                    $q->where('group_id', $commissionPlan->applies_to_id);
                });
            }
        }
        
        // Récupérer les transactions et recalculer les commissions
        $transactions = $query->get();
        $count = $this->commissionService->recalculateCommissions($transactions, $commissionPlan);
        
        return redirect()->back()
            ->with('success', "Commissions recalculées pour {$count} transactions.");
    }
}