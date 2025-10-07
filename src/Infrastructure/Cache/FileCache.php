<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Cache;

use MaintenancePro\Domain\Contracts\CacheInterface;

/**
 * A persistent cache implementation that stores data in the filesystem.
 *
 * This class also maintains a request-scoped in-memory cache to avoid
 * redundant file reads within the same request lifecycle.
 */
class FileCache implements CacheInterface
{
    private string $cacheDir;
    private array $memoryCache = [];
    private int $hits = 0;
    private int $misses = 0;

    /**
     * FileCache constructor.
     *
     * @param string $cacheDir The directory where cache files will be stored.
     */
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/');

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null)
    {
        if (isset($this->memoryCache[$key])) {
            $this->hits++;
            return $this->memoryCache[$key];
        }

        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            $this->misses++;
            return $default;
        }

        $data = unserialize(file_get_contents($file));

        if ($data['expires_at'] < time()) {
            $this->delete($key);
            $this->misses++;
            return $default;
        }

        $this->hits++;
        $this->memoryCache[$key] = $data['value'];
        return $data['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $this->memoryCache[$key] = $value;

        $file = $this->getCacheFile($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];

        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        unset($this->memoryCache[$key]);

        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->memoryCache = [];
        $this->hits = 0;
        $this->misses = 0;
        $files = glob($this->cacheDir . '/*.cache');

        foreach ($files as $file) {
            if (!unlink($file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        $total = $this->hits + $this->misses;
        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'total' => $total,
            'hit_rate' => $total > 0 ? $this->hits / $total : 0,
            'memory_size' => count($this->memoryCache),
        ];
    }

    /**
     * Generates the full path for a cache file based on its key.
     *
     * @param string $key The cache key.
     * @return string The absolute path to the cache file.
     */
    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
}