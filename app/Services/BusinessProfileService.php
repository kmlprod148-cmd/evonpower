<?php
namespace App\Services;

use App\Models\BusinessProfile;
use App\Repositories\BusinessProfileRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BusinessProfileService
{
    protected $businessProfileRepository;

    public function __construct(BusinessProfileRepository $businessProfileRepository)
    {
        $this->businessProfileRepository = $businessProfileRepository;
    }

    public function getFilteredProfiles(?string $search, ?string $visibility, ?string $creator, int $perPage = 10)
    {
        return $this->businessProfileRepository->getFilteredProfiles($search, $visibility, $creator, $perPage);
    }

    public function create(array $data): BusinessProfile
    {
        Log::info('BusinessProfileService::create called', $data);
        
        // Valeurs par défaut
        $data['is_public'] = $data['is_public'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;
        
        // Traitement des frais combinés
        $this->processCombinedFees($data);
        
        // Assurer que les champs JSON sont correctement encodés
        if (isset($data['target_audience']) && is_array($data['target_audience'])) {
            $data['target_audience'] = json_encode($data['target_audience']);
        }
        
        if (isset($data['transaction_fee_config']) && is_array($data['transaction_fee_config'])) {
            $data['transaction_fee_config'] = json_encode($data['transaction_fee_config']);
        }
        
        if (isset($data['charge_fee_config']) && is_array($data['charge_fee_config'])) {
            $data['charge_fee_config'] = json_encode($data['charge_fee_config']);
        }
        
        Log::info('BusinessProfileService::create final data', $data);
        
        $profile = $this->businessProfileRepository->create($data);
        
        Log::info('BusinessProfileService::create profile created', ['profile_id' => $profile->id]);
        
        return $profile;
    }

    private function processCombinedFees(array &$data): void
    {
        // Appliquer la période commune
        if (isset($data['subscription_period'])) {
            $data['maintenance_fee_type'] = $data['subscription_period'];
            $data['terminal_fee_period'] = $data['subscription_period'];
        }
        
        // Valeurs par défaut
        $data['maintenance_fee_amount'] = $data['maintenance_fee_amount'] ?? 0;
        $data['terminal_fee_amount'] = $data['terminal_fee_amount'] ?? 0;
        $data['base_fee_amount'] = $data['base_fee_amount'] ?? 0;
        
        // Assurer que les commissions sont définies
        $data['operator_commission'] = $data['operator_commission'] ?? 0;
        $data['integrator_commission'] = $data['integrator_commission'] ?? 0;
        $data['owner_commission'] = $data['owner_commission'] ?? 0;
    }

    public function update(BusinessProfile $profile, array $data): ?BusinessProfile
    {
        $data['is_public'] = $data['is_public'] ?? false;
        
        // Traitement des frais combinés
        $this->processCombinedFees($data);
        
        // Assurer que les champs JSON sont correctement encodés
        if (isset($data['target_audience']) && is_array($data['target_audience'])) {
            $data['target_audience'] = json_encode($data['target_audience']);
        }
        
        if (isset($data['transaction_fee_config']) && is_array($data['transaction_fee_config'])) {
            $data['transaction_fee_config'] = json_encode($data['transaction_fee_config']);
        }
        
        if (isset($data['charge_fee_config']) && is_array($data['charge_fee_config'])) {
            $data['charge_fee_config'] = json_encode($data['charge_fee_config']);
        }

        $updated = $this->businessProfileRepository->update($profile->id, $data);
        return $updated ? $updated->fresh() : null;
    }

    public function calculateMonthlyEstimate(?float $maintenanceFee, ?float $terminalFee, string $period): float
    {
        $maintenanceFee = $maintenanceFee ?? 0.0;
        $terminalFee = $terminalFee ?? 0.0;

        $multiplier = match($period) {
            'monthly' => 1,
            'quarterly' => 1/3,
            'yearly' => 1/12,
            default => 1
        };
        
        return ($maintenanceFee + $terminalFee) * $multiplier;
    }

    public function hasRelatedRecords(BusinessProfile $profile): bool
    {
        // Vérifie s’il existe au moins un partenaire ou intégrateur lié à ce profil
        $hasPartners = $profile->partners()->exists();
        $hasIntegrators = $profile->integrators()->exists();
        return $hasPartners || $hasIntegrators;
    }

    public function delete(BusinessProfile $profile): bool
    {
        return $this->businessProfileRepository->delete($profile);
    }

    public function getTotalCount(): int
    {
        return $this->businessProfileRepository->getTotalAccessibleCount();
    }

    public function getPublicCount(): int
    {
        return $this->businessProfileRepository->getPublicAccessibleCount();
    }

    public function getActiveCount(): int
    {
        return $this->businessProfileRepository->getActiveCount();
    }
}