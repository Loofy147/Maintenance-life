<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Contracts;

interface CacheInterface
{
    public function get(string $key, $default = null);
    public function set(string $key, $value, int $ttl = 3600): bool;
    public function has(string $key): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function getStats(): array;
}