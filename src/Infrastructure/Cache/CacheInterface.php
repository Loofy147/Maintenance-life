<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Cache;

interface CacheInterface
{
    public function get(string $key, $default = null);
    public function set(string $key, $value, int $ttl = 3600): bool;
    public function has(string $key): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
}