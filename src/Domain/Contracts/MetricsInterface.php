<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Contracts;

/**
 * Defines the contract for a metrics service.
 */
interface MetricsInterface
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
     * Records a gauge value, representing a point-in-time measurement.
     *
     * @param string $key The metric key (e.g., 'queue.size').
     * @param float $value The value of the gauge.
     * @param array<string, mixed> $tags Additional tags for filtering.
     */
    public function gauge(string $key, float $value, array $tags = []): void;

    /**
     * Records a timing value for a metric.
     *
     * @param string $key The metric key (e.g., 'request.time').
     * @param float $value The timing value in milliseconds.
     * @param array<string, mixed> $tags Additional tags for filtering.
     */
    public function timing(string $key, float $value, array $tags = []): void;

    /**
     * Retrieves a specific metric's value.
     *
     * @param string $metric The name of the metric to retrieve.
     * @param array<string, mixed> $filters Optional filters for the metric.
     * @return array<string, mixed>
     */
    public function getMetric(string $metric, array $filters = []): array;

    /**
     * Generates a performance report from the collected metrics.
     *
     * @param string $period The time period for the report (e.g., 'day', 'hour').
     * @return array<string, mixed>
     */
    public function getReport(string $period = 'day'): array;

    /**
     * Flushes the buffer to persist any pending metrics.
     */
    public function flush(): void;

    /**
     * Retrieves a series of historical metric data points.
     *
     * @param int $limit The number of data points to retrieve.
     * @return array<int, array<string, mixed>>
     */
    public function getHistorical(int $limit = 100): array;
}