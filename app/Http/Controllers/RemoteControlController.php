<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Services\OCPPCommandService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RemoteControlController extends Controller
{
    protected $ocppService;

    public function __construct(OCPPCommandService $ocppService)
    {
        $this->ocppService = $ocppService;
        $this->middleware('auth');
    }

    /**
     * Afficher la page de contrôle à distance
     */
    public function index()
    {
        $user = Auth::user();
        
        // Récupérer les bornes selon le rôle
        $chargingPoints = $this->getAccessibleChargingPoints($user);
        
        return view('remote-control.index', compact('chargingPoints'));
    }

    /**
     * Afficher les détails d'une borne pour le contrôle
     */
    public function show(ChargingPoint $chargingPoint)
    {
        $this->authorize('view', $chargingPoint);

        // Vérifier le statut de connexion
        $connectionStatus = $this->ocppService->checkConnectionStatus($chargingPoint);
        
        // Récupérer les sessions actives
        $activeSessions = $chargingPoint->chargingSessions()
            ->where('status', 'active')
            ->with('user')
            ->get();

        return view('remote-control.show', compact('chargingPoint', 'connectionStatus', 'activeSessions'));
    }

    /**
     * Déclencher une recharge
     */
    public function startCharging(Request $request, ChargingPoint $chargingPoint)
    {
        $this->authorize('update', $chargingPoint);

        $request->validate([
            'connector_id' => 'required|integer|min:1',
            'user_id' => 'nullable|exists:users,id',
            'meter_start' => 'nullable|numeric|min:0',
        ]);

        $result = $this->ocppService->startCharging($chargingPoint, [
            'connector_id' => $request->connector_id,
            'user_id' => $request->user_id,
            'meter_start' => $request->meter_start ?? 0,
        ]);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Arrêter une recharge
     */
    public function stopCharging(Request $request, ChargingPoint $chargingPoint)
    {
        $this->authorize('update', $chargingPoint);

        $request->validate([
            'connector_id' => 'required|integer|min:1',
            'meter_stop' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|in:EmergencyStop,EVDisconnected,HardReset,Local,Other,PowerLoss,Remote,SoftReset,UnlockCommand,DeAuthorized',
        ]);

        $result = $this->ocppService->stopCharging($chargingPoint, [
            'connector_id' => $request->connector_id,
            'meter_stop' => $request->meter_stop ?? 0,
            'reason' => $request->reason ?? 'Remote',
        ]);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Déblocage de connecteur
     */
    public function unlockConnector(Request $request, ChargingPoint $chargingPoint)
    {
        $this->authorize('update', $chargingPoint);

        $request->validate([
            'connector_id' => 'required|integer|min:1',
        ]);

        $result = $this->ocppService->unlockConnector($chargingPoint, [
            'connector_id' => $request->connector_id,
        ]);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Réinitialisation de la borne
     */
    public function resetChargingPoint(Request $request, ChargingPoint $chargingPoint)
    {
        $this->authorize('update', $chargingPoint);

        $request->validate([
            'type' => 'required|string|in:Hard,Soft',
        ]);

        $result = $this->ocppService->resetChargingPoint($chargingPoint, [
            'type' => $request->type,
        ]);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Mise à jour des paramètres
     */
    public function updateConfiguration(Request $request, ChargingPoint $chargingPoint)
    {
        $this->authorize('update', $chargingPoint);

        $request->validate([
            'key' => 'required|string|max:50',
            'value' => 'required|string|max:500',
        ]);

        $result = $this->ocppService->updateConfiguration($chargingPoint, [
            'key' => $request->key,
            'value' => $request->value,
        ]);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Diagnostic de la borne
     */
    public function getDiagnostics(Request $request, ChargingPoint $chargingPoint)
    {
        $this->authorize('view', $chargingPoint);

        $request->validate([
            'location' => 'nullable|url',
            'retries' => 'nullable|integer|min:1|max:10',
            'retry_interval' => 'nullable|integer|min:60|max:3600',
            'start_time' => 'nullable|date',
            'stop_time' => 'nullable|date|after:start_time',
        ]);

        $result = $this->ocppService->getDiagnostics($chargingPoint, [
            'location' => $request->location,
            'retries' => $request->retries ?? 3,
            'retry_interval' => $request->retry_interval ?? 180,
            'start_time' => $request->start_time,
            'stop_time' => $request->stop_time,
        ]);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Récupération des logs
     */
    public function getLogs(Request $request, ChargingPoint $chargingPoint)
    {
        $this->authorize('view', $chargingPoint);

        $request->validate([
            'log_type' => 'required|string|in:DiagnosticsLog,SecurityLog,ErrorLog',
            'retries' => 'nullable|integer|min:1|max:10',
            'retry_interval' => 'nullable|integer|min:60|max:3600',
        ]);

        $result = $this->ocppService->getLogs($chargingPoint, [
            'log_type' => $request->log_type,
            'retries' => $request->retries ?? 3,
            'retry_interval' => $request->retry_interval ?? 180,
        ]);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Vérifier le statut de connexion
     */
    public function checkConnectionStatus(Request $request, ChargingPoint $chargingPoint)
    {
        $this->authorize('view', $chargingPoint);

        $result = $this->ocppService->checkConnectionStatus($chargingPoint);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->back()->with('info', $result['message']);
    }

    /**
     * API pour obtenir les bornes accessibles
     */
    public function getChargingPoints(Request $request)
    {
        $user = Auth::user();
        $chargingPoints = $this->getAccessibleChargingPoints($user);

        return response()->json([
            'success' => true,
            'data' => $chargingPoints
        ]);
    }

    /**
     * Obtenir les bornes accessibles selon le rôle
     */
    protected function getAccessibleChargingPoints($user)
    {
        $query = ChargingPoint::with(['integrator', 'partner', 'group', 'station']);

        if ($user->hasRole('admin')) {
            // Admin voit toutes les bornes
            return $query->get();
        }

        if ($user->hasRole('integrator')) {
            // Intégrateur voit ses bornes
            return $query->where('integrator_id', $user->integrator_id)->get();
        }

        if ($user->hasRole('partner')) {
            // Partenaire voit ses bornes
            return $query->where('partner_id', $user->partner_id)->get();
        }

        // Utilisateur normal ne voit aucune borne pour le contrôle à distance
        return collect();
    }

    /**
     * Journal des actions de contrôle à distance
     */
    public function getActionLog(Request $request, ChargingPoint $chargingPoint)
    {
        $this->authorize('view', $chargingPoint);

        $logs = Log::where('charging_point_id', $chargingPoint->id)
            ->where('action', 'like', 'remote_control%')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
        }

        return view('remote-control.logs', compact('chargingPoint', 'logs'));
    }

    /**
     * Afficher le formulaire de création d'une nouvelle télécommande.
     */
    public function create()
    {
        $user = Auth::user();
        $chargingPoints = $this->getAccessibleChargingPoints($user);
        return view('remote-control.create', compact('chargingPoints'));
    }

    /**
     * Stocker une nouvelle télécommande (ou configuration).
     */
    public function store(Request $request)
    {
        // Logic to store a new remote control configuration or initiate a new remote control session
        // For now, a placeholder.
        // You might validate input, create a new record, or perform an action.

        // Example: Redirect to the index page with a success message
        return redirect()->route('remote-control.index')->with('success', 'Nouvelle télécommande créée/configurée avec succès.');
    }
}