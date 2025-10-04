<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Health;

class HealthCheckAggregator
{
    private array $checks = [];

    public function addCheck(HealthCheckInterface $check): void
    {
        $this->checks[] = $check;
    }

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