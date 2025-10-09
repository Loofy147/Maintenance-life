<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Contracts;

interface MetricsInterface
{
    /**
     * Gets a summary of metrics for a given time range.
     *
     * @param string $range The time range (e.g., '1h', '24h', '7d').
     * @return array
     */
    public function getSummary(string $range): array;

    /**
     * Generates a full metrics report.
     *
     * @return array
     */
    public function generateReport(): array;

    /**
     * Increments a counter metric.
     *
     * @param string $key
     * @param int $count
     * @param array $tags
     */
    public function increment(string $key, int $count = 1, array $tags = []): void;

    /**
     * Records a timing metric.
     *
     * @param string $key
     * @param float $milliseconds
     * @param array $tags
     */
    public function timing(string $key, float $milliseconds, array $tags = []): void;

    /**
     * Flushes any buffered metrics.
     */
    public function flush(): void;

    /**
     * Gets historical metrics data.
     *
     * @param int $limit
     * @return array
     */
    public function getHistorical(int $limit = 100): array;
}