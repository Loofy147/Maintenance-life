<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\CircuitBreaker;

use MaintenancePro\Infrastructure\Cache\CacheInterface;

class CacheableCircuitBreaker implements CircuitBreakerInterface
{
    private const STATE_CLOSED = 'CLOSED';
    private const STATE_OPEN = 'OPEN';
    private const STATE_HALF_OPEN = 'HALF_OPEN';

    private CacheInterface $cache;
    private int $failureThreshold;
    private int $openTimeout; // in seconds
    private int $halfOpenTimeout; // in seconds

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
        $status = $this->getStatus($serviceName);

        if ($status['state'] === self::STATE_OPEN) {
            return false;
        }

        return true;
    }

    public function recordSuccess(string $serviceName): void
    {
        $this->cache->delete($this->getCacheKey($serviceName, 'failures'));
        $this->cache->delete($this->getCacheKey($serviceName, 'last_failure'));
    }

    public function recordFailure(string $serviceName): void
    {
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

    private function getCacheKey(string $serviceName, string $metric): string
    {
        return "circuit_breaker.{$serviceName}.{$metric}";
    }
}