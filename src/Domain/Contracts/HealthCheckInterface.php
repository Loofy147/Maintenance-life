<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Contracts;

interface HealthCheckInterface
{
    /**
     * Runs the health check and returns a report.
     *
     * @return array
     */
    public function run(): array;
}