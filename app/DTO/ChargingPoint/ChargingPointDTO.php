<?php

namespace App\DTO\ChargingPoint;

class ChargingPointDTO
{
    public int $id;
    public string $name;
    public string $serialNumber;
    public string $status;
    public ?string $location;
    public ?float $latitude;
    public ?float $longitude;

    /**
     * Constructeur pour initialiser les propriétés du DTO.
     *
     * @param int $id
     * @param string $name
     * @param string $serialNumber
     * @param string $status
     * @param string|null $location
     * @param float|null $latitude
     * @param float|null $longitude
     */
    public function __construct(
        int $id,
        string $name,
        string $serialNumber,
        string $status,
        ?string $location = null,
        ?float $latitude = null,
        ?float $longitude = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->serialNumber = $serialNumber;
        $this->status = $status;
        $this->location = $location;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Crée un DTO à partir d'un tableau de données.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['serial_number'],
            $data['status'],
            $data['location'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null
        );
    }

    /**
     * Convertit le DTO en tableau.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'serial_number' => $this->serialNumber,
            'status' => $this->status,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
