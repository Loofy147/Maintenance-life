<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\FileSystem;

/**
 * Provides a testable wrapper around native PHP filesystem functions.
 */
class FileSystemProvider
{
    /**
     * Returns the total size of a filesystem or disk partition.
     *
     * @param string $path A path within the filesystem.
     * @return float|false The total space in bytes, or false on failure.
     */
    public function getTotalSpace(string $path)
    {
        return @disk_total_space($path);
    }

    /**
     * Returns the number of available bytes on a filesystem or disk partition.
     *
     * @param string $path A path within the filesystem.
     * @return float|false The available space in bytes, or false on failure.
     */
    public function getFreeSpace(string $path)
    {
        return @disk_free_space($path);
    }
}