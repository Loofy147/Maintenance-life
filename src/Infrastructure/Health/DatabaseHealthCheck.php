<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Health;

use MaintenancePro\Domain\ValueObjects\HealthStatusValue;
use PDO;

/**
 * Performs a health check on the application's database connection.
 *
 * It verifies that a connection to the database can be established and that a simple
 * query can be executed. This is a critical check, as the application is unlikely
 * to function correctly without a database.
 */
class DatabaseHealthCheck implements HealthCheckInterface
{
    private PDO $db;

    /**
     * @param PDO $db The PDO database connection instance to be checked.
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): HealthStatusValue
    {
        try {
            $this->db->query('SELECT 1');
            return HealthStatusValue::healthy('Database connection successful.');
        } catch (\Exception $e) {
            return HealthStatusValue::unhealthy('Could not connect to the database.', [
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    public function isCritical(): bool
    {
        return true;
    }
}