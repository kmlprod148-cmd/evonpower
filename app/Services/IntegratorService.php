<?php

namespace App\Services;

use App\Core\Services\BaseService;
use App\Repositories\Interfaces\IntegratorRepositoryInterface;
use App\Models\Integrator;
use Illuminate\Support\Collection;
// Assuming you will create FormRequests for Integrator
// use App\Http\Requests\Integrator\StoreIntegratorRequest;
// use App\Http\Requests\Integrator\UpdateIntegratorRequest;

/**
 * Class IntegratorService
 *
 * Service layer for managing Integrator resources.
 *
 * @package App\Services
 */
class IntegratorService extends BaseService
{
    /**
     * IntegratorService constructor.
     *
     * @param IntegratorRepositoryInterface $repository
     */
    public function __construct(IntegratorRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get all integrators with specified relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllIntegratorsWithRelations(array $relations = []): Collection
    {
        return $this->repository->getAllWithRelations($relations);
    }

    /**
     * Find an integrator by ID with specified relations.
     *
     * @param int $id
     * @param array $relations
     * @return Integrator|null
     */
    public function findIntegratorWithRelations(int $id, array $relations = []): ?Integrator
    {
        return $this->repository->findWithRelations($id, $relations);
    }

    /**
     * Create a new integrator.
     *
     * @param array $data
     * @return Integrator
     */
    public function createIntegrator(array $data): Integrator
    {
        // Add any specific business logic for creating an integrator here
        $integrator = $this->repository->create($data);
        
        // S'assurer que l'utilisateur associé a le rôle integrator
        // (cette logique est également dans l'événement created du modèle, mais on la garde ici pour garantir)
        if (isset($data['user_id']) && $data['user_id']) {
            $user = \App\Models\User::find($data['user_id']);
            if ($user && !$user->hasRole('integrator')) {
                $user->assignRole('integrator');
                if (!$user->integrator_id) {
                    $user->update(['integrator_id' => $integrator->id]);
                }
            }
        }
        
        return $integrator;
    }

    /**
     * Create an integrator and an associated admin user.
     *
     * @param array $integratorData
     * @param string $adminEmail
     * @param string $adminPassword
     * @return Integrator
     */
    public function createIntegratorWithAdminUser(array $integratorData, string $adminEmail, string $adminPassword): Integrator
    {
        \DB::beginTransaction();
        try {
            // Créer l'intégrateur
            $integrator = $this->createIntegrator($integratorData);

            // Créer l'utilisateur admin
            $user = new \App\Models\User();
            $user->name = ($integratorData['name'] ?? 'Intégrateur') . ' Admin';
            $user->email = $adminEmail;
            $user->password = \Hash::make($adminPassword);
            $user->integrator_id = $integrator->id;
            $user->role = 'integrator';
            $user->save();

            // Assigner le rôle integrator
            $user->assignRole('integrator');

            \DB::commit();
            return $integrator;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw new \App\Exceptions\CrudException('Erreur lors de la création de l\'intégrateur: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing integrator.
     *
     * @param Integrator $integrator
     * @param array $data
     * @return Integrator
     */
    public function updateIntegrator(Integrator $integrator, array $data): Integrator
    {
        // Add any specific business logic for updating an integrator here
        return $this->repository->update($integrator, $data);
    }

    /**
     * Delete an integrator.
     *
     * @param Integrator $integrator
     * @return bool|null
     */
    public function deleteIntegrator(Integrator $integrator): ?bool
    {
        // Empêcher la suppression si l'intégrateur est rattaché à un business profil
        if ($integrator->business_profile_id) {
            throw new \App\Exceptions\CrudException('Impossible de supprimer cet intégrateur car il est rattaché à un profil business.');
        }
        // Add any specific business logic for deleting an integrator here
        // e.g., checking for associated resources
        return $this->repository->delete($integrator);
    }

    // Add other Integrator specific service methods here
}