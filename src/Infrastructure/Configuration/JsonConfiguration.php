<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Configuration;

use MaintenancePro\Domain\Contracts\ConfigurationInterface;

/**
 * A file-based configuration manager that uses a JSON file for storage.
 *
 * This class handles loading, getting, setting, and saving configuration values.
 * It supports dot notation for accessing nested keys and can validate the
 * configuration against a predefined schema.
 */
class JsonConfiguration implements ConfigurationInterface
{
    /** @var array<string, mixed> The configuration data. */
    private array $config = [];

    /** @var string The path to the JSON configuration file. */
    private string $filePath;

    /** @var array<string, mixed> The schema to validate against. */
    private array $schema;

    /** @var bool A flag to track if the configuration has been modified. */
    private bool $dirty = false;

    /**
     * @param string $filePath The path to the configuration file.
     * @param array<string, mixed> $schema A schema for validating the configuration.
     */
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

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $config[$k] = $value;
            } else {
                if (!isset($config[$k]) || !is_array($config[$k])) {
                    $config[$k] = [];
                }
                $config = &$config[$k];
            }
        }

        $this->dirty = true;
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
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Loads configuration from a file.
     *
     * @param string $path The path to the file to load.
     * @throws \RuntimeException If the file is not found or contains invalid JSON.
     */
    public function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: {$path}");
        }

        $content = file_get_contents($path);
        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in config file: ' . json_last_error_msg());
        }

        $this->config = $config;
        $this->dirty = false;
    }

    /**
     * Saves the configuration to the file if it has been modified.
     *
     * @throws \RuntimeException If validation fails or the file cannot be written.
     */
    public function save(): void
    {
        if (!$this->dirty) {
            return;
        }

        if (!$this->validate()) {
            // This should ideally provide more context about what failed.
            throw new \RuntimeException('Configuration validation failed. Cannot save.');
        }

        $content = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (file_put_contents($this->filePath, $content, LOCK_EX) === false) {
            throw new \RuntimeException("Failed to save config to: {$this->filePath}");
        }

        $this->dirty = false;
    }

    /**
     * Validates the current configuration against the schema.
     *
     * @return bool True if the configuration is valid, false otherwise.
     */
    public function validate(): bool
    {
        if (empty($this->schema)) {
            return true;
        }

        return $this->validateRecursive($this->config, $this->schema);
    }

    /**
     * Merges an array of configuration values into the current configuration.
     *
     * @param array<string, mixed> $config The configuration to merge.
     */
    public function merge(array $config): void
    {
        $this->config = array_replace_recursive($this->config, $config);
        $this->dirty = true;
    }

    /**
     * Recursively validates a data array against a schema.
     *
     * @param array<string, mixed> $data The data to validate.
     * @param array<string, mixed> $schema The schema to validate against.
     * @return bool True if valid, false otherwise.
     */
    private function validateRecursive(array $data, array $schema): bool
    {
        foreach ($schema as $key => $rules) {
            if (str_contains($key, '.')) {
                // Handle nested keys in schema
                $keys = explode('.', $key);
                $value = $data;
                $exists = true;
                foreach ($keys as $k) {
                    if (!isset($value[$k])) {
                        $exists = false;
                        break;
                    }
                    $value = $value[$k];
                }

                if (isset($rules['required']) && $rules['required'] && !$exists) {
                    return false;
                }

                if ($exists && isset($rules['type']) && gettype($value) !== $rules['type']) {
                    return false;
                }
            } else {
                if (isset($rules['required']) && $rules['required'] && !isset($data[$key])) {
                    return false;
                }

                if (isset($data[$key]) && isset($rules['type'])) {
                    if (gettype($data[$key]) !== $rules['type']) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Initializes the configuration with a set of default values and saves it.
     * This is called when the specified config file does not exist.
     */
    private function initializeDefaults(): void
    {
        // This method is now primarily handled by the Kernel's default config creation.
        // It can be kept for standalone use or simplified.
        $this->config = [];
        $this->dirty = true;
    }
}