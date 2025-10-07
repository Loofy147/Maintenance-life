<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Repository;

use MaintenancePro\Domain\Entity\AnalyticsEvent;
use MaintenancePro\Domain\ValueObject\IPAddress;
use MaintenancePro\Infrastructure\Logger\LoggerInterface;

/**
 * Repository for storing and retrieving AnalyticsEvent entities using a SQLite database.
 */
class AnalyticsEventRepository extends SQLiteRepository
{
    /**
     * AnalyticsEventRepository constructor.
     *
     * @param \PDO            $db     The PDO database connection.
     * @param LoggerInterface $logger The logger for recording repository activity.
     */
    public function __construct(\PDO $db, LoggerInterface $logger)
    {
        parent::__construct($db, 'analytics_events', $logger);
    }

    /**
     * Creates an AnalyticsEvent entity from a database row.
     *
     * @param array<string, mixed> $data The raw data from the database.
     * @return AnalyticsEvent The hydrated entity.
     */
    protected function hydrate(array $data): AnalyticsEvent
    {
        // In a real application, this would reconstruct the full entity
        return new AnalyticsEvent(
            $data['event_type'],
            json_decode($data['properties'], true),
            new IPAddress($data['ip_address']),
            $data['user_agent']
        );
    }

    /**
     * Extracts data from an AnalyticsEvent entity to be stored in the database.
     *
     * @param object $entity The entity to extract data from.
     * @return array<string, mixed> The extracted data array.
     * @throws \InvalidArgumentException If the provided entity is not an instance of AnalyticsEvent.
     */
    protected function extract($entity): array
    {
        if (!$entity instanceof AnalyticsEvent) {
            throw new \InvalidArgumentException('Entity must be an instance of AnalyticsEvent');
        }

        return [
            'event_type' => $entity->getEventType(),
            'properties' => json_encode($entity->getProperties()),
            'ip_address' => $entity->getIPAddress()->toString(),
            'user_agent' => $entity->getUserAgent(),
            'timestamp' => $entity->getTimestamp()->format('Y-m-d H:i:s'),
        ];
    }
}