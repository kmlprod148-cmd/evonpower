<?php

namespace Database\Factories;

use App\Models\StatusLog;
use App\Models\ChargingPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusLogFactory extends Factory
{
    protected $model = StatusLog::class;

    public function definition()
    {
        $entityTypes = ['api', 'charger', 'service'];
        $entityType = $this->faker->randomElement($entityTypes);
        
        $statuses = ['online', 'offline', 'degraded', 'unknown'];
        $status = $this->faker->randomElement($statuses);
        
        $healthStatuses = ['healthy', 'warning', 'critical', 'unknown'];
        $healthStatus = $this->faker->randomElement($healthStatuses);

        return [
            'entity_type' => $entityType,
            'entity_id' => $entityType === 'charger' ? ChargingPoint::factory() : null,
            'entity_name' => $this->generateEntityName($entityType),
            'status' => $status,
            'health_status' => $healthStatus,
            'response_time_ms' => $status === 'online' ? $this->faker->numberBetween(50, 2000) : null,
            'status_code' => $status === 'online' ? 200 : $this->faker->randomElement([400, 401, 403, 404, 500, 502, 503]),
            'response_body' => $status === 'online' ? $this->generateSuccessResponse() : $this->generateErrorResponse(),
            'error_message' => $status === 'offline' ? $this->faker->randomElement([
                'Connection timeout',
                'Server error',
                'Invalid request',
                'Authentication failed',
                'Resource not found'
            ]) : null,
            'metadata' => [
                'url' => $entityType === 'api' ? $this->faker->url() : null,
                'charging_point_id' => $entityType === 'charger' ? $this->faker->numberBetween(1, 100) : null,
                'group_id' => $entityType === 'charger' ? $this->faker->numberBetween(1, 50) : null,
                'partner_id' => $entityType === 'charger' ? $this->faker->numberBetween(1, 20) : null,
                'response_size' => $this->faker->numberBetween(100, 10000),
                'user_agent' => $this->faker->userAgent(),
                'ip_address' => $this->faker->ipv4()
            ],
            'checked_at' => $this->faker->dateTimeBetween('-1 hour', 'now')
        ];
    }

    public function online()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'online',
                'health_status' => 'healthy',
                'response_time_ms' => $this->faker->numberBetween(50, 1000),
                'status_code' => 200,
                'error_message' => null
            ];
        });
    }

    public function offline()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'offline',
                'health_status' => 'critical',
                'response_time_ms' => null,
                'status_code' => $this->faker->randomElement([500, 502, 503]),
                'error_message' => $this->faker->randomElement([
                    'Connection timeout',
                    'Server error',
                    'Service unavailable'
                ])
            ];
        });
    }

    public function degraded()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'degraded',
                'health_status' => 'warning',
                'response_time_ms' => $this->faker->numberBetween(3000, 10000),
                'status_code' => $this->faker->randomElement([400, 401, 403, 404]),
                'error_message' => $this->faker->randomElement([
                    'Slow response',
                    'Partial service',
                    'Degraded performance'
                ])
            ];
        });
    }

    public function api()
    {
        return $this->state(function (array $attributes) {
            return [
                'entity_type' => 'api',
                'entity_id' => null,
                'entity_name' => $this->faker->randomElement(['Evon API', 'Steve API', 'External API'])
            ];
        });
    }

    public function charger()
    {
        return $this->state(function (array $attributes) {
            return [
                'entity_type' => 'charger',
                'entity_id' => ChargingPoint::factory(),
                'entity_name' => 'BORNE_' . $this->faker->numberBetween(1, 1000)
            ];
        });
    }

    public function service()
    {
        return $this->state(function (array $attributes) {
            return [
                'entity_type' => 'service',
                'entity_id' => null,
                'entity_name' => $this->faker->randomElement([
                    'MonitorApiStatusJob',
                    'CleanupService',
                    'NotificationService'
                ])
            ];
        });
    }

    public function recent()
    {
        return $this->state(function (array $attributes) {
            return [
                'checked_at' => $this->faker->dateTimeBetween('-30 minutes', 'now')
            ];
        });
    }

    public function old()
    {
        return $this->state(function (array $attributes) {
            return [
                'checked_at' => $this->faker->dateTimeBetween('-2 months', '-1 month')
            ];
        });
    }

    public function fast()
    {
        return $this->state(function (array $attributes) {
            return [
                'response_time_ms' => $this->faker->numberBetween(50, 500)
            ];
        });
    }

    public function slow()
    {
        return $this->state(function (array $attributes) {
            return [
                'response_time_ms' => $this->faker->numberBetween(5000, 30000)
            ];
        });
    }

    protected function generateEntityName(string $entityType): string
    {
        return match($entityType) {
            'api' => $this->faker->randomElement(['Evon API', 'Steve API', 'External API']),
            'charger' => 'BORNE_' . $this->faker->numberBetween(1, 1000),
            'service' => $this->faker->randomElement([
                'MonitorApiStatusJob',
                'CleanupService',
                'NotificationService'
            ]),
            default => $this->faker->word()
        };
    }

    protected function generateSuccessResponse(): string
    {
        $responses = [
            '{"status": "ok", "message": "Service healthy"}',
            '{"success": true, "data": {"uptime": 99.9}}',
            '{"health": "good", "response_time": 150}',
            '{"status": "online", "services": ["api", "database"]}',
            '{"result": "success", "timestamp": "' . now()->toISOString() . '"}'
        ];

        return $this->faker->randomElement($responses);
    }

    protected function generateErrorResponse(): string
    {
        $responses = [
            '{"error": "Service unavailable", "code": 503}',
            '{"status": "error", "message": "Connection failed"}',
            '{"error": "Timeout", "retry_after": 30}',
            '{"status": "offline", "reason": "Maintenance"}',
            '{"error": "Internal server error", "code": 500}'
        ];

        return $this->faker->randomElement($responses);
    }
}
