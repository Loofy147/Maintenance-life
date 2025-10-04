<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Health;

use MaintenancePro\Domain\ValueObjects\HealthStatusValue;

class DiskSpaceHealthCheck implements HealthCheckInterface
{
    private string $path;
    private float $threshold;

    public function __construct(string $path, float $threshold = 90.0)
    {
        $this->path = $path;
        $this->threshold = $threshold;
    }

    public function check(): HealthStatusValue
    {
        $free = disk_free_space($this->path);
        $total = disk_total_space($this->path);

        if ($total === false || $free === false) {
            return HealthStatusValue::unhealthy('Could not determine disk space.');
        }

        $used = $total - $free;
        $usedPercent = ($used / $total) * 100;

        $details = [
            'path' => $this->path,
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'used_percent' => round($usedPercent, 2)
        ];

        if ($usedPercent > $this->threshold) {
            return HealthStatusValue::unhealthy('Disk space critical', $details);
        }

        return HealthStatusValue::healthy('Disk space OK', $details);
    }

    public function getName(): string
    {
        return 'disk_space';
    }

    public function isCritical(): bool
    {
        return true;
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}