<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Cache;

use MaintenancePro\Application\Service\MetricsServiceInterface;

class FileSystemCache implements CacheInterface
{
    private string $cacheDir;
    private array $memoryCache = [];
    private int $hits = 0;
    private int $misses = 0;

    private ?MetricsServiceInterface $metrics;

    public function __construct(string $cacheDir, ?MetricsServiceInterface $metrics = null)
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->metrics = $metrics;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key, $default = null)
    {
        if (isset($this->memoryCache[$key])) {
            $this->hits++;
            $this->metrics?->increment('cache.hits');
            return $this->memoryCache[$key];
        }

        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            $this->misses++;
            $this->metrics?->increment('cache.misses');
            return $default;
        }

        $data = unserialize(file_get_contents($file));

        if ($data['expires_at'] < time()) {
            $this->delete($key);
            $this->misses++;
            $this->metrics?->increment('cache.misses');
            return $default;
        }

        $this->hits++;
        $this->metrics?->increment('cache.hits');
        $this->memoryCache[$key] = $data['value'];
        return $data['value'];
    }

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

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        unset($this->memoryCache[$key]);

        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

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

    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
}