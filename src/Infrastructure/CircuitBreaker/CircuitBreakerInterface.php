<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\CircuitBreaker;

interface CircuitBreakerInterface
{
    /**
     * Checks if the circuit is open (service is considered unavailable).
     *
     * @param string $serviceName The name of the service to check.
     * @return bool True if the circuit is open, false otherwise.
     */
    public function isAvailable(string $serviceName): bool;

    /**
     * Records a successful call to the service.
     *
     * @param string $serviceName The name of the service.
     */
    public function recordSuccess(string $serviceName): void;

    /**
     * Records a failed call to the service.
     *
     * @param string $serviceName The name of the service.
     */
    public function recordFailure(string $serviceName): void;

    /**
     * Gets the current status of the circuit for a given service.
     *
     * @param string $serviceName The name of the service.
     * @return array<string, mixed> The status details.
     */
    public function getStatus(string $serviceName): array;
}