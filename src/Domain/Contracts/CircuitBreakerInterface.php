<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Contracts;

interface CircuitBreakerInterface
{
    /**
     * Checks if a service is currently available.
     *
     * @param string $serviceName
     * @return bool
     */
    public function isAvailable(string $serviceName): bool;

    /**
     * Records a successful call to a service.
     *
     * @param string $serviceName
     */
    public function recordSuccess(string $serviceName): void;

    /**
     * Records a failed call to a service.
     *
     * @param string $serviceName
     */
    public function recordFailure(string $serviceName): void;

    /**
     * Gets the status of a specific service.
     *
     * @param string $serviceName
     * @return array
     */
    public function getStatus(string $serviceName): array;

    /**
     * Gets the status of all tracked services.
     *
     * @return array
     */
    public function getAllStatuses(): array;

    /**
     * Resets the circuit breaker for a specific service.
     *
     * @param string $service
     */
    public function reset(string $service): void;
}