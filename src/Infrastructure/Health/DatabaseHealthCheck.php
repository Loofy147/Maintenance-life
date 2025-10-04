<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Health;

use MaintenancePro\Domain\ValueObjects\HealthStatusValue;
use PDO;

class DatabaseHealthCheck implements HealthCheckInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function check(): HealthStatusValue
    {
        try {
            $this->db->query('SELECT 1');
            return HealthStatusValue::healthy('Database connection OK');
        } catch (\Exception $e) {
            return HealthStatusValue::unhealthy('Database connection failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getName(): string
    {
        return 'database';
    }

    public function isCritical(): bool
    {
        return true;
    }
}