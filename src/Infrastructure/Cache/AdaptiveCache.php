<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Cache;

use MaintenancePro\Domain\Contracts\CacheInterface;

/**
 * A two-layer adaptive cache that combines a fast, in-memory cache (request-scoped)
 * with a slower, persistent cache (e.g., FileCache). This provides optimal performance
 * by serving frequently accessed data from memory while falling back to a persistent
 * store when needed.
 */
class AdaptiveCache implements CacheInterface
{
    private array $memoryCache = [];
    private CacheInterface $persistentCache;

    private int $memoryHits = 0;
    private int $persistentHits = 0;
    private int $misses = 0;

    /**
     * AdaptiveCache constructor.
     *
     * @param CacheInterface $persistentCache The underlying persistent cache layer.
     */
    public function __construct(CacheInterface $persistentCache)
    {
        $this->persistentCache = $persistentCache;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null)
    {
        // First, try the fast in-memory cache.
        if (array_key_exists($key, $this->memoryCache)) {
            $this->memoryHits++;
            return $this->memoryCache[$key];
        }

        // If not in memory, try the persistent cache.
        $value = $this->persistentCache->get($key, $default);

        // If found in the persistent cache, "warm" the in-memory cache for next time.
        if ($value !== $default) {
            $this->persistentHits++;
            $this->memoryCache[$key] = $value;
        } else {
            $this->misses++;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        // Set the value in both caches to ensure consistency.
        $this->memoryCache[$key] = $value;
        return $this->persistentCache->set($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->memoryCache) || $this->persistentCache->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        // Delete from both caches.
        if (array_key_exists($key, $this->memoryCache)) {
            unset($this->memoryCache[$key]);
        }
        return $this->persistentCache->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->memoryCache = [];
        return $this->persistentCache->clear();
    }

    /**
     * {@inheritdoc}
     *
     * Returns a comprehensive report of statistics from both the in-memory
     * and persistent cache layers.
     */
    public function getStats(): array
    {
        $totalRequests = $this->memoryHits + $this->persistentHits + $this->misses;
        $persistentStats = $this->persistentCache->getStats();

        return [
            'memory_hits' => $this->memoryHits,
            'persistent_hits' => $this->persistentHits,
            'misses' => $this->misses,
            'total_requests' => $totalRequests,
            'hit_rate' => $totalRequests > 0 ? ($this->memoryHits + $this->persistentHits) / $totalRequests : 0,
            'memory_hit_rate' => $totalRequests > 0 ? $this->memoryHits / $totalRequests : 0,
            'persistent_cache_stats' => $persistentStats,
        ];
    }
}