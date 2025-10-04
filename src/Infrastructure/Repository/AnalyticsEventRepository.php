<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Repository;

use MaintenancePro\Domain\Entity\AnalyticsEvent;
use MaintenancePro\Domain\ValueObject\IPAddress;
use MaintenancePro\Infrastructure\Logger\LoggerInterface;

class AnalyticsEventRepository extends SQLiteRepository
{
    public function __construct(\PDO $db, LoggerInterface $logger)
    {
        parent::__construct($db, 'analytics_events', $logger);
    }

    protected function hydrate(array $data)
    {
        // In a real application, this would reconstruct the full entity
        return new AnalyticsEvent(
            $data['event_type'],
            json_decode($data['properties'], true),
            new IPAddress($data['ip_address']),
            $data['user_agent']
        );
    }

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