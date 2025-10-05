<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Health;

use MaintenancePro\Domain\ValueObjects\HealthStatusValue;

/**
 * Performs a health check on the available disk space for a given path.
 *
 * This check is critical because a full disk can lead to application-wide failures,
 * including the inability to write logs, cache files, or session data.
 */
class DiskSpaceHealthCheck implements HealthCheckInterface
{
    private string $path;
    private float $warningThreshold;

    /**
     * @param string $path The path to check for disk space (e.g., '/var/www').
     * @param float $warningThreshold The usage percentage at which to trigger a warning (e.g., 90.0 for 90%).
     */
    public function __construct(string $path, float $warningThreshold = 90.0)
    {
        $this->path = $path;
        $this->warningThreshold = $warningThreshold;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): HealthStatusValue
    {
        if (!is_dir($this->path) || !is_readable($this->path)) {
            return HealthStatusValue::unhealthy("Path '{$this->path}' is not a readable directory.");
        }

        $free = @disk_free_space($this->path);
        $total = @disk_total_space($this->path);

        if ($total === false || $free === false) {
            return HealthStatusValue::unhealthy("Could not determine disk space for path '{$this->path}'. Check permissions.");
        }

        $used = $total - $free;
        $usedPercent = $total > 0 ? ($used / $total) * 100 : 0;

        $details = [
            'path' => $this->path,
            'total_space' => $this->formatBytes($total),
            'used_space' => $this->formatBytes($used),
            'free_space' => $this->formatBytes($free),
            'used_percent' => round($usedPercent, 2),
            'threshold_percent' => $this->warningThreshold,
        ];

        if ($usedPercent > $this->warningThreshold) {
            $message = sprintf(
                'Disk space usage is critical: %.2f%% used, exceeds threshold of %.2f%%.',
                $usedPercent,
                $this->warningThreshold
            );
            return HealthStatusValue::unhealthy($message, $details);
        }

        $message = sprintf(
            'Disk space is OK: %.2f%% used.',
            $usedPercent
        );
        return HealthStatusValue::healthy($message, $details);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'disk_space';
    }

    /**
     * {@inheritdoc}
     */
    public function isCritical(): bool
    {
        return true;
    }

    /**
     * Formats a number of bytes into a human-readable string.
     *
     * @param float $bytes The number of bytes.
     * @return string The formatted string (e.g., '1.23 GB').
     */
    private function formatBytes(float $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $factor = floor((strlen((string)$bytes) - 1) / 3);

        return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . @$units[$factor];
    }
}