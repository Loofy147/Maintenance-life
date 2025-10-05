<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Strategy;

use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Domain\Strategy\IntelligentMaintenanceStrategy;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IntelligentMaintenanceStrategy::class)]
class IntelligentMaintenanceStrategyTest extends TestCase
{
    private ConfigurationInterface $config;
    private MetricsInterface $metrics;
    private HealthCheckAggregator $healthCheckAggregator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->createMock(ConfigurationInterface::class);
        $this->metrics = $this->createMock(MetricsInterface::class);
        $this->healthCheckAggregator = $this->createMock(HealthCheckAggregator::class);
    }

    #[Test]
    public function it_does_not_enter_maintenance_when_system_is_healthy(): void
    {
        $this->healthCheckAggregator->method('runAll')->willReturn(['status' => 'healthy']);
        $this->metrics->method('getReport')->willReturn([
            'metrics' => [
                'error_rate' => 1.0,
                'avg_response_time' => 200,
            ]
        ]);

        $this->config->method('get')
            ->willReturnMap([
                ['maintenance.intelligent.error_rate_threshold', 5.0, 5.0],
                ['maintenance.intelligent.response_time_threshold', 1000, 1000],
            ]);

        $strategy = new IntelligentMaintenanceStrategy($this->config, $this->metrics, $this->healthCheckAggregator);
        $this->assertFalse($strategy->shouldEnterMaintenance([]));
    }

    #[Test]
    public function it_enters_maintenance_on_critical_health_failure(): void
    {
        $this->healthCheckAggregator->method('runAll')->willReturn(['status' => 'unhealthy']);

        $strategy = new IntelligentMaintenanceStrategy($this->config, $this->metrics, $this->healthCheckAggregator);
        $this->assertTrue($strategy->shouldEnterMaintenance([]));
    }

    #[Test]
    public function it_enters_maintenance_when_error_rate_exceeds_threshold(): void
    {
        $this->healthCheckAggregator->method('runAll')->willReturn(['status' => 'healthy']);
        $this->metrics->method('getReport')->willReturn([
            'metrics' => [
                'error_rate' => 10.0, // Exceeds 5% threshold
                'avg_response_time' => 200,
            ]
        ]);

        $this->config->method('get')
            ->willReturnMap([
                ['maintenance.intelligent.error_rate_threshold', 5.0, 5.0],
                ['maintenance.intelligent.response_time_threshold', 1000, 1000],
            ]);

        $strategy = new IntelligentMaintenanceStrategy($this->config, $this->metrics, $this->healthCheckAggregator);
        $this->assertTrue($strategy->shouldEnterMaintenance([]));
    }

    #[Test]
    public function it_enters_maintenance_when_response_time_exceeds_threshold(): void
    {
        $this->healthCheckAggregator->method('runAll')->willReturn(['status' => 'healthy']);
        $this->metrics->method('getReport')->willReturn([
            'metrics' => [
                'error_rate' => 1.0,
                'avg_response_time' => 1500, // Exceeds 1000ms threshold
            ]
        ]);

        $this->config->method('get')
            ->willReturnMap([
                ['maintenance.intelligent.error_rate_threshold', 5.0, 5.0],
                ['maintenance.intelligent.response_time_threshold', 1000, 1000],
            ]);

        $strategy = new IntelligentMaintenanceStrategy($this->config, $this->metrics, $this->healthCheckAggregator);
        $this->assertTrue($strategy->shouldEnterMaintenance([]));
    }
}