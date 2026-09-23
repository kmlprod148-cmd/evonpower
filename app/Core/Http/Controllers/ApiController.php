<?php

namespace App\Core\Http\Controllers;

use App\Core\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Classe ApiController
 * Contrôleur de base pour toutes les API
 */
class ApiController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, ApiResponse;

    /**
     * Le service à utiliser
     * 
     * @var mixed
     */
    protected $service;

    /**
     * ApiController constructor.
     * 
     * @param mixed $service
     */
    public function __construct($service = null)
    {
        $this->service = $service;
    }

    /**
     * Récupère la liste des éléments
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request = null)
    {
        try {
            $data = $this->service->getAll();
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->handleApiException($e);
        }
    }

    /**
     * Récupère un élément par son ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        try {
            $data = $this->service->getById($id);
            
            if (!$data) {
                return $this->errorResponse('Ressource non trouvée', 404);
            }
            
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->handleApiException($e);
        }
    }

    /**
     * Crée un nouvel élément
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $data = $this->service->create($request->validated());
            return $this->successResponse($data, 'Ressource créée avec succès', 201);
        } catch (\Exception $e) {
            return $this->handleApiException($e);
        }
    }

    /**
     * Met à jour un élément
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        try {
            $data = $this->service->update($id, $request->validated());
            return $this->successResponse($data, 'Ressource mise à jour avec succès');
        } catch (\Exception $e) {
            return $this->handleApiException($e);
        }
    }

    /**
     * Supprime un élément
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            $this->service->delete($id);
            return $this->successResponse(null, 'Ressource supprimée avec succès');
        } catch (\Exception $e) {
            return $this->handleApiException($e);
        }
    }
}