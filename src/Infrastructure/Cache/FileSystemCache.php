<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Cache;

class FileSystemCache implements CacheInterface
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/');

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key, $default = null)
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));

        if ($data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
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
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');

        foreach ($files as $file) {
            if (!unlink($file)) {
                return false;
            }
        }

        return true;
    }

    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
}