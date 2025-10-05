<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\CircuitBreaker;

/**
 * Defines the contract for a circuit breaker, a design pattern used to detect failures
 * and prevent a failing service from being called repeatedly. This helps to avoid
 * cascading failures and allows services to recover.
 */
interface CircuitBreakerInterface
{
    /**
     * Checks if the service is available to be called.
     *
     * Returns true if the circuit is CLOSED or HALF_OPEN, indicating that a call
     * to the service can be attempted. Returns false if the circuit is OPEN.
     *
     * @param string $serviceName The unique identifier for the service.
     * @return bool True if the service is available, false otherwise.
     */
    public function isAvailable(string $serviceName): bool;

    /**
     * Records a successful call to the service.
     *
     * If the circuit is in the HALF_OPEN state, a successful call will transition
     * it back to the CLOSED state. If it's already CLOSED, this resets the failure count.
     *
     * @param string $serviceName The unique identifier for the service.
     */
    public function recordSuccess(string $serviceName): void;

    /**
     * Records a failed call to the service.
     *
     * Each failure increments a counter. If the failure count exceeds the configured
     * threshold, the circuit will trip and transition to the OPEN state.
     *
     * @param string $serviceName The unique identifier for the service.
     */
    public function recordFailure(string $serviceName): void;

    /**
     * Gets the current status of the circuit for a given service.
     *
     * @param string $serviceName The unique identifier for the service.
     * @return array{
     *     service: string,
     *     state: string,
     *     failures: int,
     *     last_failure_timestamp: int
     * } An associative array containing the service's current state details.
     */
    public function getStatus(string $serviceName): array;
}