<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Cache;

use MaintenancePro\Domain\Contracts\CacheInterface;

class AdaptiveCache implements CacheInterface
{
    private array $memoryCache = [];
    private CacheInterface $persistentCache;

    public function __construct(CacheInterface $persistentCache)
    {
        $this->persistentCache = $persistentCache;
    }

    public function get(string $key, $default = null)
    {
        if (array_key_exists($key, $this->memoryCache)) {
            return $this->memoryCache[$key];
        }

        $value = $this->persistentCache->get($key, $default);
        if ($value !== $default) {
            $this->memoryCache[$key] = $value;
        }

        return $value;
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $this->memoryCache[$key] = $value;
        return $this->persistentCache->set($key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->memoryCache) || $this->persistentCache->has($key);
    }

    public function delete(string $key): bool
    {
        if (array_key_exists($key, $this->memoryCache)) {
            unset($this->memoryCache[$key]);
        }
        return $this->persistentCache->delete($key);
    }

    public function clear(): bool
    {
        $this->memoryCache = [];
        return $this->persistentCache->clear();
    }

    public function getStats(): array
    {
        // For simplicity, we'll just return the stats from the persistent cache.
        // A more advanced implementation could merge stats from both caches.
        return $this->persistentCache->getStats();
    }
}