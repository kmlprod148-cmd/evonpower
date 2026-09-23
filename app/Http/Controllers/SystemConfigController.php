<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemConfig;
use App\Traits\IntegratorDataIsolation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class SystemConfigController extends Controller
{
    use IntegratorDataIsolation;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la configuration système pour l'intégrateur
     */
    public function index()
    {
        Gate::authorize('view_system_config');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Récupérer la configuration spécifique à l'intégrateur
        $config = SystemConfig::where('integrator_id', $integrator->id)->first();
        
        // Si aucune configuration n'existe, créer une configuration par défaut
        if (!$config) {
            $config = SystemConfig::create([
                'integrator_id' => $integrator->id,
                'commission_rate' => 0.05, // 5% par défaut
                'max_operators' => 10,
                'auto_approve_operators' => false,
                'notification_email' => $integrator->email,
                'is_active' => true,
            ]);
        }

        return view('integrator.system-config.index', compact('config'));
    }

    /**
     * Affiche le formulaire d'édition de la configuration
     */
    public function edit()
    {
        Gate::authorize('edit_system_config');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $config = SystemConfig::where('integrator_id', $integrator->id)->first();
        
        if (!$config) {
            return redirect()->route('integrator.system-config.index')
                ->with('error', 'Configuration non trouvée.');
        }

        return view('integrator.system-config.edit', compact('config'));
    }

    /**
     * Met à jour la configuration système
     */
    public function update(Request $request)
    {
        Gate::authorize('edit_system_config');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:1',
            'max_operators' => 'required|integer|min:1|max:100',
            'auto_approve_operators' => 'boolean',
            'notification_email' => 'required|email',
            'is_active' => 'boolean',
        ]);

        try {
            $config = SystemConfig::where('integrator_id', $integrator->id)->first();
            
            if (!$config) {
                return redirect()->route('integrator.system-config.index')
                    ->with('error', 'Configuration non trouvée.');
            }

            $config->update([
                'commission_rate' => $request->commission_rate,
                'max_operators' => $request->max_operators,
                'auto_approve_operators' => $request->has('auto_approve_operators'),
                'notification_email' => $request->notification_email,
                'is_active' => $request->has('is_active'),
            ]);

            // Log de l'audit
            Log::info('System config updated by integrator', [
                'integrator_id' => $integrator->id,
                'integrator_name' => $integrator->name,
                'config_id' => $config->id,
                'changes' => $request->all()
            ]);

            return redirect()->route('integrator.system-config.index')
                ->with('success', 'Configuration mise à jour avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Error updating system config', [
                'integrator_id' => $integrator->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de la configuration.')
                ->withInput();
        }
    }
}