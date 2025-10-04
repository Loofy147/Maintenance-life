<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\CircuitBreaker;

/**
 * Defines the contract for a circuit breaker, which helps to prevent repeated calls to a failing service.
 */
interface CircuitBreakerInterface
{
    /**
     * Checks if the service is available to be called.
     *
     * @param string $serviceName The name of the service to check.
     * @return bool True if the service is available, false otherwise.
     */
    public function isAvailable(string $serviceName): bool;

    /**
     * Records a successful call to the service, resetting any failures.
     *
     * @param string $serviceName The name of the service.
     */
    public function recordSuccess(string $serviceName): void;

    /**
     * Records a failed call to the service, which may trip the circuit.
     *
     * @param string $serviceName The name of the service.
     */
    public function recordFailure(string $serviceName): void;

    /**
     * Gets the current status of the circuit for a given service (e.g., 'CLOSED', 'OPEN', 'HALF_OPEN').
     *
     * @param string $serviceName The name of the service.
     * @return array<string, mixed> The status details, including state and failure count.
     */
    public function getStatus(string $serviceName): array;
}