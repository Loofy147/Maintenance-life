<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Configuration;

use MaintenancePro\Domain\Contracts\ConfigurationInterface;

class JsonConfiguration implements ConfigurationInterface
{
    private array $config = [];
    private string $filePath;
    private array $schema;
    private bool $dirty = false;

    public function __construct(string $filePath, array $schema = [])
    {
        $this->filePath = $filePath;
        $this->schema = $schema;

        if (file_exists($filePath)) {
            $this->load($filePath);
        } else {
            $this->initializeDefaults();
        }
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
        $this->dirty = true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: {$path}");
        }

        $content = file_get_contents($path);
        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON: ' . json_last_error_msg());
        }

        $this->config = $config;
        $this->dirty = false;
    }

    public function save(): void
    {
        if (!$this->dirty) {
            return;
        }

        if (!$this->validate()) {
            throw new \RuntimeException('Configuration validation failed');
        }

        $content = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (file_put_contents($this->filePath, $content, LOCK_EX) === false) {
            throw new \RuntimeException("Failed to save config to: {$this->filePath}");
        }

        $this->dirty = false;
    }

    public function validate(): bool
    {
        if (empty($this->schema)) {
            return true;
        }

        return $this->validateRecursive($this->config, $this->schema);
    }

    public function merge(array $config): void
    {
        $this->config = array_replace_recursive($this->config, $config);
        $this->dirty = true;
    }

    private function validateRecursive(array $data, array $schema): bool
    {
        foreach ($schema as $key => $rules) {
            if (isset($rules['required']) && $rules['required'] && !isset($data[$key])) {
                return false;
            }

            if (isset($data[$key]) && isset($rules['type'])) {
                if (gettype($data[$key]) !== $rules['type']) {
                    return false;
                }
            }
        }

        return true;
    }

    private function initializeDefaults(): void
    {
        $this->config = [
            'system' => [
                'version' => '6.0.0',
                'debug' => false,
                'timezone' => 'UTC'
            ],
            'maintenance' => [
                'enabled' => false,
                'strategy' => 'default',
                'title' => 'Site Under Maintenance',
                'message' => 'We\'ll be back soon!'
            ],
            'cache' => [
                'enabled' => true,
                'driver' => 'adaptive',
                'ttl' => 3600
            ],
            'security' => [
                'rate_limiting' => [
                    'enabled' => true,
                    'max_requests' => 100,
                    'window' => 3600
                ],
                'csrf_protection' => true
            ],
            'metrics' => [
                'enabled' => true,
                'buffer_size' => 100
            ]
        ];

        $this->dirty = true;
        $this->save();
    }
}