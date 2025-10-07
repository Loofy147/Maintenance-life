<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Strategy;

use MaintenancePro\Domain\Contracts\AnomalyDetectorInterface;
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
    private AnomalyDetectorInterface $anomalyDetector;

    /**
     * IntelligentMaintenanceStrategy constructor.
     *
     * @param ConfigurationInterface   $config                The application configuration.
     * @param MetricsInterface         $metrics               The metrics service for performance data.
     * @param HealthCheckAggregator    $healthCheckAggregator The health check aggregator for system status.
     * @param AnomalyDetectorInterface $anomalyDetector       The anomaly detector for identifying issues.
     */
    public function __construct(
        ConfigurationInterface $config,
        MetricsInterface $metrics,
        HealthCheckAggregator $healthCheckAggregator,
        AnomalyDetectorInterface $anomalyDetector
    ) {
        $this->config = $config;
        $this->metrics = $metrics;
        $this->healthCheckAggregator = $healthCheckAggregator;
        $this->anomalyDetector = $anomalyDetector;
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

        // 2. Check for performance anomalies using the anomaly detector.
        $historicalMetrics = $this->metrics->getHistorical();
        $currentMetrics = $this->metrics->getReport()['metrics'];

        // Check for anomalies in error rate
        if (isset($currentMetrics['error_rate'])) {
            $errorRateDataPoints = array_column($historicalMetrics, 'error_rate');
            if ($this->anomalyDetector->isAnomalous($errorRateDataPoints, $currentMetrics['error_rate'])) {
                return true;
            }
        }

        // Check for anomalies in response time
        if (isset($currentMetrics['avg_response_time'])) {
            $responseTimeDataPoints = array_column($historicalMetrics, 'avg_response_time');
            if ($this->anomalyDetector->isAnomalous($responseTimeDataPoints, $currentMetrics['avg_response_time'])) {
                return true;
            }
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