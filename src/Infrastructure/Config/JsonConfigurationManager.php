<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Config;

class JsonConfigurationManager implements ConfigurationManagerInterface
{
    private array $config = [];
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        if (file_exists($filePath)) {
            $this->load($filePath);
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
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Configuration file not found: {$path}");
        }

        $content = file_get_contents($path);
        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in configuration file: ' . json_last_error_msg());
        }

        $this->config = $config;
    }

    public function save(string $path): void
    {
        $content = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException("Failed to save configuration to: {$path}");
        }
    }

    public function all(): array
    {
        return $this->config;
    }
}