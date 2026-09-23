<?php

namespace App\Core\Interfaces;

/**
 * Interface BaseServiceInterface
 * Définit les méthodes de base pour tous les services
 */
interface BaseServiceInterface
{
    /**
     * Récupère tous les éléments
     * 
     * @param array $filters Filtres optionnels
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(array $filters = []);
    
    /**
     * Récupère tous les éléments avec pagination
     * 
     * @param int $perPage Nombre d'éléments par page
     * @param array $filters Filtres optionnels
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, array $filters = []);
    
    /**
     * Récupère un élément par son ID
     * 
     * @param int $id ID de l'élément
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getById(int $id);
    
    /**
     * Crée un nouvel élément
     * 
     * @param array $data Données de l'élément
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data);
    
    /**
     * Met à jour un élément
     * 
     * @param int $id ID de l'élément
     * @param array $data Données à mettre à jour
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update(int $id, array $data);
    
    /**
     * Supprime un élément
     * 
     * @param int $id ID de l'élément
     * @return bool
     */
    public function delete(int $id);
}