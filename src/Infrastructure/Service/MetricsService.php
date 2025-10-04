<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Service;

use MaintenancePro\Application\Service\MetricsServiceInterface;
use PDO;

class MetricsService implements MetricsServiceInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->setupDatabase();
    }

    private function setupDatabase(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            metric_key VARCHAR(255) NOT NULL,
            metric_type VARCHAR(20) NOT NULL,
            value REAL NOT NULL,
            tags TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function increment(string $key, int $count = 1, array $tags = []): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO metrics (metric_key, metric_type, value, tags) VALUES (:key, :type, :value, :tags)'
        );
        $stmt->execute([
            ':key' => $key,
            ':type' => 'counter',
            ':value' => $count,
            ':tags' => json_encode($tags),
        ]);
    }

    public function timing(string $key, float $value, array $tags = []): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO metrics (metric_key, metric_type, value, tags) VALUES (:key, :type, :value, :tags)'
        );
        $stmt->execute([
            ':key' => $key,
            ':type' => 'timing',
            ':value' => $value,
            ':tags' => json_encode($tags),
        ]);
    }

    public function generateReport(): array
    {
        $report = [];
        $stmt = $this->db->query("
            SELECT
                metric_key,
                metric_type,
                COUNT(value) as count,
                SUM(value) as total,
                AVG(value) as average,
                MIN(value) as min,
                MAX(value) as max
            FROM metrics
            WHERE created_at >= datetime('now', '-24 hours')
            GROUP BY metric_key, metric_type
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $report[$row['metric_key']] = [
                'type' => $row['metric_type'],
                'count' => (int) $row['count'],
                'total' => (float) $row['total'],
                'average' => (float) $row['average'],
                'min' => (float) $row['min'],
                'max' => (float) $row['max'],
            ];
        }

        return $report;
    }
}