<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Connector;
use App\Models\ChargingSession;
use App\Models\User;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service pour l'Espace Client
 * 
 * Fonctionnalités:
 * - Carte géolocalisée interactive
 * - Filtrage par type de connecteur, disponibilité, tarification
 * - Gestion des favoris
 * - Historique des sessions
 */
class ClientSpaceService
{
    /**
     * Rayon de recherche par défaut (en km)
     */
    public const DEFAULT_SEARCH_RADIUS = 25;

    /**
     * Rayon maximum de recherche (en km)
     */
    public const MAX_SEARCH_RADIUS = 100;

    /**
     * Nombre maximum de résultats
     */
    public const MAX_RESULTS = 100;

    public function __construct(
        protected MoneyService $moneyService
    ) {}

    /**
     * Obtenir les stations de recharge avec filtrage avancé
     */
    public function getChargingStations(array $filters = []): array
    {
        $query = ChargingPoint::with([
            'connectors',
            'group.partner.integrator',
            'businessProfile',
            'pricingPlans'
        ])
        ->active()
        ->public();

        // Filtrer par région/localisation
        if (isset($filters['latitude']) && isset($filters['longitude'])) {
            $radius = $filters['radius'] ?? self::DEFAULT_SEARCH_RADIUS;
            $query = $this->applyLocationFilter($query, $filters['latitude'], $filters['longitude'], $radius);
        }

        // Filtrer par type de connecteur
        if (!empty($filters['connector_types'])) {
            $query = $this->applyConnectorTypeFilter($query, $filters['connector_types']);
        }

        // Filtrer par disponibilité
        if (isset($filters['available_only']) && $filters['available_only']) {
            $query = $this->applyAvailabilityFilter($query);
        }

        // Filtrer par puissance minimale
        if (isset($filters['min_power'])) {
            $query = $this->applyMinPowerFilter($query, $filters['min_power']);
        }

        // Filtrer par fourchette de prix
        if (isset($filters['max_price_per_kwh'])) {
            $query = $this->applyPriceFilter($query, $filters['max_price_per_kwh']);
        }

        // Filtrer par partenaire
        if (!empty($filters['partner_ids'])) {
            $query->whereHas('group.partner', function ($q) use ($filters) {
                $q->whereIn('id', $filters['partner_ids']);
            });
        }

        // Filtrer par intégrateur
        if (!empty($filters['integrator_ids'])) {
            $query->whereHas('group.partner.integrator', function ($q) use ($filters) {
                $q->whereIn('id', $filters['integrator_ids']);
            });
        }

        // Trier
        $sortBy = $filters['sort_by'] ?? 'distance';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        
        if ($sortBy === 'price') {
            $query = $this->applyPriceSorting($query, $sortOrder);
        } elseif ($sortBy === 'power') {
            $query->orderBy('max_power', $sortOrder);
        }

        // Limiter les résultats
        $stations = $query->limit(self::MAX_RESULTS)->get();

        // Transformer pour la carte
        return $stations->map(function ($station) {
            return $this->transformStationForMap($station);
        })->toArray();
    }

