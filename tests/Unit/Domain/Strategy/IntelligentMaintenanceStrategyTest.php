<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Strategy;

use MaintenancePro\Domain\Contracts\AnomalyDetectorInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\HealthCheckInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Domain\Strategy\IntelligentMaintenanceStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IntelligentMaintenanceStrategy::class)]
class IntelligentMaintenanceStrategyTest extends TestCase
{
    private ConfigurationInterface $config;
    private MetricsInterface $metrics;
    private HealthCheckInterface $healthCheckAggregator;
    private AnomalyDetectorInterface $anomalyDetector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->createMock(ConfigurationInterface::class);
        $this->metrics = $this->createMock(MetricsInterface::class);
        $this->healthCheckAggregator = $this->createMock(HealthCheckInterface::class);
        $this->anomalyDetector = $this->createMock(AnomalyDetectorInterface::class);
    }

    #[Test]
    public function it_does_not_enter_maintenance_when_system_is_healthy_and_no_anomalies_are_detected(): void
    {
        $this->healthCheckAggregator->method('run')->willReturn(['status' => 'healthy']);
        $this->metrics->method('getHistorical')->willReturn([]);
        $this->metrics->method('generateReport')->willReturn(['metrics' => ['error_rate' => 1.0, 'avg_response_time' => 200]]);
        $this->anomalyDetector->method('isAnomalous')->willReturn(false);

        $strategy = new IntelligentMaintenanceStrategy($this->config, $this->metrics, $this->healthCheckAggregator, $this->anomalyDetector);
        $this->assertFalse($strategy->shouldEnterMaintenance([]));
    }

    #[Test]
    public function it_enters_maintenance_on_critical_health_failure(): void
    {
        $this->healthCheckAggregator->method('run')->willReturn(['status' => 'unhealthy']);

        $strategy = new IntelligentMaintenanceStrategy($this->config, $this->metrics, $this->healthCheckAggregator, $this->anomalyDetector);
        $this->assertTrue($strategy->shouldEnterMaintenance([]));
    }

    #[Test]
    public function it_enters_maintenance_when_error_rate_is_anomalous(): void
    {
        $this->healthCheckAggregator->method('run')->willReturn(['status' => 'healthy']);
        $this->metrics->method('getHistorical')->willReturn([]);
        $this->metrics->method('generateReport')->willReturn(['metrics' => ['error_rate' => 10.0, 'avg_response_time' => 200]]);

        $this->anomalyDetector->expects($this->once())
            ->method('isAnomalous')
            ->willReturn(true);

        $strategy = new IntelligentMaintenanceStrategy($this->config, $this->metrics, $this->healthCheckAggregator, $this->anomalyDetector);
        $this->assertTrue($strategy->shouldEnterMaintenance([]));
    }

    #[Test]
    public function it_enters_maintenance_when_response_time_is_anomalous(): void
    {
        $this->healthCheckAggregator->method('run')->willReturn(['status' => 'healthy']);
        $this->metrics->method('getHistorical')->willReturn([]);
        $this->metrics->method('generateReport')->willReturn(['metrics' => ['error_rate' => 1.0, 'avg_response_time' => 1500.0]]);

        $this->anomalyDetector->expects($this->exactly(2))
            ->method('isAnomalous')
            ->willReturnOnConsecutiveCalls(false, true);

        $strategy = new IntelligentMaintenanceStrategy($this->config, $this->metrics, $this->healthCheckAggregator, $this->anomalyDetector);
        $this->assertTrue($strategy->shouldEnterMaintenance([]));
    }
}