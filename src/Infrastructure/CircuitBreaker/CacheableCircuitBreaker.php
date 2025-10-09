<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\CircuitBreaker;

use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\CircuitBreakerInterface;

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
    private const CACHE_PREFIX = 'circuit_breaker.';
    private const ALL_SERVICES_KEY = self::CACHE_PREFIX . 'all_services';

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

    public function isAvailable(string $serviceName): bool
    {
        $this->trackService($serviceName);
        $status = $this->getStatus($serviceName);

        if ($status['state'] === self::STATE_OPEN) {
            return false;
        }

        return true;
    }

    public function recordSuccess(string $serviceName): void
    {
        $this->reset($serviceName);
    }

    public function recordFailure(string $serviceName): void
    {
        $this->trackService($serviceName);
        $failuresKey = $this->getCacheKey($serviceName, 'failures');
        $lastFailureKey = $this->getCacheKey($serviceName, 'last_failure');

        $failures = (int) $this->cache->get($failuresKey, 0);
        $failures++;

        $this->cache->set($failuresKey, $failures, $this->openTimeout * 2);
        $this->cache->set($lastFailureKey, time(), $this->openTimeout * 2);
    }

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

    public function getAllStatuses(): array
    {
        $services = $this->cache->get(self::ALL_SERVICES_KEY, []);
        $statuses = [];
        foreach ($services as $serviceName) {
            $statuses[] = $this->getStatus($serviceName);
        }
        return $statuses;
    }

    public function reset(string $service): void
    {
        $this->cache->delete($this->getCacheKey($service, 'failures'));
        $this->cache->delete($this->getCacheKey($service, 'last_failure'));
    }

    private function trackService(string $serviceName): void
    {
        $services = $this->cache->get(self::ALL_SERVICES_KEY, []);
        if (!in_array($serviceName, $services)) {
            $services[] = $serviceName;
            $this->cache->set(self::ALL_SERVICES_KEY, $services);
        }
    }

    private function getCacheKey(string $serviceName, string $metric): string
    {
        return self::CACHE_PREFIX . "{$serviceName}.{$metric}";
    }
}