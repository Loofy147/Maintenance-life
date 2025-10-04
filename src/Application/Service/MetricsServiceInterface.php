<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

interface MetricsServiceInterface
{
    /**
     * Increments a metric counter.
     *
     * @param string $key The metric key (e.g., 'cache.hits', 'security.threats').
     * @param int $count The value to increment by.
     * @param array<string, mixed> $tags Additional tags for filtering.
     */
    public function increment(string $key, int $count = 1, array $tags = []): void;

    /**
     * Records a timing value for a metric.
     *
     * @param string $key The metric key (e.g., 'request.time').
     * @param float $value The timing value in milliseconds.
     * @param array<string, mixed> $tags Additional tags for filtering.
     */
    public function timing(string $key, float $value, array $tags = []): void;

    /**
     * Generates a performance report from the collected metrics.
     *
     * @return array<string, mixed>
     */
    public function generateReport(): array;
}