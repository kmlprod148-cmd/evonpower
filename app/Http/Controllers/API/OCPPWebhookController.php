<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Ocpp\OcppEventIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OCPPWebhookController extends Controller
{
    public function __construct(protected OcppEventIngestionService $ingestionService)
    {
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $messageType = $payload['messageType'] ?? $payload['type'] ?? null;
        $chargeBoxId = $payload['chargeBoxId'] ?? $payload['charge_point_id'] ?? null;

        if (!$messageType || !$chargeBoxId) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required fields: messageType or chargeBoxId',
            ], 400);
        }

        try {
            $this->ingestionService->ingest($messageType, $chargeBoxId, $payload, $messageType);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function startTransaction(Request $request): JsonResponse
    {
        return $this->handleWebhook($request);
    }

    public function stopTransaction(Request $request): JsonResponse
    {
        return $this->handleWebhook($request);
    }

    public function statusNotification(Request $request): JsonResponse
    {
        return $this->handleWebhook($request);
    }
}
