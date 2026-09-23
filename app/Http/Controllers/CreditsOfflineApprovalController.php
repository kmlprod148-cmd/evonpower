<?php

namespace App\Http\Controllers;

use App\Models\CreditRecharge;
use App\Services\CreditClientScopeService;
use App\Services\CreditRechargeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur pour l'approbation des recharges offline par intégrateurs, opérateurs et partenaires.
 * Chaque rôle ne voit que les demandes des clients ayant réservé sur leurs points de charge.
 */
class CreditsOfflineApprovalController extends Controller
{
    public function __construct(
        protected CreditRechargeService $creditRechargeService,
        protected CreditClientScopeService $clientScopeService
    ) {
        $this->middleware('auth');
        $this->middleware('role:integrator|operator|partner|super_admin');
    }

    /**
     * Liste des recharges offline en attente pour les clients de l'utilisateur.
     */
    public function pending(Request $request)
    {
        $user = Auth::user();
        $clientIds = $this->clientScopeService->getRelatedClientIds($user);

        if ($clientIds !== null && empty($clientIds)) {
            $recharges = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
            $stats = ['total_pending' => 0, 'total_amount' => 0];
            return view('admin.credit-recharges.pending', compact('recharges', 'stats'));
        }

        $query = CreditRecharge::with(['user', 'wallet', 'creditPack'])
            ->where('status', 'pending')
            ->where('payment_method', 'offline')
            ->orderBy('created_at', 'desc');
            
        if ($clientIds !== null) {
            $query->whereIn('user_id', $clientIds);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $recharges = $query->paginate(20);

        $stats = [
            'total_pending' => CreditRecharge::where('status', 'pending')
                ->where('payment_method', 'offline')
                ->when($clientIds !== null, fn($q) => $q->whereIn('user_id', $clientIds))
                ->count(),
            'total_amount' => CreditRecharge::where('status', 'pending')
                ->where('payment_method', 'offline')
                ->when($clientIds !== null, fn($q) => $q->whereIn('user_id', $clientIds))
                ->sum('amount'),
        ];

        return view('admin.credit-recharges.pending', compact('recharges', 'stats'))
            ->with('offlineApprovalScope', true);
    }

    /**
     * Détails d'une recharge (vérification de portée).
     */
    public function show(CreditRecharge $creditRecharge)
    {
        $user = Auth::user();
        if (!$this->clientScopeService->canAccessClient($user, (int) $creditRecharge->user_id)) {
            abort(403, 'Vous n\'avez pas accès à cette recharge.');
        }

        $creditRecharge->load(['user', 'wallet', 'processor', 'creditPack']);

        return view('admin.credit-recharges.show', compact('creditRecharge'))
            ->with('offlineApprovalScope', true);
    }

    /**
     * Confirmer une recharge offline.
     */
    public function confirm(CreditRecharge $creditRecharge, Request $request)
    {
        $approver = Auth::user();
        if (!$this->clientScopeService->canAccessClient($approver, (int) $creditRecharge->user_id)) {
            abort(403, 'Vous n\'avez pas accès à cette recharge.');
        }

        try {
            $result = $this->creditRechargeService->confirmOfflineRecharge(
                $creditRecharge,
                $approver,
                $request->only(['notes'])
            );

            if ($result['success']) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $result['message'],
                        'recharge' => $result['recharge'] ?? null
                    ]);
                }
                return redirect()->route('credits.offline.pending')
                    ->with('success', $result['message']);
            }

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $result['message']], 400);
            }
            return redirect()->back()->with('error', $result['message']);
        } catch (\Exception $e) {
            Log::error('Erreur confirmation recharge offline', [
                'recharge_id' => $creditRecharge->id,
                'approver_id' => $approver->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Erreur lors de la confirmation.');
        }
    }

    /**
     * Rejeter une recharge offline.
     */
    public function reject(CreditRecharge $creditRecharge, Request $request)
    {
        $approver = Auth::user();
        if (!$this->clientScopeService->canAccessClient($approver, (int) $creditRecharge->user_id)) {
            abort(403, 'Vous n\'avez pas accès à cette recharge.');
        }

        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $result = $this->creditRechargeService->rejectOfflineRecharge(
                $creditRecharge,
                $approver,
                $request->reason
            );

            if ($result['success']) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => true, 'message' => $result['message']]);
                }
                return redirect()->route('credits.offline.pending')->with('success', $result['message']);
            }

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $result['message']], 400);
            }
            return redirect()->back()->with('error', $result['message']);
        } catch (\Exception $e) {
            Log::error('Erreur rejet recharge offline', [
                'recharge_id' => $creditRecharge->id,
                'approver_id' => $approver->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Erreur lors du rejet.');
        }
    }
}
