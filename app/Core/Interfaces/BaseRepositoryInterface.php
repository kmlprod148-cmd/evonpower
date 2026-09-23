<?php

namespace App\Core\Interfaces;

/**
 * Interface BaseRepositoryInterface
 * Définit les méthodes de base pour tous les repositories
 */
interface BaseRepositoryInterface
{
    /**
     * Récupère tous les éléments
     * 
     * @param array $columns Colonnes à récupérer
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $columns = ['*']);
    
    /**
     * Récupère tous les éléments avec pagination
     * 
     * @param int $perPage Nombre d'éléments par page
     * @param array $columns Colonnes à récupérer
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']);
    
    /**
     * Récupère un élément par son ID
     * 
     * @param int $id ID de l'élément
     * @param array $columns Colonnes à récupérer
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function find(int $id, array $columns = ['*']);
    
    /**
     * Récupère un élément par son ID ou échoue
     * 
     * @param int $id ID de l'élément
     * @param array $columns Colonnes à récupérer
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*']);
    
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