<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DiagnosticController extends Controller
{
    public function showGroupPartnerAssociations()
    {
        $issues = [];

        $partners = Partner::all();

        foreach ($partners as $partner) {
            $users = User::where('partner_id', $partner->id)->get();
            
            if ($users->isEmpty()) {
                continue;
            }

            $userIds = $users->pluck('id');
            
            // Groupes créés par les utilisateurs du partenaire
            $groupsCreatedByUsers = Group::whereIn('user_id', $userIds)->get();
            
            // Séparer les groupes corrects et incorrects
            $correctGroups = $groupsCreatedByUsers->filter(function($group) use ($partner) {
                return $group->partner_id === $partner->id;
            });

            $incorrectGroups = $groupsCreatedByUsers->filter(function($group) use ($partner) {
                return $group->partner_id !== $partner->id;
            });

            if ($incorrectGroups->isNotEmpty()) {
                $issues[$partner->id] = [
                    'partner' => $partner,
                    'users' => $users,
                    'correct_groups' => $correctGroups,
                    'incorrect_groups' => $incorrectGroups,
                ];
            }
        }

        return view('admin.diagnostics.group-partner-associations', compact('issues'));
    }

    public function fixGroupAssociation(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'partner_id' => 'required|exists:partners,id',
        ]);

        try {
            $group = Group::find($validated['group_id']);
            $partner = Partner::find($validated['partner_id']);

            // Vérifier que l'utilisateur qui crée le groupe est associé à ce partenaire
            if ($group->user && $group->user->partner_id !== $partner->id) {
                return back()->with('error', 'L\'utilisateur qui a créé ce groupe n\'est pas associé à ce partenaire');
            }

            $oldPartnerId = $group->partner_id;
            $group->update(['partner_id' => $partner->id]);

            Log::info('Group association fixed', [
                'group_id' => $group->id,
                'old_partner_id' => $oldPartnerId,
                'new_partner_id' => $partner->id,
                'fixed_by' => auth()->id(),
            ]);

            return back()->with('success', "Groupe '{$group->name}' associé au partenaire '{$partner->name}'");
        } catch (\Exception $e) {
            Log::error('Error fixing group association', [
                'group_id' => $validated['group_id'],
                'partner_id' => $validated['partner_id'],
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de la correction: ' . $e->getMessage());
        }
    }

    public function fixAllAssociations()
    {
        try {
            $fixedCount = 0;
            $partners = Partner::all();

            foreach ($partners as $partner) {
                $users = User::where('partner_id', $partner->id)->get();
                
                if ($users->isEmpty()) {
                    continue;
                }

                $userIds = $users->pluck('id');
                
                $groupsCreatedByUsers = Group::whereIn('user_id', $userIds)->get();
                
                foreach ($groupsCreatedByUsers as $group) {
                    if ($group->partner_id !== $partner->id) {
                        $oldPartnerId = $group->partner_id;
                        $group->update(['partner_id' => $partner->id]);
                        
                        Log::info('Group association fixed (bulk)', [
                            'group_id' => $group->id,
                            'old_partner_id' => $oldPartnerId,
                            'new_partner_id' => $partner->id,
                            'fixed_by' => auth()->id(),
                        ]);
                        
                        $fixedCount++;
                    }
                }
            }

            return redirect()->route('admin.diagnostics.group-partner-associations')
                ->with('success', "{$fixedCount} association(s) corrigée(s) avec succès");
        } catch (\Exception $e) {
            Log::error('Error fixing all associations', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de la correction globale: ' . $e->getMessage());
        }
    }
}
