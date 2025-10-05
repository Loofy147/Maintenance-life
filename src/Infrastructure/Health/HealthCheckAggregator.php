<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Health;

/**
 * Aggregates multiple health checks and runs them to determine overall system health.
 *
 * This class acts as a central registry for all health checks in the application.
 * It iterates through each registered check, collects its status, and provides a
 * consolidated report.
 */
class HealthCheckAggregator
{
    /** @var HealthCheckInterface[] */
    private array $checks = [];

    /**
     * Adds a health check to the aggregator.
     *
     * @param HealthCheckInterface $check The health check to add.
     */
    public function addCheck(HealthCheckInterface $check): void
    {
        $this->checks[] = $check;
    }

    /**
     * Runs all registered health checks and returns a consolidated report.
     *
     * The overall status will be 'unhealthy' if any critical check fails.
     *
     * @return array{
     *     status: string,
     *     timestamp: string,
     *     checks: array<string, array{
     *         healthy: bool,
     *         message: string,
     *         details: array<string, mixed>,
     *         critical: bool
     *     }>
     * } The aggregated health report.
     */
    public function runAll(): array
    {
        $results = [];
        $overallHealthy = true;

        foreach ($this->checks as $check) {
            $status = $check->check();

            $results[$check->getName()] = [
                'healthy' => $status->isHealthy(),
                'message' => $status->getMessage(),
                'details' => $status->getDetails(),
                'critical' => $check->isCritical()
            ];

            if (!$status->isHealthy() && $check->isCritical()) {
                $overallHealthy = false;
            }
        }

        return [
            'status' => $overallHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('c'),
            'checks' => $results
        ];
    }
}