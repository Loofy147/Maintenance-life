<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Health;

use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\ValueObjects\HealthStatusValue;

/**
 * Performs a health check on the application's cache system.
 *
 * It verifies that the cache is reachable and that basic operations (set, get, delete)
 * are working correctly. This check is typically not critical, as the application
 * might be able to function without a cache, albeit with degraded performance.
 */
class CacheHealthCheck implements HealthCheckInterface
{
    private CacheInterface $cache;

    /**
     * @param CacheInterface $cache The cache instance to be checked.
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): HealthStatusValue
    {
        try {
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test';

            $this->cache->set($testKey, $testValue, 60);
            $retrieved = $this->cache->get($testKey);
            $this->cache->delete($testKey);

            if ($retrieved === $testValue) {
                return HealthStatusValue::healthy('Cache read/write test successful.');
            }

            return HealthStatusValue::unhealthy('Cache read/write test failed; value mismatch.');
        } catch (\Exception $e) {
            return HealthStatusValue::unhealthy('An exception occurred while accessing the cache.', [
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'cache';
    }

    /**
     * {@inheritdoc}
     */
    public function isCritical(): bool
    {
        return false;
    }
}