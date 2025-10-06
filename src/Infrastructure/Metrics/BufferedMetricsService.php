<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Metrics;

use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;

/**
 * A high-performance, buffered metrics service that batches writes to the cache.
 *
 * This service improves performance by reducing I/O operations. Metrics are collected in an
 * in-memory buffer and flushed to the cache periodically or when the buffer is full.
 */
class BufferedMetricsService implements MetricsInterface
{
    private CacheInterface $cache;
    private array $buffer = [];
    private int $bufferSize;

    /**
     * @param CacheInterface $cache The cache to use for storing metrics.
     * @param int $bufferSize The number of metrics to buffer before flushing.
     */
    public function __construct(CacheInterface $cache, int $bufferSize = 50)
    {
        $this->cache = $cache;
        $this->bufferSize = $bufferSize;
    }

    /**
     * Flushes any remaining metrics in the buffer when the object is destroyed.
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $count = 1, array $tags = []): void
    {
        $this->buffer[] = ['type' => 'increment', 'key' => $key, 'value' => $count, 'tags' => $tags];
        $this->flushIfNeeded();
    }

    /**
     * {@inheritdoc}
     */
    public function gauge(string $key, float $value, array $tags = []): void
    {
        $this->buffer[] = ['type' => 'gauge', 'key' => $key, 'value' => $value, 'tags' => $tags];
        $this->flushIfNeeded();
    }

    /**
     * {@inheritdoc}
     */
    public function timing(string $key, float $value, array $tags = []): void
    {
        $this->buffer[] = ['type' => 'timing', 'key' => $key, 'value' => $value, 'tags' => $tags];
        $this->flushIfNeeded();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetric(string $metric, array $filters = []): array
    {
        $key = $this->buildKey($metric, $filters);
        return [
            'metric' => $metric,
            'value' => $this->cache->get($key, 0),
            'timestamp' => time()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getReport(string $period = 'day'): array
    {
        $this->flush(); // Ensure all buffered metrics are persisted before reporting
        $report = [
            'period' => $period,
            'timestamp' => time(),
            'metrics' => []
        ];

        $report['metrics']['cache_hit_rate'] = $this->calculateCacheHitRate();
        $report['metrics']['avg_response_time'] = $this->getAverageResponseTime();
        $report['metrics']['total_requests'] = $this->cache->get('metrics:requests.total', 0);
        $report['metrics']['blocked_requests'] = $this->cache->get('metrics:requests.blocked', 0);
        $report['metrics']['security_events'] = $this->cache->get('metrics:security.events', 0);
        $report['metrics']['error_rate'] = $this->calculateErrorRate();

        $this->storeHistoricalReport($report['metrics']);

        return $report;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        if (empty($this->buffer)) {
            return;
        }

        foreach ($this->buffer as $metric) {
            $key = $this->buildKey($metric['key'], $metric['tags']);
            switch ($metric['type']) {
                case 'increment':
                    $this->cache->set($key, (int)$this->cache->get($key, 0) + $metric['value']);
                    break;
                case 'gauge':
                    $this->cache->set($key, $metric['value']);
                    break;
                case 'timing':
                    $timings = $this->cache->get($key, []);
                    $timings[] = $metric['value'];
                    $this->cache->set($key, $timings);
                    break;
            }
        }
        $this->buffer = [];
    }

    private function buildKey(string $metric, array $tags): string
    {
        $tagString = empty($tags) ? '' : ':' . http_build_query($tags);
        return "metrics:{$metric}{$tagString}";
    }

    private function flushIfNeeded(): void
    {
        if (count($this->buffer) >= $this->bufferSize) {
            $this->flush();
        }
    }

    private function calculateCacheHitRate(): float
    {
        $hits = (int)$this->cache->get($this->buildKey('cache.hits', []), 0);
        $misses = (int)$this->cache->get($this->buildKey('cache.misses', []), 0);
        $total = $hits + $misses;

        return $total > 0 ? ($hits / $total) * 100 : 0;
    }

    private function getAverageResponseTime(): float
    {
        $timings = $this->cache->get($this->buildKey('request.time', []), []);
        return empty($timings) ? 0 : array_sum($timings) / count($timings);
    }

    private function calculateErrorRate(): float
    {
        $errors = (int)$this->cache->get($this->buildKey('errors.total', []), 0);
        $total = (int)$this->cache->get($this->buildKey('requests.total', []), 1); // Avoid division by zero

        return ($errors / $total) * 100;
    }

    public function getHistorical(int $limit = 100): array
    {
        $historicalData = $this->cache->get('metrics:historical', []);
        return array_slice($historicalData, 0, $limit);
    }

    private function storeHistoricalReport(array $metrics): void
    {
        $historicalKey = 'metrics:historical';
        $maxEntries = 200; // Keep a reasonable number of historical entries

        $historicalData = $this->cache->get($historicalKey, []);
        array_unshift($historicalData, $metrics);

        if (count($historicalData) > $maxEntries) {
            $historicalData = array_slice($historicalData, 0, $maxEntries);
        }

        $this->cache->set($historicalKey, $historicalData);
    }
}