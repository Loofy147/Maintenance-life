<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Strategy;

use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;

/**
 * An intelligent maintenance strategy that automatically triggers maintenance mode
 * based on the application's health and performance metrics.
 */
class IntelligentMaintenanceStrategy implements MaintenanceStrategyInterface
{
    private ConfigurationInterface $config;
    private MetricsInterface $metrics;
    private HealthCheckAggregator $healthCheckAggregator;

    /**
     * @param ConfigurationInterface $config
     * @param MetricsInterface $metrics
     * @param HealthCheckAggregator $healthCheckAggregator
     */
    public function __construct(
        ConfigurationInterface $config,
        MetricsInterface $metrics,
        HealthCheckAggregator $healthCheckAggregator
    ) {
        $this->config = $config;
        $this->metrics = $metrics;
        $this->healthCheckAggregator = $healthCheckAggregator;
    }

    /**
     * Determines if the application should enter maintenance mode based on health and metrics.
     *
     * @param array $context The request context (currently unused by this strategy).
     * @return bool True if maintenance mode should be activated, false otherwise.
     */
    public function shouldEnterMaintenance(array $context): bool
    {
        // 1. Check system health. If any critical check fails, enter maintenance.
        $healthReport = $this->healthCheckAggregator->runAll();
        if ($healthReport['status'] === 'unhealthy') {
            return true;
        }

        // 2. Check performance metrics against configured thresholds.
        $metricsReport = $this->metrics->getReport();

        // Check error rate
        $errorRateThreshold = $this->config->get('maintenance.intelligent.error_rate_threshold', 5.0); // 5%
        if (isset($metricsReport['metrics']['error_rate']) && $metricsReport['metrics']['error_rate'] > $errorRateThreshold) {
            return true;
        }

        // Check response time
        $responseTimeThreshold = $this->config->get('maintenance.intelligent.response_time_threshold', 1000); // 1000ms
        if (isset($metricsReport['metrics']['avg_response_time']) && $metricsReport['metrics']['avg_response_time'] > $responseTimeThreshold) {
            return true;
        }

        return false;
    }

    /**
     * This strategy does not define bypass rules. Bypassing is handled by the
     * MaintenanceService, which should use the DefaultMaintenanceStrategy for bypass checks.
     *
     * @param array $context The request context.
     * @return bool Always returns false.
     */
    public function shouldBypassMaintenance(array $context): bool
    {
        return false;
    }

    /**
     * Gets the recommended maintenance duration.
     *
     * @return int The duration in seconds.
     */
    public function getMaintenanceDuration(): int
    {
        // Could be made dynamic based on the severity of the issue detected.
        return 3600; // Default 1 hour
    }
}