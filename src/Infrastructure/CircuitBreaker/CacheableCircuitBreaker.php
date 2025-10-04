<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\CircuitBreaker;

use MaintenancePro\Infrastructure\Cache\CacheInterface;

/**
 * A cache-based implementation of the CircuitBreakerInterface.
 *
 * This class uses a cache to store the state of the circuit, including the number of failures
 * and the timestamp of the last failure. This allows the circuit breaker to be stateful across
 * requests without requiring a separate storage mechanism.
 */
class CacheableCircuitBreaker implements CircuitBreakerInterface
{
    private const STATE_CLOSED = 'CLOSED';
    private const STATE_OPEN = 'OPEN';
    private const STATE_HALF_OPEN = 'HALF_OPEN';

    private CacheInterface $cache;
    private int $failureThreshold;
    private int $openTimeout; // in seconds
    private int $halfOpenTimeout; // in seconds

    /**
     * @param CacheInterface $cache The cache to use for storing the circuit breaker's state.
     * @param int $failureThreshold The number of failures required to open the circuit.
     * @param int $openTimeout The number of seconds the circuit should remain open before transitioning to half-open.
     * @param int $halfOpenTimeout The number of seconds to wait in the half-open state before re-closing the circuit.
     */
    public function __construct(
        CacheInterface $cache,
        int $failureThreshold = 5,
        int $openTimeout = 60,
        int $halfOpenTimeout = 10
    ) {
        $this->cache = $cache;
        $this->failureThreshold = $failureThreshold;
        $this->openTimeout = $openTimeout;
        $this->halfOpenTimeout = $halfOpenTimeout;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(string $serviceName): bool
    {
        $status = $this->getStatus($serviceName);

        if ($status['state'] === self::STATE_OPEN) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function recordSuccess(string $serviceName): void
    {
        $this->cache->delete($this->getCacheKey($serviceName, 'failures'));
        $this->cache->delete($this->getCacheKey($serviceName, 'last_failure'));
    }

    /**
     * {@inheritdoc}
     */
    public function recordFailure(string $serviceName): void
    {
        $failuresKey = $this->getCacheKey($serviceName, 'failures');
        $lastFailureKey = $this->getCacheKey($serviceName, 'last_failure');

        $failures = (int) $this->cache->get($failuresKey, 0);
        $failures++;

        $this->cache->set($failuresKey, $failures, $this->openTimeout * 2);
        $this->cache->set($lastFailureKey, time(), $this->openTimeout * 2);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(string $serviceName): array
    {
        $failures = (int) $this->cache->get($this->getCacheKey($serviceName, 'failures'), 0);
        $lastFailure = (int) $this->cache->get($this->getCacheKey($serviceName, 'last_failure'), 0);
        $state = self::STATE_CLOSED;

        if ($failures >= $this->failureThreshold) {
            if ((time() - $lastFailure) > $this->openTimeout) {
                $state = self::STATE_HALF_OPEN;
            } else {
                $state = self::STATE_OPEN;
            }
        }

        return [
            'service' => $serviceName,
            'state' => $state,
            'failures' => $failures,
            'last_failure_timestamp' => $lastFailure,
        ];
    }

    private function getCacheKey(string $serviceName, string $metric): string
    {
        return "circuit_breaker.{$serviceName}.{$metric}";
    }
}