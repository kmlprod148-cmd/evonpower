<?php

namespace App\Services;

use App\Models\ClientUser;
use App\Models\CreditRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditRequestService
{
    protected $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->creditService = $creditService;
    }

    /**
     * Creer une demande de credit par un client.
     *
     * @param int $userId ID du client
     * @param float $amount Montant demande
     * @param string $type Type de credit
     * @param string|null $description Description de la demande
     * @return array
     */
    public function createRequest(int $userId, float $amount, string $type = 'manuel', ?string $description = null): array
    {
        try {
            DB::beginTransaction();

            $user = $this->resolveCreditRequestUser($userId);
            $ownerId = $this->resolveCreditRequestOwnerId($user);

            // Valider le montant
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Le montant doit etre superieur a 0.');
            }

            // Valider le type
            if (!in_array($type, ['bonus', 'manuel', 'automatique'], true)) {
                throw new \InvalidArgumentException('Type de credit invalide.');
            }

            // Creer la demande
            $request = CreditRequest::create([
                'client_id' => $user->id,
                'owner_id' => $ownerId,
                'amount' => $amount,
                'request_type' => $type,
                'reason' => $description,
                'status' => 'pending',
            ]);

            DB::commit();

            Log::info('Demande de credit creee', [
                'request_id' => $request->id,
                'requested_user_id' => $userId,
                'client_id' => $user->id,
                'owner_id' => $ownerId,
                'amount' => $amount,
                'request_type' => $type,
            ]);

            return [
                'success' => true,
                'request' => $request,
                'message' => 'Votre demande de credit a ete soumise avec succes. Elle sera examinee par un administrateur.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la creation de la demande de credit', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function resolveCreditRequestUser(int $userId): User
    {
        $user = User::find($userId);
        if ($user && $this->isEligibleCreditRequester($user)) {
            Log::info('Credit request user resolved from users table', [
                'requested_user_id' => $userId,
                'resolved_user_id' => $user->id,
                'roles' => $user->getRoleNames()->toArray(),
            ]);

            return $user;
        }

        $clientUser = ClientUser::with('user')->find($userId);
        if ($clientUser?->user && $this->isEligibleCreditRequester($clientUser->user)) {
            Log::info('Credit request user resolved from client_users link', [
                'requested_user_id' => $userId,
                'client_user_id' => $clientUser->id,
                'resolved_user_id' => $clientUser->user->id,
                'roles' => $clientUser->user->getRoleNames()->toArray(),
            ]);

            return $clientUser->user;
        }

        if ($clientUser && !$clientUser->user) {
            throw new \Exception('Impossible de creer la demande de credit - compte utilisateur non configure.');
        }

        if ($user) {
            Log::warning('Credit request denied for non-client user', [
                'requested_user_id' => $userId,
                'roles' => $user->getRoleNames()->toArray(),
            ]);

            throw new \Exception('Seuls les clients ou les utilisateurs finaux peuvent faire une demande de credit.');
        }

        throw new \Exception('Utilisateur non trouve.');
    }

    protected function isEligibleCreditRequester(User $user): bool
    {
        $roles = $user->getRoleNames()
            ->map(fn ($role) => strtolower((string) $role))
            ->toArray();

        $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
        $clientRoles = ['user', 'client'];

        $hasSystemRole = !empty(array_intersect($roles, $systemRoles));
        $hasClientRole = !empty(array_intersect($roles, $clientRoles));

        return !$hasSystemRole && ($hasClientRole || empty($roles));
    }

    protected function resolveCreditRequestOwnerId(User $user): int
    {
        $creator = $user->creator;
        if ($creator && $creator->hasRole(['admin', 'super_admin', 'integrator', 'operator', 'partner'])) {
            return (int) $creator->id;
        }

        $owner = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['super_admin', 'admin']);
            })
            ->orderBy('id')
            ->first();

        if (!$owner) {
            $owner = User::query()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['integrator', 'operator', 'partner']);
                })
                ->orderBy('id')
                ->first();
        }

        if (!$owner) {
            throw new \Exception('Aucun approbateur disponible pour traiter la demande de credit.');
        }

        return (int) $owner->id;
    }

    /**
     * Approuver une demande de credit
     *
     * @param int $requestId ID de la demande
     * @param int $adminId ID de l'admin qui approuve
     * @return array
     */
    public function approveRequest(int $requestId, int $adminId): array
    {
        try {
            DB::beginTransaction();

            // Verifier que l'admin a les permissions
            $admin = User::findOrFail($adminId);
            if (!$admin->hasRole(['admin', 'super_admin', 'integrator'])) {
                throw new \Exception('Seuls les administrateurs peuvent approuver les demandes.');
            }

            // Recuperer la demande
            $request = CreditRequest::findOrFail($requestId);

            // Verifier que la demande est en attente
            if (!$request->isPending()) {
                throw new \Exception('Cette demande ne peut plus etre approuvee.');
            }

            // Ajouter le credit au client via CreditService
            $creditResult = $this->creditService->addCredit(
                $request->user_id,
                $request->amount,
                $request->type,
                $request->description ?? 'Credit approuve suite a une demande',
                $adminId
            );

            if (!isset($creditResult['success']) || !$creditResult['success']) {
                throw new \Exception($creditResult['message'] ?? 'Erreur lors de l\'ajout du credit.');
            }

            // Mettre a jour la demande
            $request->update([
                'status' => 'approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'metadata' => [
                    'credit_transaction_id' => $creditResult['credit_transaction']->id ?? null,
                    'wallet_transaction_id' => $creditResult['wallet_transaction']->id ?? null,
                    'new_balance' => $creditResult['new_balance'] ?? null,
                ],
            ]);

            DB::commit();

            Log::info('Demande de credit approuvee', [
                'request_id' => $requestId,
                'admin_id' => $adminId,
                'user_id' => $request->user_id,
                'amount' => $request->amount,
            ]);

            return [
                'success' => true,
                'request' => $request,
                'credit_result' => $creditResult,
                'message' => 'La demande de credit a ete approuvee et le credit a ete ajoute au wallet du client.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'approbation de la demande de credit', [
                'request_id' => $requestId,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Rejeter une demande de credit
     *
     * @param int $requestId ID de la demande
     * @param int $adminId ID de l'admin qui rejette
     * @param string|null $reason Raison du rejet
     * @return array
     */
    public function rejectRequest(int $requestId, int $adminId, ?string $reason = null): array
    {
        try {
            DB::beginTransaction();

            // Verifier que l'admin a les permissions
            $admin = User::findOrFail($adminId);
            if (!$admin->hasRole(['admin', 'super_admin', 'integrator'])) {
                throw new \Exception('Seuls les administrateurs peuvent rejeter les demandes.');
            }

            // Recuperer la demande
            $request = CreditRequest::findOrFail($requestId);

            // Verifier que la demande est en attente
            if (!$request->isPending()) {
                throw new \Exception('Cette demande ne peut plus etre rejetee.');
            }

            // Mettre a jour la demande
            $request->update([
                'status' => 'rejected',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            DB::commit();

            Log::info('Demande de credit rejetee', [
                'request_id' => $requestId,
                'admin_id' => $adminId,
                'user_id' => $request->user_id,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'request' => $request,
                'message' => 'La demande de credit a ete rejetee.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors du rejet de la demande de credit', [
                'request_id' => $requestId,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Annuler une demande de credit (par le client)
     *
     * @param int $requestId ID de la demande
     * @param int $userId ID du client
     * @return array
     */
    public function cancelRequest(int $requestId, int $userId): array
    {
        try {
            DB::beginTransaction();

            // Recuperer la demande
            $request = CreditRequest::findOrFail($requestId);

            // Verifier que c'est bien le client qui a fait la demande
            if ($request->user_id !== $userId) {
                throw new \Exception('Vous ne pouvez annuler que vos propres demandes.');
            }

            // Verifier que la demande peut etre annulee
            if (!$request->canBeCancelled()) {
                throw new \Exception('Cette demande ne peut plus etre annulee.');
            }

            // Mettre a jour la demande
            $request->update([
                'status' => 'cancelled',
            ]);

            DB::commit();

            Log::info('Demande de credit annulee', [
                'request_id' => $requestId,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'request' => $request,
                'message' => 'La demande de credit a ete annulee.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'annulation de la demande de credit', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Recuperer les demandes en attente pour l'admin
     *
     * @param array $filters Filtres optionnels
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPendingRequests(array $filters = [])
    {
        $query = CreditRequest::with(['user', 'approver'])
            ->pending()
            ->orderBy('created_at', 'desc');

        if (isset($filters['user_id']) && $filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['type']) && $filters['type']) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Recuperer les demandes d'un client
     *
     * @param int $userId ID du client
     * @param array $filters Filtres optionnels
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getClientRequests(int $userId, array $filters = [])
    {
        $query = CreditRequest::with(['user', 'approver'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type']) && $filters['type']) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Recuperer toutes les demandes pour l'admin (avec filtres)
     *
     * @param array $filters Filtres optionnels
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllRequests(array $filters = [])
    {
        $query = CreditRequest::with(['user', 'approver'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['status']) && $filters['status'] && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id']) && $filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['type']) && $filters['type']) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }
}
