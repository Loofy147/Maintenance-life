<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Strategy;

/**
 * Defines the contract for a maintenance strategy.
 *
 * A maintenance strategy determines whether the application should enter
 * or bypass maintenance mode based on a given context.
 */
interface MaintenanceStrategyInterface
{
    /**
     * Determines whether the application should enter maintenance mode.
     *
     * @param array<string, mixed> $context The current application context (e.g., server load, error rate).
     * @return bool True if maintenance mode should be enabled, false otherwise.
     */
    public function shouldEnterMaintenance(array $context): bool;

    /**
     * Determines whether a request should bypass an active maintenance mode.
     *
     * @param array<string, mixed> $context The request context (e.g., IP address, user role).
     * @return bool True if the request should bypass maintenance, false otherwise.
     */
    public function shouldBypassMaintenance(array $context): bool;

    /**
     * Gets the recommended duration for the maintenance window in seconds.
     *
     * @return int The duration in seconds.
     */
    public function getMaintenanceDuration(): int;
}