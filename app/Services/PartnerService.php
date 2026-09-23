<?php

namespace App\Services;

use App\Core\Services\BaseService;
use App\Repositories\Interfaces\PartnerRepositoryInterface;
use App\Models\Partner;
use Illuminate\Support\Collection;
// Assuming you will create FormRequests for Partner
// use App\Http\Requests\Partner\StorePartnerRequest;
// use App\Http\Requests\Partner\UpdatePartnerRequest;

/**
 * Class PartnerService
 *
 * Service layer for managing Partner resources.
 *
 * @package App\Services
 */
class PartnerService extends BaseService
{
    /**
     * PartnerService constructor.
     *
     * @param PartnerRepositoryInterface $repository
     */
    public function __construct(PartnerRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get all partners with specified relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllPartnersWithRelations(array $relations = []): Collection
    {
        return $this->repository->getAllWithRelations($relations);
    }

    /**
     * Find a partner by ID with specified relations.
     *
     * @param int $id
     * @param array $relations
     * @return Partner|null
     */
    public function findPartnerWithRelations(int $id, array $relations = []): ?Partner
    {
        return $this->repository->findWithRelations($id, $relations);
    }

    /**
     * Create a new partner.
     *
     * @param array $data
     * @return Partner
     */
    public function createPartner(array $data): Partner
    {
        // Add any specific business logic for creating a partner here
        return $this->repository->create($data);
    }

    /**
     * Met à jour un partenaire existant.
     *
     * @param int $id
     * @param array $data
     * @return Partner
     */
    public function update(int $id, array $data): Partner
    {
        \Log::info('PartnerService@update - Données reçues', ['id' => $id, 'data' => $data]);
        $result = parent::update($id, $data);
        \Log::info('PartnerService@update - Résultat', ['result' => $result]);
        return $result;
    }

    /**
     * Delete a partner.
     *
     * @param Partner $partner
     * @return bool|null
     */
    public function deletePartner(Partner $partner): ?bool
    {
        // Add any specific business logic for deleting a partner here
        // e.g., checking for associated resources
        return $this->repository->delete($partner);
    }

    /**
     * Delete a partner by ID with proper constraint handling.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        \DB::beginTransaction();
        try {
            $partner = $this->findOrFail($id);
            
            \Log::info('PartnerService@delete: Starting deletion process', [
                'partner_id' => $id,
                'partner_name' => $partner->name
            ]);
            
            // Vérifier et supprimer les relations qui pourraient bloquer la suppression
            $this->handlePartnerRelations($partner);
            
            // Supprimer le partenaire
            $result = $this->repository->delete($partner);
            
            \DB::commit();
            
            \Log::info('PartnerService@delete: Partner deleted successfully', [
                'partner_id' => $id,
                'result' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('PartnerService@delete: Error deleting partner', [
                'partner_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle partner relations before deletion.
     *
     * @param Partner $partner
     * @return void
     * @throws \Exception
     */
    private function handlePartnerRelations(Partner $partner): void
    {
        \Log::info('PartnerService@handlePartnerRelations: Processing relations', [
            'partner_id' => $partner->id
        ]);
        
        // Supprimer les utilisateurs associés
        $users = $partner->users;
        foreach ($users as $user) {
            \Log::info('PartnerService@handlePartnerRelations: Deleting user', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
            
            try {
                // Supprimer les rôles de l'utilisateur
                if (method_exists($user, 'syncRoles')) {
                    $user->syncRoles([]);
                }
                
                // Supprimer les permissions de l'utilisateur
                if (method_exists($user, 'syncPermissions')) {
                    $user->syncPermissions([]);
                }
                
                $user->delete();
                
                \Log::info('PartnerService@handlePartnerRelations: User deleted successfully', [
                    'user_id' => $user->id
                ]);
                
            } catch (\Exception $e) {
                \Log::error('PartnerService@handlePartnerRelations: Error deleting user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception("Impossible de supprimer l'utilisateur {$user->email}: " . $e->getMessage());
            }
        }
        
        // Supprimer les points de charge associés
        $chargingPoints = $partner->chargingPoints;
        foreach ($chargingPoints as $chargingPoint) {
            \Log::info('PartnerService@handlePartnerRelations: Deleting charging point', [
                'charging_point_id' => $chargingPoint->id
            ]);
            
            // Supprimer les connecteurs associés
            $chargingPoint->connectors()->delete();
            $chargingPoint->delete();
        }
        
        // Supprimer les groupes associés
        $groups = $partner->groups;
        foreach ($groups as $group) {
            \Log::info('PartnerService@handlePartnerRelations: Deleting group', [
                'group_id' => $group->id,
                'group_name' => $group->name
            ]);
            $group->delete();
        }
        
        \Log::info('PartnerService@handlePartnerRelations: All relations processed', [
            'partner_id' => $partner->id,
            'users_deleted' => $users->count(),
            'charging_points_deleted' => $chargingPoints->count(),
            'groups_deleted' => $groups->count()
        ]);
    }

    /**
     * Crée un partenaire et un utilisateur admin associé, puis attribue le rôle operator à l'utilisateur.
     *
     * @param array $partnerData
     * @param string $adminEmail
     * @param string $adminPassword
     * @return Partner
     * @throws \Exception
     */
    public function createPartnerWithAdminUser(array $partnerData, string $adminEmail, string $adminPassword): Partner
    {
        \DB::beginTransaction();
        try {
            // Filtrer les champs qui ne doivent pas être passés au modèle Partner
            $partnerFields = collect($partnerData)->except([
                'admin_email', 
                'admin_password', 
                'admin_password_confirmation'
            ])->toArray();
            
            // Création du partenaire
            $partner = $this->repository->create($partnerFields);

            // Création de l'utilisateur admin associé
            $user = new \App\Models\User();
            $user->name = $partnerData['name'] ?? 'Admin';
            $user->email = $adminEmail;
            $user->password = \Illuminate\Support\Facades\Hash::make($adminPassword);
            $user->partner_id = $partner->id;
            if (isset($partnerData['integrator_id'])) {
                $user->integrator_id = $partnerData['integrator_id'];
            }
            $user->save();

            // Attribution du rôle operator
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('operator');
            }

            \DB::commit();
            return $partner;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du partenaire ou de l\'utilisateur admin : ' . $e->getMessage(), ['exception' => $e]);
            \DB::rollBack();
            throw $e;
        }
    }

    // Add other Partner specific service methods here
}