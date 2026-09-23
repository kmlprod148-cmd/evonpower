<?php

namespace Database\Factories;

use App\Models\ApiMonitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiMonitoringFactory extends Factory
{
    protected $model = ApiMonitoring::class;

    public function definition()
    {
        $apis = ['evon', 'steve'];
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        $endpoints = [
            '/api/users',
            '/api/charging-points',
            '/api/transactions',
            '/api/wallets',
            '/api/steve/connect',
            '/api/steve/start-charge',
            '/api/steve/stop-charge'
        ];

        $statusCodes = [200, 201, 400, 401, 403, 404, 500];
        $statusCode = $this->faker->randomElement($statusCodes);
        $success = $statusCode >= 200 && $statusCode < 300;

        return [
            'api_name' => $this->faker->randomElement($apis),
            'endpoint' => $this->faker->randomElement($endpoints),
            'method' => $this->faker->randomElement($methods),
            'status_code' => $statusCode,
            'response_body' => $success ? $this->generateSuccessResponse() : $this->generateErrorResponse(),
            'response_time_ms' => $this->faker->numberBetween(50, 5000),
            'success' => $success,
            'error_message' => $success ? null : $this->faker->randomElement([
                'Connection timeout',
                'Server error',
                'Invalid request',
                'Authentication failed',
                'Resource not found'
            ]),
            'request_headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => $this->faker->userAgent(),
                'Authorization' => 'Bearer ' . $this->faker->uuid()
            ],
            'response_headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
                'X-Response-Time' => $this->faker->numberBetween(50, 5000) . 'ms'
            ],
            'user_agent' => $this->faker->userAgent(),
            'ip_address' => $this->faker->ipv4(),
            'user_id' => User::factory(),
            'requested_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'responded_at' => $this->faker->dateTimeBetween('-1 hour', 'now')
        ];
    }

    public function successful()
    {
        return $this->state(function (array $attributes) {
            return [
                'status_code' => $this->faker->randomElement([200, 201, 202]),
                'success' => true,
                'error_message' => null,
                'response_time_ms' => $this->faker->numberBetween(50, 1000)
            ];
        });
    }

    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status_code' => $this->faker->randomElement([400, 401, 403, 404, 500, 502, 503]),
                'success' => false,
                'error_message' => $this->faker->randomElement([
                    'Bad Request',
                    'Unauthorized',
                    'Forbidden',
                    'Not Found',
                    'Internal Server Error',
                    'Bad Gateway',
                    'Service Unavailable'
                ]),
                'response_time_ms' => $this->faker->numberBetween(1000, 10000)
            ];
        });
    }

    public function evon()
    {
        return $this->state(function (array $attributes) {
            return [
                'api_name' => 'evon'
            ];
        });
    }

    public function steve()
    {
        return $this->state(function (array $attributes) {
            return [
                'api_name' => 'steve'
            ];
        });
    }

    public function recent()
    {
        return $this->state(function (array $attributes) {
            return [
                'requested_at' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
                'responded_at' => $this->faker->dateTimeBetween('-30 minutes', 'now')
            ];
        });
    }

    public function old()
    {
        return $this->state(function (array $attributes) {
            return [
                'requested_at' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
                'responded_at' => $this->faker->dateTimeBetween('-2 months', '-1 month')
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

    public function fast()
    {
        return $this->state(function (array $attributes) {
            return [
                'response_time_ms' => $this->faker->numberBetween(50, 500)
            ];
        });
    }

    protected function generateSuccessResponse()
    {
        $responses = [
            '{"success": true, "data": {}}',
            '{"status": "ok", "message": "Request processed successfully"}',
            '{"result": "success", "id": "' . $this->faker->uuid() . '"}',
            '{"success": true, "data": {"id": ' . $this->faker->numberBetween(1, 1000) . '}}',
            '{"status": "success", "response": "Operation completed"}'
        ];

        return $this->faker->randomElement($responses);
    }

    protected function generateErrorResponse()
    {
        $responses = [
            '{"error": "Bad Request", "message": "Invalid parameters"}',
            '{"success": false, "error": "Authentication failed"}',
            '{"status": "error", "message": "Resource not found"}',
            '{"error": "Internal Server Error", "code": 500}',
            '{"success": false, "message": "Service temporarily unavailable"}'
        ];

        return $this->faker->randomElement($responses);
    }
}
