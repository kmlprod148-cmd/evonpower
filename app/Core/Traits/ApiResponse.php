<?php

namespace App\Core\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Trait ApiResponse
 * Fournit des méthodes standardisées pour les réponses API
 */
trait ApiResponse
{
    /**
     * Retourne une réponse de succès
     * 
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Retourne une réponse d'erreur
     * 
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Une erreur est survenue', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Gère les exceptions et retourne une réponse API appropriée
     * 
     * @param Throwable $exception
     * @return JsonResponse
     */
    protected function handleApiException(Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return $this->errorResponse(
                'Erreur de validation',
                422,
                $exception->errors()
            );
        }

        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return $this->errorResponse(
                'Ressource non trouvée',
                404
            );
        }

        // En environnement de production, ne pas exposer les détails de l'erreur
        if (config('app.debug')) {
            return $this->errorResponse(
                $exception->getMessage(),
                500,
                [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ]
            );
        }

        return $this->errorResponse(
            'Une erreur interne du serveur s\'est produite',
            500
        );
    }
}
