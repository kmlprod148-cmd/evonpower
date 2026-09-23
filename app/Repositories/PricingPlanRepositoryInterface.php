<?php

namespace App\Repositories;

interface PricingPlanRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getDefaultPlanForChargingPoint($chargingPointId);
    public function getAvailablePlansForChargingPoint($chargingPoint);
}