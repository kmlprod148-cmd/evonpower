<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\ChargingPoint;

class DiagnosticsController extends Controller
{
    /**
     * Recevoir et stocker les fichiers de diagnostic uploadés par Steve
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        try {
            // Récupérer les paramètres de la requête
            $chargePointId = $request->input('chargePointId') ?? $request->header('X-ChargePoint-Id');
            $fileName = $request->input('fileName') ?? $request->header('X-FileName');
            
            Log::info('DiagnosticsController: Receiving diagnostic file upload', [
                'chargePointId' => $chargePointId,
                'fileName' => $fileName,
                'has_file' => $request->hasFile('file'),
                'request_data' => $request->all()
            ]);

            // Vérifier si un fichier est présent
            if (!$request->hasFile('file') && !$request->has('fileData')) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Aucun fichier fourni'
                ], 400);
            }

            // Trouver le point de charge
            $chargingPoint = null;
            if ($chargePointId) {
                // Chercher par steve_charging_point_id
                $chargingPoint = ChargingPoint::where('steve_charging_point_id', $chargePointId)->first();
            }

            // Créer le dossier de stockage
            $storagePath = 'diagnostics';
            if ($chargingPoint) {
                $storagePath = 'diagnostics/' . $chargingPoint->id;
            } elseif ($chargePointId) {
                $storagePath = 'diagnostics/' . $chargePointId;
            }

            // Utiliser storage_path('app') directement pour stocker dans storage/app/diagnostics
            $fullStoragePath = storage_path('app/' . $storagePath);
            
            // Créer le dossier s'il n'existe pas
            if (!is_dir($fullStoragePath)) {
                mkdir($fullStoragePath, 0755, true);
            }

            // Gérer l'upload du fichier
            $storedFileName = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $storedFileName = ($fileName ?? pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move($fullStoragePath, $storedFileName);
            } elseif ($request->has('fileData')) {
                // Si les données du fichier sont dans le body
                $fileData = $request->input('fileData');
                if (is_string($fileData)) {
                    // Essayer de décoder en base64 si nécessaire
                    $decoded = base64_decode($fileData, true);
                    $fileData = ($decoded !== false) ? $decoded : $fileData;
                }
                $extension = pathinfo($fileName ?? '', PATHINFO_EXTENSION) ?: 'txt';
                $storedFileName = ($fileName ?? 'diagnostic_' . time()) . '.' . $extension;
                file_put_contents($fullStoragePath . '/' . $storedFileName, $fileData);
            } elseif ($request->has('content')) {
                // Si le contenu est directement dans 'content'
                $content = $request->input('content');
                if (is_string($content)) {
                    $decoded = base64_decode($content, true);
                    $content = ($decoded !== false) ? $decoded : $content;
                }
                $storedFileName = ($fileName ?? 'diagnostic_' . time()) . '.txt';
                file_put_contents($fullStoragePath . '/' . $storedFileName, $content);
            }

            if (!$storedFileName) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Impossible de sauvegarder le fichier'
                ], 500);
            }

            $fullPath = $storagePath . '/' . $storedFileName;

            Log::info('DiagnosticsController: Diagnostic file saved', [
                'charging_point_id' => $chargingPoint?->id,
                'chargePointId' => $chargePointId,
                'file_path' => $fullPath,
                'file_name' => $storedFileName
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Fichier de diagnostic reçu et stocké',
                'data' => [
                    'file_path' => $fullPath,
                    'file_name' => $storedFileName,
                    'charging_point_id' => $chargingPoint?->id,
                    'download_url' => config('app.url') . '/api/diagnostics/download/' . base64_encode($fullPath),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('DiagnosticsController: Failed to upload diagnostic file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Erreur lors de la réception du fichier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger un fichier de diagnostic
     * 
     * @param Request $request
     * @param string $path
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function download(Request $request, $path)
    {
        try {
            $filePath = base64_decode($path);
            
            // Sécurité : vérifier que le chemin est dans le dossier diagnostics
            if (!str_starts_with($filePath, 'diagnostics/')) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Chemin de fichier invalide'
                ], 403);
            }

            $fullPath = storage_path('app/' . $filePath);
            
            if (!file_exists($fullPath)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Fichier introuvable'
                ], 404);
            }

            return response()->download($fullPath);

        } catch (\Exception $e) {
            Log::error('DiagnosticsController: Failed to download diagnostic file', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
            ], 500);
        }
    }
}