    /**
     * Obtenir une station avec tous ses détails
     */
    public function getStationDetails(int $stationId, ?User $user = null): ?array
    {
        $station = ChargingPoint::with([
            'connectors',
            'group.partner.integrator',
            'businessProfile',
            'pricingPlans',
            'stations'
        ])->find($stationId);

        if (!$station) {
            return null;
        }

        $isFavorite = $user ? $this->isFavorite($user, $stationId) : false;

        return [
            'id' => $station->id,
            'name' => $station->name,
            'description' => $station->description,
            'address' => $station->address,
            'location' => [
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
            ],
            'connectors' => $station->connectors->map(function ($connector) {
                return [
                    'id' => $connector->id,
                    'type' => $connector->type,
                    'status' => $connector->status,
                    'status_label' => $connector->status_label,
                    'power' => $connector->power,
                    'formatted_power' => $connector->formatted_power,
                    'is_available' => $connector->isAvailable(),
                ];
            }),
            'pricing_plans' => $station->pricingPlans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price_per_kwh' => $plan->price_per_kwh,
                    'price_per_minute' => $plan->price_per_minute,
                    'activation_fee' => $plan->activation_fee,
                    'currency' => $plan->currency ?? 'EUR',
                ];
            }),
            'opening_hours' => $station->opening_hours,
            'accessibility' => $station->accessibility,
            'amenities' => $station->amenities ?? [],
            'images' => $station->images ?? [],
            'is_favorite' => $isFavorite,
            'distance' => null, // Calculé côté client avec position utilisateur
        ];
    }

    /**
     * Ajouter une station aux favoris
     */
    public function addFavorite(User $user, int $stationId): bool
    {
        $existingFavorite = DB::table('user_favorites')
            ->where('user_id', $user->id)
            ->where('charging_point_id', $stationId)
            ->first();

        if ($existingFavorite) {
            return true; // Déjà en favoris
        }

        DB::table('user_favorites')->insert([
            'user_id' => $user->id,
            'charging_point_id' => $stationId,
            'created_at' => now(),
        ]);

        Log::info('ClientSpaceService: Favori ajouté', [
            'user_id' => $user->id,
            'station_id' => $stationId,
        ]);

        return true;
    }

    /**
     * Retirer une station des favoris
     */
    public function removeFavorite(User $user, int $stationId): bool
    {
        $deleted = DB::table('user_favorites')
            ->where('user_id', $user->id)
            ->where('charging_point_id', $stationId)
            ->delete();

        Log::info('ClientSpaceService: Favori retiré', [
            'user_id' => $user->id,
            'station_id' => $stationId,
        ]);

        return $deleted > 0;
    }

    /**
     * Vérifier si une station est en favoris
     */
    public function isFavorite(User $user, int $stationId): bool
    {
        return DB::table('user_favorites')
            ->where('user_id', $user->id)
            ->where('charging_point_id', $stationId)
            ->exists();
    }

    /**
     * Obtenir les favoris de l'utilisateur
     */
    public function getUserFavorites(User $user): array
    {
        $favorites = DB::table('user_favorites')
            ->where('user_id', $user->id)
            ->pluck('charging_point_id');

        $stations = ChargingPoint::with(['connectors', 'pricingPlans'])
            ->whereIn('id', $favorites)
            ->get();

        return $stations->map(function ($station) {
            return $this->transformStationForMap($station);
        })->toArray();
    }

    /**
     * Obtenir l'historique des sessions de l'utilisateur
     */
    public function getSessionHistory(User $user, array $filters = []): array
    {
        $query = ChargingSession::where('user_id', $user->id)
            ->with(['chargingPoint', 'pricingPlan']);

        // Filtrer par date
        if (isset($filters['date_from'])) {
            $query->where('start_timestamp', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('start_timestamp', '<=', $filters['date_to']);
        }

        // Filtrer par statut
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtrer par station
        if (!empty($filters['charging_point_id'])) {
            $query->where('charging_point_id', $filters['charging_point_id']);
        }

        // Paginer
        $perPage = $filters['per_page'] ?? 20;
        $sessions = $query->orderBy('start_timestamp', 'desc')
            ->paginate($perPage);

        return [
            'data' => $sessions->map(function ($session) {
                return $this->transformSessionForHistory($session);
            })->toArray(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ];
    }

    /**
     * Obtenir les statistiques des sessions
     */
    public function getSessionStatistics(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $sessions = ChargingSession::where('user_id', $user->id)
            ->whereBetween('start_timestamp', [$startDate, $endDate]);

        $completedSessions = (clone $sessions)->where('status', 'completed');
        $activeSessions = (clone $sessions)->where('status', 'active');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_sessions' => $sessions->count(),
            'completed_sessions' => $completedSessions->count(),
            'active_sessions' => $activeSessions->count(),
            'total_energy_kwh' => (clone $completedSessions)->sum('energy_consumed_kwh'),
            'total_duration_minutes' => (clone $completedSessions)->sum('duration_minutes'),
            'total_spent' => (clone $completedSessions)->sum('actual_cost'),
            'average_session_cost' => $this->calculateAverageCost($completedSessions->get()),
            'average_session_energy' => $this->calculateAverageEnergy($completedSessions->get()),
            'favorite_stations' => $this->getFavoriteStations($user, $startDate, $endDate),
        ];
    }

    /**
     * Rechercher des stations
     */
    public function searchStations(string $query, ?User $user = null): array
    {
        $stations = ChargingPoint::with(['connectors', 'pricingPlans'])
            ->active()
            ->public()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('address', 'like', "%{$query}%")
                  ->orWhere('city', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return $stations->map(function ($station) use ($user) {
            $data = $this->transformStationForMap($station);
            $data['is_favorite'] = $user ? $this->isFavorite($user, $station->id) : false;
            return $data;
        })->toArray();
    }

    // ========================================
    // Méthodes privées de filtrage
    // ========================================

    /**
     * Appliquer le filtre de localisation
     */
    protected function applyLocationFilter($query, float $lat, float $lng, float $radius): mixed
    {
        // Haversine formula pour calculer la distance
        $query->select('charging_points.*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(charging_points.latitude)) * 
                cos(radians(charging_points.longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(charging_points.latitude)))) AS distance)',
                [$lat, $lng, $lat]
            )
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc');

        return $query;
    }

    /**
     * Appliquer le filtre de type de connecteur
     */
    protected function applyConnectorTypeFilter($query, array $connectorTypes): mixed
    {
        return $query->whereHas('connectors', function ($q) use ($connectorTypes) {
            $q->whereIn('type', $connectorTypes);
        });
    }

    /**
     * Appliquer le filtre de disponibilité
     */
    protected function applyAvailabilityFilter($query): mixed
    {
        return $query->whereHas('connectors', function ($q) {
            $q->available();
        });
    }

    /**
     * Appliquer le filtre de puissance minimale
     */
    protected function applyMinPowerFilter($query, float $minPower): mixed
    {
        return $query->whereHas('connectors', function ($q) use ($minPower) {
            $q->where('power', '>=', $minPower);
        });
    }

    /**
     * Appliquer le filtre de prix
     */
    protected function applyPriceFilter($query, float $maxPrice): mixed
    {
        return $query->whereHas('pricingPlans', function ($q) use ($maxPrice) {
            $q->where('price_per_kwh', '<=', $maxPrice);
        });
    }

    /**
     * Appliquer le tri par prix
     */
    protected function applyPriceSorting($query, string $order): mixed
    {
        return $query->select('charging_points.*')
            ->selectRaw('(SELECT MIN(price_per_kwh) FROM pricing_plans 
                WHERE pricing_plans.charging_point_id = charging_points.id) as min_price')
            ->orderBy('min_price', $order);
    }

    // ========================================
    // Méthodes de transformation
    // ========================================

    /**
     * Transformer une station pour l'affichage sur carte
     */
    protected function transformStationForMap(ChargingPoint $station): array
    {
        $connectors = $station->connectors;
        $pricingPlans = $station->pricingPlans;

        return [
            'id' => $station->id,
            'name' => $station->name,
            'address' => $station->address,
            'city' => $station->city,
            'location' => [
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
            ],
            'status' => $station->status,
            'connectors' => [
                'count' => $connectors->count(),
                'available' => $connectors->where('status', Connector::STATUS_AVAILABLE)->count(),
                'types' => $connectors->pluck('type')->unique()->values()->toArray(),
                'max_power' => $connectors->max('power'),
            ],
            'pricing' => [
                'min_price_per_kwh' => $pricingPlans->min('price_per_kwh'),
                'max_price_per_kwh' => $pricingPlans->max('price_per_kwh'),
                'currency' => $pricingPlans->first()?->currency ?? 'EUR',
            ],
            'partner' => $station->group?->partner?->name,
            'images' => $station->images ?? [],
            'amenities' => $station->amenities ?? [],
        ];
    }

    /**
     * Transformer une session pour l'historique
     */
    protected function transformSessionForHistory(ChargingSession $session): array
    {
        return [
            'id' => $session->id,
            'transaction_id' => $session->transaction_id,
            'charging_point' => [
                'id' => $session->chargingPoint?->id,
                'name' => $session->chargingPoint?->name,
                'address' => $session->chargingPoint?->address,
                'city' => $session->chargingPoint?->city,
            ],
            'status' => $session->status,
            'start_timestamp' => $session->start_timestamp,
            'stopped_at' => $session->stopped_at,
            'duration_minutes' => $session->duration_minutes,
            'energy_consumed_kwh' => $session->energy_consumed_kwh,
            'total_cost' => $session->actual_cost,
            'currency' => $session->currency ?? 'EUR',
            'pricing_plan' => $session->pricingPlan ? [
                'name' => $session->pricingPlan->name,
                'price_per_kwh' => $session->pricingPlan->price_per_kwh,
            ] : null,
        ];
    }

    /**
     * Calculer le coût moyen
     */
    protected function calculateAverageCost($sessions): float
    {
        if ($sessions->isEmpty()) {
            return 0;
        }
        return $sessions->avg('actual_cost') ?? 0;
    }

    /**
     * Calculer l'énergie moyenne
     */
    protected function calculateAverageEnergy($sessions): float
    {
        if ($sessions->isEmpty()) {
            return 0;
        }
        return $sessions->avg('energy_consumed_kwh') ?? 0;
    }

    /**
     * Obtenir les stations favorites avec statistiques
     */
    protected function getFavoriteStations(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $favoriteIds = DB::table('user_favorites')
            ->where('user_id', $user->id)
            ->pluck('charging_point_id');

        $sessions = ChargingSession::where('user_id', $user->id)
            ->whereIn('charging_point_id', $favoriteIds)
            ->whereBetween('start_timestamp', [$startDate, $endDate])
            ->groupBy('charging_point_id')
            ->selectRaw('charging_point_id, COUNT(*) as session_count, SUM(actual_cost) as total_spent')
            ->get();

        return $sessions->map(function ($session) {
            $station = ChargingPoint::find($session->charging_point_id);
            return [
                'id' => $station?->id,
                'name' => $station?->name,
                'session_count' => $session->session_count,
                'total_spent' => $session->total_spent,
            ];
        })->toArray();
    }
}
