<?php

namespace App\DTO\Charging;

use Illuminate\Http\Request;

class StartChargeDTO
{
    /**
     * ID de la borne de recharge
     * @var int
     */
    public $charging_point_id;

    /**
     * ID du connecteur
     * @var int
     */
    public $connector_id;

    /**
     * ID de l'utilisateur
     * @var int
     */
    public $user_id;

    /**
     * Méthode d'authentification (e.g., "app", "rfid")
     * @var string|null
     */
    public $auth_method;

    /**
     * Identifiant d'authentification (e.g., ID utilisateur, UID RFID)
     * @var string|null
     */
    public $auth_id;

    /**
     * Constructeur
     *
     * @param int $charging_point_id
     * @param int $connector_id
     * @param int $user_id
     * @param string|null $auth_method
     * @param string|null $auth_id
     */
    public function __construct(
        int $charging_point_id,
        int $connector_id,
        int $user_id,
        ?string $auth_method = null,
        ?string $auth_id = null
    ) {
        $this->charging_point_id = $charging_point_id;
        $this->connector_id = $connector_id;
        $this->user_id = $user_id;
        $this->auth_method = $auth_method;
        $this->auth_id = $auth_id;
    }

    /**
     * Crée un DTO à partir d'une requête HTTP
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->input('charging_point_id'),
            $request->input('connector_id'),
            $request->user()->id, // Assumes authenticated user
            $request->input('auth_method'),
            $request->input('auth_id')
        );
    }

    /**
     * Convertit le DTO en tableau
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'charging_point_id' => $this->charging_point_id,
            'connector_id' => $this->connector_id,
            'user_id' => $this->user_id,
            'auth_method' => $this->auth_method,
            'auth_id' => $this->auth_id,
        ];
    }
}