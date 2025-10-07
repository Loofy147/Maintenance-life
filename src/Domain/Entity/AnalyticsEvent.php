<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Entity;

use MaintenancePro\Domain\ValueObject\IPAddress;

/**
 * Represents an analytics event, capturing details about user interactions
 * and system occurrences for monitoring and analysis.
 */
class AnalyticsEvent
{
    private ?int $id = null;
    private string $eventType;
    private array $properties;
    private IPAddress $ipAddress;
    private string $userAgent;
    private \DateTimeImmutable $timestamp;

    /**
     * AnalyticsEvent constructor.
     *
     * @param string    $eventType  The type of event being tracked (e.g., 'login', 'page_view').
     * @param array     $properties Additional data associated with the event.
     * @param IPAddress $ipAddress  The IP address from which the event originated.
     * @param string    $userAgent  The user agent string of the client.
     */
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

    /**
     * Gets the type of the event.
     *
     * @return string The event type.
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    /**
     * Gets the properties associated with the event.
     *
     * @return array The event properties.
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Gets the IP address from which the event originated.
     *
     * @return IPAddress The IP address.
     */
    public function getIPAddress(): IPAddress
    {
        return $this->ipAddress;
    }

    /**
     * Gets the user agent of the client.
     *
     * @return string The user agent string.
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Gets the timestamp when the event occurred.
     *
     * @return \DateTimeImmutable The event timestamp.
     */
    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }
}