<?php

namespace App\Repositories;

use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ReservationRepository
{
    public function all(): Collection
    {
        return Reservation::visibleToUser(Auth::user())->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Reservation::visibleToUser(Auth::user())->latest()->paginate($perPage);
    }

    public function find(int $id): ?Model
    {
        return Reservation::visibleToUser(Auth::user())->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return Reservation::visibleToUser(Auth::user())->findOrFail($id);
    }

    public function findBy(string $field, $value): ?Model
    {
        return Reservation::visibleToUser(Auth::user())->where($field, $value)->first();
    }

    public function findWhere(array $criteria): Collection
    {
        $query = Reservation::visibleToUser(Auth::user());
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        return $query->get();
    }
}
