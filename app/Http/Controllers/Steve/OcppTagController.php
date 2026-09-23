<?php

declare(strict_types=1);

namespace App\Http\Controllers\Steve;

use App\Exceptions\SteVeConfigurationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Steve\Concerns\RespondsWithSteveEnvelope;
use App\Http\Requests\Steve\OcppTag\StoreOcppTagRequest;
use App\Http\Requests\Steve\OcppTag\UpdateOcppTagRequest;
use App\Http\Responses\ApiEnvelope;
use App\Services\SteVeHttpClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * BFF for SteVe's `/manager/api/v1/ocppTags` resource.
 *
 * Routes (all under /api/v1/steve, auth:sanctum gated):
 *   GET    /ocpp-tags           → index
 *   POST   /ocpp-tags           → store
 *   GET    /ocpp-tags/{pk}      → show
 *   PUT    /ocpp-tags/{pk}      → update
 *   DELETE /ocpp-tags/{pk}      → destroy
 */
class OcppTagController extends Controller
{
    use RespondsWithSteveEnvelope;

    private const TRISTATE      = ['ALL', 'TRUE', 'FALSE'];
    private const USER_FILTERS  = ['All', 'OnlyTagsWithUser', 'OnlyTagsWithoutUser'];

    public function __construct(
        private readonly SteVeHttpClientService $steve,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->validate([
                'ocppTagPk'     => ['sometimes', 'integer', 'min:1'],
                'idTag'         => ['sometimes', 'string', 'max:64'],
                'parentIdTag'   => ['sometimes', 'string', 'max:64'],
                'userId'        => ['sometimes', 'integer', 'min:1'],
                'expired'       => ['sometimes', 'string', 'in:' . implode(',', self::TRISTATE)],
                'inTransaction' => ['sometimes', 'string', 'in:' . implode(',', self::TRISTATE)],
                'blocked'       => ['sometimes', 'string', 'in:' . implode(',', self::TRISTATE)],
                'note'          => ['sometimes', 'string'],
                'userFilter'    => ['sometimes', 'string', 'in:' . implode(',', self::USER_FILTERS)],
            ]);

            $result = $this->steve->listOcppTags($filters);

            return $this->respondFromServiceResult(
                $result,
                $result['message'] ?? 'Liste des tags OCPP récupérée',
                ['count' => $result['count'] ?? 0],
            );
        } catch (ValidationException|SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('index', $e, 'ocpp_tags_list_failed');
        }
    }

    public function store(StoreOcppTagRequest $request): JsonResponse
    {
        try {
            $result = $this->steve->createOcppTag($request->validated());

            if (($result['success'] ?? false) === true) {
                return response()->json(
                    ApiEnvelope::ok(
                        $result['message'] ?? 'Tag OCPP créé avec succès',
                        $result['data'] ?? null,
                    ),
                    201,
                );
            }

            return $this->respondFromServiceResult($result);
        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('store', $e, 'ocpp_tag_create_failed');
        }
    }

    public function show(int $ocppTagPk): JsonResponse
    {
        try {
            return $this->respondFromServiceResult($this->steve->getOcppTag($ocppTagPk));
        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('show', $e, 'ocpp_tag_show_failed', $ocppTagPk);
        }
    }

    public function update(UpdateOcppTagRequest $request, int $ocppTagPk): JsonResponse
    {
        try {
            return $this->respondFromServiceResult(
                $this->steve->updateOcppTag($ocppTagPk, $request->validated()),
            );
        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('update', $e, 'ocpp_tag_update_failed', $ocppTagPk);
        }
    }

    public function destroy(int $ocppTagPk): JsonResponse
    {
        try {
            return $this->respondFromServiceResult($this->steve->deleteOcppTag($ocppTagPk));
        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('destroy', $e, 'ocpp_tag_delete_failed', $ocppTagPk);
        }
    }

    private function logAndFail(string $action, Throwable $e, string $code, int|string|null $id = null): JsonResponse
    {
        Log::error("Steve\\OcppTagController: {$action} failed", [
            'id'    => $id,
            'error' => $e->getMessage(),
        ]);

        return $this->serverErrorResponse('Erreur SteVe (' . $action . ')', $code);
    }
}
