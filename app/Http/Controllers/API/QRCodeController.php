<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Traits\ApiResponse;
use App\Services\QRCodeService;
use App\Services\ChargingPointQRCodeService;
use App\Repositories\ChargingPointRepositoryInterface;
use App\Exceptions\ChargingPoint\ChargingPointNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * @OA\Tag(
 *     name="QR Codes",
 *     description="API pour la gestion des QR codes des bornes de recharge"
 * )
 */
class QRCodeController extends ApiController
{
    use ApiResponse;

    /**
     * Service de gestion des QR codes
     *
     * @var QRCodeService
     */
    protected $qrCodeService;

    /**
     * Repository des bornes de recharge
     *
     * @var ChargingPointRepositoryInterface
     */
    protected $chargingPointRepository;

    /**
     * Service pour la logique métier des QR codes de bornes de recharge
     *
     * @var ChargingPointQRCodeService
     */
    protected $chargingPointQRCodeService;


    /**
     * Constructor avec injection des dépendances
     *
     * @param ChargingPointRepositoryInterface $chargingPointRepository
     * @param QRCodeService $qrCodeService
     * @param ChargingPointQRCodeService $chargingPointQRCodeService // Inject the new service
     */
    public function __construct(
        ChargingPointRepositoryInterface $chargingPointRepository,
        QRCodeService $qrCodeService,
        ChargingPointQRCodeService $chargingPointQRCodeService
    ) {
        $this->chargingPointRepository = $chargingPointRepository;
        $this->qrCodeService = $qrCodeService;
        $this->chargingPointQRCodeService = $chargingPointQRCodeService; // Assign the new service
    }

    /**
     * Génère un QR code pour une borne de recharge
     *
     * @OA\Post(
     *     path="/api/charging-points/{id}/qrcode",
     *     tags={"QR Codes"},
     *     summary="Génère un QR code pour une borne de recharge",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la borne de recharge",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR code généré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="QR code généré avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="qr_code_url", type="string", example="https://example.com/storage/qrcodes/charging_point_1.svg"),
     *                 @OA\Property(property="charging_point_id", type="integer", example=1),
     *                 @OA\Property(property="public_url", type="string", example="https://example.com/charging-points/1/offer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Borne de recharge non trouvée"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors de la génération du QR code"
     *     )
     * )
     *
     * @param int $id ID de la borne de recharge
     * @return JsonResponse
     */
    public function generate(int $id): JsonResponse
    {
        try {
            Log::info('Début génération QR code', ['id' => $id]);
            $data = $this->chargingPointQRCodeService->generate($id);
            Log::info('QR code généré', ['file' => $data['qr_code_url']]);
            return $this->sendSuccess($data, 'QR code généré avec succès');
        } catch (ChargingPointNotFoundException $e) {
            Log::error('Génération QR code : Borne de recharge non trouvée', ['id' => $id]);
            return $this->sendNotFound($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du QR code', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('QR_GENERATION_ERROR', 'Erreur lors de la génération du QR code', 500, ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Récupère le QR code d'une borne de recharge
     *
     * @OA\Get(
     *     path="/api/charging-points/{id}/qrcode",
     *     tags={"QR Codes"},
     *     summary="Récupère le QR code d'une borne de recharge",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la borne de recharge",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR code récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="QR code récupéré avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="qr_code_url", type="string", example="https://example.com/storage/qrcodes/charging_point_1.svg"),
     *                 @OA\Property(property="charging_point_id", type="integer", example=1),
     *                 @OA\Property(property="generated_at", type="string", format="date-time"),
     *                 @OA\Property(property="public_url", type="string", example="https://example.com/charging-points/1/offer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Borne de recharge non trouvée ou QR code non généré"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors de la récupération du QR code"
     *     )
     * )
     *
     * @param int $id ID de la borne de recharge
     * @return JsonResponse
     */
    public function get(int $id): JsonResponse
    {
        try {
            $data = $this->chargingPointQRCodeService->get($id);
            return $this->sendSuccess($data, 'QR code récupéré avec succès');
        } catch (ChargingPointNotFoundException $e) {
            Log::error('Récupération QR code : Borne de recharge non trouvée', ['id' => $id]);
            return $this->sendNotFound($e->getMessage());
        } catch (\Exception $e) {
             // Check if the error is specifically about the QR code not being generated
            if ($e->getMessage() === 'QR code not generated for this charging point') {
                 return $this->sendError('QR code non généré pour cette borne de recharge', [
                    'charging_point_id' => $id,
                    'generate_url' => route('api.charging-points.qrcode.generate', $id)
                ]);
            }
            Log::error('Erreur lors de la récupération du QR code', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('QR_RETRIEVAL_ERROR', 'Erreur lors de la récupération du QR code', 500, ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Télécharge le QR code d'une borne de recharge
     *
     * @OA\Get(
     *     path="/api/charging-points/{id}/qrcode/download",
     *     tags={"QR Codes"},
     *     summary="Télécharge le QR code d'une borne de recharge",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la borne de recharge",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fichier QR code à télécharger",
     *         @OA\MediaType(
     *             mediaType="image/svg+xml"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Borne de recharge non trouvée ou QR code non généré"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors du téléchargement du QR code"
     *     )
     * )
     *
     * @param int $id ID de la borne de recharge
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function download(int $id)
    {
        try {
            return $this->chargingPointQRCodeService->download($id);
        } catch (ChargingPointNotFoundException $e) {
            Log::error('Téléchargement QR code : Borne de recharge non trouvée', ['id' => $id]);
            return $this->sendNotFound($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du QR code', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('QR_DOWNLOAD_ERROR', 'Erreur lors du téléchargement du QR code', 500, ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Régénère le QR code d'une borne de recharge
     *
     * @OA\Post(
     *     path="/api/charging-points/{id}/qrcode/regenerate",
     *     tags={"QR Codes"},
     *     summary="Régénère le QR code d'une borne de recharge",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la borne de recharge",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR code régénéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="QR code régénéré avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="qr_code_url", type="string", example="https://example.com/storage/qrcodes/charging_point_1.svg"),
     *                 @OA\Property(property="charging_point_id", type="integer", example=1),
     *                 @OA\Property(property="public_url", type="string", example="https://example.com/charging-points/1/offer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Borne de recharge non trouvée"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors de la régénération du QR code"
     *     )
     * )
     *
     * @param int $id ID de la borne de recharge
     * @return JsonResponse
     */
    public function regenerate(int $id): JsonResponse
    {
        try {
            $data = $this->chargingPointQRCodeService->regenerate($id);
            return $this->sendSuccess($data, 'QR code régénéré avec succès');
        } catch (ChargingPointNotFoundException $e) {
            Log::error('Régénération QR code : Borne de recharge non trouvée', ['id' => $id]);
            return $this->sendNotFound($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erreur lors de la régénération du QR code', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('QR_REGENERATION_ERROR', 'Erreur lors de la régénération du QR code', 500, ['exception' => $e->getMessage()]);
        }
    }
}