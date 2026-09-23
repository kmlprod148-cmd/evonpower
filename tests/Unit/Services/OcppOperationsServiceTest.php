<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\OcppOperationsService;
use App\Services\SteVeHttpClientService;
use App\DTO\OCPP\ChangeAvailabilityRequestDTO;
use App\DTO\OCPP\ChangeAvailabilityResponseDTO;
use App\DTO\OCPP\AvailabilityTypeEnum;
use App\DTO\OCPP\AvailabilityStatusEnum;
use App\DTO\OCPP\RemoteStartRequestDTO;
use App\DTO\OCPP\RemoteStartResponseDTO;
use App\DTO\OCPP\ResetRequestDTO;
use App\DTO\OCPP\ResetResponseDTO;
use App\DTO\OCPP\ResetTypeEnum;
use App\Models\ChargingPoint;
use App\Models\Connector;
use Mockery;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Unit tests for OcppOperationsService
 * 
 * Tests the OCPP operations service that handles commands
 * to charging stations via SteVe API.
 */
class OcppOperationsServiceTest extends TestCase
{
    protected OcppOperationsService $service;
    protected $mockSteveClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockSteveClient = Mockery::mock(SteVeHttpClientService::class);
        $this->service = new OcppOperationsService($this->mockSteveClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // CHANGE AVAILABILITY TESTS
    // =========================================================================

    /** @test */
    public function it_can_change_connector_availability_to_operative()
    {
        // Arrange
        $request = ChangeAvailabilityRequestDTO::makeOperative('CP-001', 1);
        
        $apiResponse = [
            'success' => true,
            'data' => [
                'status' => 'Accepted',
                'connectorId' => 1,
            ],
        ];

        $this->mockSteveClient
            ->shouldReceive('changeAvailability')
            ->once()
            ->with('CP-001', 1, 'Operative')
            ->andReturn($apiResponse);

        // Act
        $result = $this->service->changeAvailability($request);

        // Assert
        $this->assertInstanceOf(ChangeAvailabilityResponseDTO::class, $result);
        $this->assertTrue($result->success);
    }

    /** @test */
    public function it_can_change_connector_availability_to_inoperative()
    {
        // Arrange
        $request = ChangeAvailabilityRequestDTO::makeInoperative('CP-001', 1);
        
        $apiResponse = [
            'success' => true,
            'data' => [
                'status' => 'Accepted',
                'connectorId' => 1,
            ],
        ];

        $this->mockSteveClient
            ->shouldReceive('changeAvailability')
            ->once()
            ->with('CP-001', 1, 'Inoperative')
            ->andReturn($apiResponse);

        // Act
        $result = $this->service->changeAvailability($request);

        // Assert
        $this->assertInstanceOf(ChangeAvailabilityResponseDTO::class, $result);
        $this->assertTrue($result->success);
    }

    /** @test */
    public function it_handles_change_availability_rejection()
    {
        // Arrange
        $request = ChangeAvailabilityRequestDTO::makeOperative('CP-001', 1);
        
        $apiResponse = [
            'success' => false,
            'data' => [
                'status' => 'Rejected',
                'connectorId' => 1,
            ],
        ];

        $this->mockSteveClient
            ->shouldReceive('changeAvailability')
            ->once()
            ->andReturn($apiResponse);

        // Act
        $result = $this->service->changeAvailability($request);

        // Assert
        $this->assertInstanceOf(ChangeAvailabilityResponseDTO::class, $result);
        $this->assertFalse($result->success);
    }

    /** @test */
    public function it_validates_invalid_change_availability_request()
    {
        // Arrange - Create invalid request with empty chargeBoxId
        $request = new ChangeAvailabilityRequestDTO([
            'chargeBoxId' => '',  // Invalid - empty
            'connectorId' => 1,
            'type' => AvailabilityTypeEnum::OPERATIVE,
        ]);

        // Act
        $result = $this->service->changeAvailability($request);

        // Assert
        $this->assertInstanceOf(ChangeAvailabilityResponseDTO::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('invalides', $result->message);
    }

    // =========================================================================
    // REMOTE START TESTS
    // =========================================================================

    /** @test */
    public function it_can_remote_start_charging_session()
    {
        // Arrange
        $request = new RemoteStartRequestDTO([
            'chargeBoxId' => 'CP-001',
            'connectorId' => 1,
            'ocppTag' => 'TAG-001',
        ]);

        $apiResponse = [
            'success' => true,
            'data' => [
                'status' => 'Accepted',
                'transactionId' => 12345,
            ],
        ];

        $this->mockSteveClient
            ->shouldReceive('remoteStartTransaction')
            ->once()
            ->with('CP-001', [
                'connector_id' => 1,
                'id_tag' => 'TAG-001',
            ])
            ->andReturn($apiResponse);

        // Act
        $result = $this->service->remoteStart($request);

        // Assert
        $this->assertInstanceOf(RemoteStartResponseDTO::class, $result);
        $this->assertTrue($result->isAccepted());
    }

    /** @test */
    public function it_handles_remote_start_rejection()
    {
        // Arrange
        $request = new RemoteStartRequestDTO([
            'chargeBoxId' => 'CP-001',
            'connectorId' => 1,
            'ocppTag' => 'INVALID-TAG',
        ]);

        $apiResponse = [
            'success' => false,
            'data' => [
                'status' => 'Rejected',
                'errorCode' => 'SecurityViolation',
            ],
        ];

        $this->mockSteveClient
            ->shouldReceive('remoteStartTransaction')
            ->once()
            ->andReturn($apiResponse);

        // Act
        $result = $this->service->remoteStart($request);

        // Assert
        $this->assertInstanceOf(RemoteStartResponseDTO::class, $result);
        $this->assertFalse($result->isAccepted());
    }

    // =========================================================================
    // RESET TESTS
    // =========================================================================

    /** @test */
    public function it_can_soft_reset_charge_point()
    {
        // Arrange
        $request = ResetRequestDTO::soft('CP-001');

        $apiResponse = [
            'success' => true,
            'data' => [
                'status' => 'Accepted',
                'type' => 'Soft',
            ],
        ];

        $this->mockSteveClient
            ->shouldReceive('reset')
            ->once()
            ->with('CP-001', 'Soft')
            ->andReturn($apiResponse);

        // Act
        $result = $this->service->resetChargePoint($request);

        // Assert
        $this->assertInstanceOf(ResetResponseDTO::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /** @test */
    public function it_can_hard_reset_charge_point()
    {
        // Arrange
        $request = ResetRequestDTO::hard('CP-001');

        $apiResponse = [
            'success' => true,
            'data' => [
                'status' => 'Accepted',
                'type' => 'Hard',
            ],
        ];

        $this->mockSteveClient
            ->shouldReceive('reset')
            ->once()
            ->with('CP-001', 'Hard')
            ->andReturn($apiResponse);

        // Act
        $result = $this->service->resetChargePoint($request);

        // Assert
        $this->assertInstanceOf(ResetResponseDTO::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    // =========================================================================
    // EXCEPTION HANDLING TESTS
    // =========================================================================

    /** @test */
    public function it_handles_api_exception_gracefully()
    {
        // Arrange
        $request = ChangeAvailabilityRequestDTO::makeOperative('CP-001', 1);

        $this->mockSteveClient
            ->shouldReceive('changeAvailability')
            ->once()
            ->andThrow(new Exception('Connection timeout'));

        // Act
        $result = $this->service->changeAvailability($request);

        // Assert
        $this->assertInstanceOf(ChangeAvailabilityResponseDTO::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('timeout', $result->message);
    }

    // =========================================================================
    // INTEGRATION HELPERS - For testing with real models
    // =========================================================================

    /**
     * Helper to test with a real ChargingPoint model
     */
    protected function createMockChargingPoint(array $attributes = []): ChargingPoint
    {
        $chargingPoint = Mockery::mock(ChargingPoint::class)->makePartial();
        
        $defaultAttributes = [
            'charge_box_id' => 'CP-TEST-001',
            'steve_charging_point_id' => 'steve-001',
            'name' => 'Test Charging Point',
            'status' => 'online',
        ];

        foreach (array_merge($defaultAttributes, $attributes) as $key => $value) {
            $chargingPoint->{$key} = $value;
        }

        return $chargingPoint;
    }

    /**
     * Helper to test with a real Connector model
     */
    protected function createMockConnector(array $attributes = []): Connector
    {
        $connector = Mockery::mock(Connector::class)->makePartial();
        
        $defaultAttributes = [
            'connector_id' => 1,
            'status' => 'Available',
            'type' => 'Type2',
            'power' => 22.0,
        ];

        foreach (array_merge($defaultAttributes, $attributes) as $key => $value) {
            $connector->{$key} = $value;
        }

        return $connector;
    }
}
