<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Group;
use App\Models\Partner;
use App\Models\User;

class DiagnoseGroupPartnerAssociation extends Command
{
    protected $signature = 'diagnose:group-partner-association {--partner-id= : ID du partenaire à vérifier} {--fix : Corriger les associations}';
    protected $description = 'Diagnose and optionally fix group-partner association issues';

    public function handle()
    {
        $this->info('=== Diagnostic: Associations Groupe-Partenaire ===');
        $this->newLine();

        $partnerId = $this->option('partner-id');
        $shouldFix = $this->option('fix');

        if ($partnerId) {
            $this->diagnosePartner($partnerId, $shouldFix);
        } else {
            $this->diagnoseAll($shouldFix);
        }
    }

    private function diagnosePartner($partnerId, $shouldFix)
    {
        $partner = Partner::find($partnerId);
        
        if (!$partner) {
            $this->error("Partenaire #$partnerId non trouvé");
            return;
        }

        $this->info("Diagnostic pour le partenaire: {$partner->name} (ID: {$partner->id})");
        $this->newLine();

        // Groupes associés à ce partenaire
        $groupsForPartner = Group::where('partner_id', $partner->id)->get();
        
        $this->info("Groupes actuellement associés à ce partenaire:");
        if ($groupsForPartner->isEmpty()) {
            $this->warn("  Aucun groupe associé");
        } else {
            foreach ($groupsForPartner as $group) {
                $this->line("  - {$group->name} (ID: {$group->id}, user_id: {$group->user_id})");
            }
        }

        $this->newLine();

        // Utilisateurs associés à ce partenaire
        $users = User::where('partner_id', $partner->id)->get();
        $this->info("Utilisateurs associés à ce partenaire: " . $users->count());
        foreach ($users as $user) {
            $this->line("  - {$user->name} (ID: {$user->id}, email: {$user->email})");
        }

        $this->newLine();

        // Groupes créés par les utilisateurs de ce partenaire
        $userIds = $users->pluck('id');
        $groupsCreatedByPartnerUsers = Group::whereIn('user_id', $userIds)->get();
        
        $this->info("Groupes créés par les utilisateurs de ce partenaire: " . $groupsCreatedByPartnerUsers->count());
        foreach ($groupsCreatedByPartnerUsers as $group) {
            $associatedStatus = $group->partner_id === $partner->id ? '✓ Associé' : '✗ NON associé';
            $this->line("  - {$group->name} (ID: {$group->id}) - {$associatedStatus}");
        }

        $this->newLine();

        // Groupes créés par les utilisateurs mais NON associés à ce partenaire
        $incorrectGroups = $groupsCreatedByPartnerUsers->where('partner_id', '!=', $partner->id);
        
        if ($incorrectGroups->isNotEmpty()) {
            $this->warn("⚠️  Groupes créés par des utilisateurs du partenaire mais NON associés:");
            foreach ($incorrectGroups as $group) {
                $this->line("  - {$group->name} (ID: {$group->id}, partner_id: {$group->partner_id})");
            }

            $this->newLine();

            if ($shouldFix && $this->confirm('Associer ces groupes au partenaire?')) {
                foreach ($incorrectGroups as $group) {
                    $group->update(['partner_id' => $partner->id]);
                    $this->info("  ✓ Groupe {$group->name} associé au partenaire");
                }
                $this->info('Correction appliquée!');
            }
        } else {
            $this->info("✓ Tous les groupes créés par les utilisateurs du partenaire sont correctement associés");
        }

        $this->newLine();
    }

    private function diagnoseAll($shouldFix)
    {
        $partners = Partner::all();
        $this->info("Diagnostic pour " . $partners->count() . " partenaires");
        $this->newLine();

        $issuesFound = 0;

        foreach ($partners as $partner) {
            $users = User::where('partner_id', $partner->id)->get();
            $userIds = $users->pluck('id');
            
            if ($userIds->isEmpty()) {
                continue;
            }

            $groupsCreatedByUsers = Group::whereIn('user_id', $userIds)->get();
            $incorrectGroups = $groupsCreatedByUsers->filter(function($group) use ($partner) {
                return $group->partner_id !== $partner->id;
            });

            if ($incorrectGroups->isNotEmpty()) {
                $issuesFound++;
                $this->warn("Partenaire: {$partner->name} (ID: {$partner->id})");
                foreach ($incorrectGroups as $group) {
                    $this->line("  - Groupe: {$group->name} (ID: {$group->id}, partner_id: {$group->partner_id})");
                }
                $this->newLine();

                if ($shouldFix) {
                    foreach ($incorrectGroups as $group) {
                        $group->update(['partner_id' => $partner->id]);
                    }
                    $this->info("  ✓ Corrections appliquées pour {$partner->name}");
                    $this->newLine();
                }
            }
        }

        if ($issuesFound === 0) {
            $this->info("✓ Aucun problème détecté!");
        } else {
            $this->warn("⚠️  {$issuesFound} partenaire(s) avec des groupes incorrectement associés");
            if (!$shouldFix) {
                $this->info("Rouvrez avec --fix pour corriger automatiquement");
            }
        }
    }
}
