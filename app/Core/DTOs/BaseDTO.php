<?php

namespace App\Core\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionProperty;

/**
 * Classe BaseDTO
 * Classe de base pour tous les DTOs (Data Transfer Objects)
 */
abstract class BaseDTO implements Arrayable
{
    /**
     * Crée un nouveau DTO à partir d'une requête HTTP
     * 
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request)
    {
        return new static($request->all());
    }

    /**
     * Crée un nouveau DTO à partir d'un modèle
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return static
     */
    public static function fromModel($model)
    {
        return new static($model->toArray());
    }

    /**
     * Crée un nouveau DTO à partir d'un tableau
     * 
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data)
    {
        return new static($data);
    }

    /**
     * Convertit le DTO en tableau
     * 
     * @return array
     */
    public function toArray()
    {
        $array = [];
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $array[$propertyName] = $this->{$propertyName};
        }

        return $array;
    }
}