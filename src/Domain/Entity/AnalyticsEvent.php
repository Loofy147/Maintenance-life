<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Entity;

use MaintenancePro\Domain\ValueObject\IPAddress;

class AnalyticsEvent
{
    private ?int $id = null;
    private string $eventType;
    private array $properties;
    private IPAddress $ipAddress;
    private string $userAgent;
    private \DateTimeImmutable $timestamp;

    public function __construct(
        string $eventType,
        array $properties,
        IPAddress $ipAddress,
        string $userAgent
    ) {
        $this->eventType = $eventType;
        $this->properties = $properties;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->timestamp = new \DateTimeImmutable();
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getIPAddress(): IPAddress
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }
}