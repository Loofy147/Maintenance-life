<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Health;

use MaintenancePro\Domain\ValueObjects\HealthStatusValue;
use MaintenancePro\Infrastructure\Cache\CacheInterface;

class CacheHealthCheck implements HealthCheckInterface
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function check(): HealthStatusValue
    {
        try {
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test';

            $this->cache->set($testKey, $testValue, 60);
            $retrieved = $this->cache->get($testKey);
            $this->cache->delete($testKey);

            if ($retrieved === $testValue) {
                return HealthStatusValue::healthy('Cache working correctly');
            }

            return HealthStatusValue::unhealthy('Cache read/write failed');
        } catch (\Exception $e) {
            return HealthStatusValue::unhealthy('Cache error', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getName(): string
    {
        return 'cache';
    }

    public function isCritical(): bool
    {
        return false;
    }
}