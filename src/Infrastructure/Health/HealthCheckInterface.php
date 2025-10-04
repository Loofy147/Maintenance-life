<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Health;

use MaintenancePro\Domain\ValueObjects\HealthStatusValue;

/**
 * Defines the contract for a health check, which assesses the status of a specific component.
 */
interface HealthCheckInterface
{
    /**
     * Runs the health check and returns a status object.
     *
     * @return HealthStatusValue The result of the health check.
     */
    public function check(): HealthStatusValue;

    /**
     * Gets the unique, machine-readable name of the health check (e.g., 'database', 'cache').
     *
     * @return string The name of the health check.
     */
    public function getName(): string;

    /**
     * Indicates if a failure of this check should be considered critical to the application's overall health.
     *
     * @return bool True if the check is critical, false otherwise.
     */
    public function isCritical(): bool;
}