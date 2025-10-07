<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Contracts;

/**
 * Interface for managing application configuration settings.
 *
 * This interface defines the contract for loading, accessing, and modifying
 * configuration parameters within the application.
 */
interface ConfigurationInterface
{
    /**
     * Retrieves a configuration value by key.
     *
     * @param string $key The key of the configuration value.
     * @param mixed|null $default The default value to return if the key is not found.
     * @return mixed The configuration value.
     */
    public function get(string $key, $default = null);

    /**
     * Sets a configuration value by key.
     *
     * @param string $key The key of the configuration value.
     * @param mixed $value The value to set.
     */
    public function set(string $key, $value): void;

    /**
     * Checks if a configuration key exists.
     *
     * @param string $key The key to check.
     * @return bool True if the key exists, false otherwise.
     */
    public function has(string $key): bool;

    /**
     * Retrieves all configuration values.
     *
     * @return array<string, mixed> All configuration values.
     */
    public function all(): array;

    /**
     * Loads configuration from a file.
     *
     * @param string $path The path to the configuration file.
     */
    public function load(string $path): void;

    /**
     * Saves the configuration.
     */
    public function save(): void;

    /**
     * Validates the configuration against a schema.
     *
     * @return bool True if the configuration is valid, false otherwise.
     */
    public function validate(): bool;

    /**
     * Merges an array of configuration values into the existing configuration.
     *
     * @param array<string, mixed> $config The configuration to merge.
     */
    public function merge(array $config): void;
}