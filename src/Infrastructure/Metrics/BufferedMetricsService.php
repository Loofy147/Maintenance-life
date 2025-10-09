<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Metrics;

use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;

class BufferedMetricsService implements MetricsInterface
{
    private CacheInterface $cache;
    private array $buffer = [];
    private int $bufferSize;

    public function __construct(CacheInterface $cache, int $bufferSize = 50)
    {
        $this->cache = $cache;
        $this->bufferSize = $bufferSize;
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function increment(string $key, int $count = 1, array $tags = []): void
    {
        $this->buffer[] = ['type' => 'increment', 'key' => $key, 'value' => $count, 'tags' => $tags];
        $this->flushIfNeeded();
    }

    public function timing(string $key, float $milliseconds, array $tags = []): void
    {
        $this->buffer[] = ['type' => 'timing', 'key' => $key, 'value' => $milliseconds, 'tags' => $tags];
        $this->flushIfNeeded();
    }

    public function getSummary(string $range): array
    {
        $this->flush();
        // For simplicity, we'll return a subset of the full report for the summary.
        $report = $this->generateReport();
        return [
            'range' => $range, // In a real app, you'd filter by range
            'avg_response_time' => $report['metrics']['avg_response_time'],
            'total_requests' => $report['metrics']['total_requests'],
            'error_rate' => $report['metrics']['error_rate'],
        ];
    }

    public function generateReport(): array
    {
        $this->flush(); // Ensure all buffered metrics are persisted before reporting
        $report = [
            'timestamp' => time(),
            'metrics' => []
        ];

        $report['metrics']['cache_hit_rate'] = $this->calculateCacheHitRate();
        $report['metrics']['avg_response_time'] = $this->getAverageResponseTime();
        $report['metrics']['total_requests'] = (int) $this->cache->get('metrics:requests.total', 0);
        $report['metrics']['blocked_requests'] = (int) $this->cache->get('metrics:requests.blocked', 0);
        $report['metrics']['security_events'] = (int) $this->cache->get('metrics:security.events', 0);
        $report['metrics']['error_rate'] = $this->calculateErrorRate();

        return $report;
    }

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

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    private function getAverageResponseTime(): float
    {
        $timings = $this->cache->get($this->buildKey('request.time', []), []);
        if (empty($timings)) {
            return 0;
        }
        return round(array_sum($timings) / count($timings), 2);
    }

    private function calculateErrorRate(): float
    {
        $errors = (int)$this->cache->get($this->buildKey('errors.total', []), 0);
        $total = (int)$this->cache->get($this->buildKey('requests.total', []), 1); // Avoid division by zero

        return round(($errors / $total) * 100, 2);
    }

    public function getHistorical(int $limit = 100): array
    {
        $historicalData = $this->cache->get('metrics:historical', []);
        return array_slice($historicalData, 0, $limit);
    }
}