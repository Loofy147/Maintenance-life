<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Config;

interface ConfigurationManagerInterface
{
    public function get(string $key, $default = null);
    public function set(string $key, $value): void;
    public function has(string $key): bool;
    public function load(string $path): void;
    public function save(string $path): void;
    public function all(): array;
}