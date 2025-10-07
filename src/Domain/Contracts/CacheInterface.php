<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Contracts;

/**
 * Interface for a cache system, defining the standard operations for cache interaction.
 */
interface CacheInterface
{
    /**
     * Retrieves an item from the cache by key.
     *
     * @param string $key     The key of the item to retrieve.
     * @param mixed  $default Default value to return if the key does not exist.
     * @return mixed The value of the item from the cache, or $default if not found.
     */
    public function get(string $key, $default = null);

    /**
     * Stores an item in the cache.
     *
     * @param string $key   The key of the item to store.
     * @param mixed  $value The value of the item to be stored.
     * @param int    $ttl   Time to live in seconds.
     * @return bool True on success, false on failure.
     */
    public function set(string $key, $value, int $ttl = 3600): bool;

    /**
     * Checks if an item exists in the cache.
     *
     * @param string $key The key of the item to check.
     * @return bool True if the item exists, false otherwise.
     */
    public function has(string $key): bool;

    /**
     * Deletes an item from the cache.
     *
     * @param string $key The key of the item to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(string $key): bool;

    /**
     * Clears the entire cache.
     *
     * @return bool True on success, false on failure.
     */
    public function clear(): bool;

    /**
     * Retrieves cache statistics.
     *
     * @return array An array of cache statistics.
     */
    public function getStats(): array;
}