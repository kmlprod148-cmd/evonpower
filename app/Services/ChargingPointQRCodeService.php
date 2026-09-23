<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Repositories\ChargingPointRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Exception;

class ChargingPointQRCodeService
{
    /**
     * @var ChargingPointRepositoryInterface
     */
    protected $chargingPointRepository;

    /**
     * @var QRCodeService
     */
    protected $qrCodeService;

    /**
     * Storage directory for QR codes
     */
    protected string $storageDir = 'qrcodes';

    public function __construct(
        ChargingPointRepositoryInterface $chargingPointRepository,
        QRCodeService $qrCodeService
    ) {
        $this->chargingPointRepository = $chargingPointRepository;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Génère un QR code pour une borne de recharge
     *
     * @param int $chargingPointId
     * @return array
     * @throws Exception
     */
    public function generate(int $chargingPointId): array
    {
        Log::info('Début génération QR code pour la borne ID: ' . $chargingPointId);

        // Vérifier que la borne existe
        $chargingPoint = $this->chargingPointRepository->findById($chargingPointId);
        if (!$chargingPoint) {
            throw new Exception("Borne de recharge non trouvée avec l'ID: {$chargingPointId}");
        }

        try {
            // Utiliser le service QR existant
            $qrCodeUrl = $this->qrCodeService->generateAndSaveQRCode($chargingPointId);
            
            // Générer l'URL publique de l'offre
            $publicUrl = route('public.charging-point.offer.reservation', $chargingPointId);
            
            // Normaliser l'URL en HTTPS
            $publicUrl = $this->normalizeUrlToHttps($publicUrl);

            $result = [
                'qr_code_url' => $qrCodeUrl,
                'charging_point_id' => $chargingPointId,
                'public_url' => $publicUrl,
                'charging_point_name' => $chargingPoint->name,
                'generated_at' => now()->toISOString()
            ];

            Log::info('QR code généré avec succès', $result);
            return $result;

        } catch (Exception $e) {
            Log::error("Erreur lors de la génération du QR code pour la borne {$chargingPointId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère le QR code d'une borne de recharge
     *
     * @param int $chargingPointId
     * @return array
     * @throws Exception
     */
    public function get(int $chargingPointId): array
    {
        Log::info('Récupération QR code pour la borne ID: ' . $chargingPointId);

        // Vérifier que la borne existe
        $chargingPoint = $this->chargingPointRepository->findById($chargingPointId);
        if (!$chargingPoint) {
            throw new Exception("Borne de recharge non trouvée avec l'ID: {$chargingPointId}");
        }

        // Chercher le fichier QR code existant
        $qrCodeFile = $this->findExistingQRCode($chargingPointId);
        
        if (!$qrCodeFile) {
            // Générer un nouveau QR code s'il n'existe pas
            Log::info('Aucun QR code trouvé, génération d\'un nouveau');
            return $this->generate($chargingPointId);
        }

        $publicUrl = route('public.charging-point.offer.reservation', $chargingPointId);
        $publicUrl = $this->normalizeUrlToHttps($publicUrl);

        return [
            'qr_code_url' => $qrCodeFile,
            'charging_point_id' => $chargingPointId,
            'public_url' => $publicUrl,
            'charging_point_name' => $chargingPoint->name,
            'exists' => true
        ];
    }

    /**
     * Télécharge le QR code d'une borne de recharge
     *
     * @param int $chargingPointId
     * @return array
     * @throws Exception
     */
    public function download(int $chargingPointId): array
    {
        Log::info('Téléchargement QR code pour la borne ID: ' . $chargingPointId);

        $qrCodeData = $this->get($chargingPointId);
        
        // Générer le QR code en base64 pour le téléchargement
        $dataUrl = $this->qrCodeService->generateAsDataUrl($chargingPointId);
        
        return array_merge($qrCodeData, [
            'download_url' => $dataUrl,
            'filename' => "qr_code_borne_{$chargingPointId}.svg"
        ]);
    }

    /**
     * Régénère le QR code d'une borne de recharge
     *
     * @param int $chargingPointId
     * @return array
     * @throws Exception
     */
    public function regenerate(int $chargingPointId): array
    {
        Log::info('Régénération QR code pour la borne ID: ' . $chargingPointId);

        // Supprimer l'ancien QR code
        $this->deleteExistingQRCodes($chargingPointId);
        
        // Générer un nouveau QR code
        return $this->generate($chargingPointId);
    }

    /**
     * Supprime les QR codes existants pour une borne
     *
     * @param int $chargingPointId
     * @return bool
     */
    protected function deleteExistingQRCodes(int $chargingPointId): bool
    {
        try {
            $pattern = "charging_point_{$chargingPointId}_*.svg";
            $files = Storage::disk('public')->files($this->storageDir);
            
            foreach ($files as $file) {
                if (Str::is($this->storageDir . '/' . $pattern, $file)) {
                    Storage::disk('public')->delete($file);
                    Log::info("QR code supprimé: {$file}");
                }
            }
            
            return true;
        } catch (Exception $e) {
            Log::warning("Erreur lors de la suppression des QR codes: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouve le QR code existant pour une borne
     *
     * @param int $chargingPointId
     * @return string|null
     */
    protected function findExistingQRCode(int $chargingPointId): ?string
    {
        try {
            $pattern = "charging_point_{$chargingPointId}_*.svg";
            $files = Storage::disk('public')->files($this->storageDir);
            
            foreach ($files as $file) {
                if (Str::is($this->storageDir . '/' . $pattern, $file)) {
                    $publicUrl = Storage::disk('public')->url($file);
                    return $this->normalizeUrlToHttps($publicUrl);
                }
            }
            
            return null;
        } catch (Exception $e) {
            Log::warning("Erreur lors de la recherche du QR code: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Normalise l'URL pour utiliser HTTPS
     *
     * @param string $url
     * @return string
     */
    protected function normalizeUrlToHttps(string $url): string
    {
        if (Str::startsWith($url, 'http://')) {
            $url = preg_replace('/^http:/i', 'https:', $url);
        }
        return $url;
    }

    /**
     * Vérifie si un QR code existe pour une borne
     *
     * @param int $chargingPointId
     * @return bool
     */
    public function exists(int $chargingPointId): bool
    {
        return $this->findExistingQRCode($chargingPointId) !== null;
    }

    /**
     * Récupère les informations de toutes les bornes avec leurs QR codes
     *
     * @return array
     */
    public function getAllWithQRCodes(): array
    {
        $chargingPoints = $this->chargingPointRepository->all();
        $result = [];

        foreach ($chargingPoints as $chargingPoint) {
            $qrCodeExists = $this->exists($chargingPoint->id);
            $result[] = [
                'id' => $chargingPoint->id,
                'name' => $chargingPoint->name,
                'qr_code_exists' => $qrCodeExists,
                'qr_code_url' => $qrCodeExists ? $this->findExistingQRCode($chargingPoint->id) : null
            ];
        }

        return $result;
    }
}
