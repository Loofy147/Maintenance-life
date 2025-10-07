<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

/**
 * Provides a base implementation for events, including a name, timestamp,
 * and data payload. It also handles propagation control.
 */
abstract class BaseEvent implements EventInterface
{
    private string $name;
    private int $timestamp;
    private array $data;
    private bool $propagationStopped = false;

    /**
     * BaseEvent constructor.
     *
     * @param string $name The name of the event.
     * @param array  $data The data payload associated with the event.
     */
    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        $this->timestamp = time();
        $this->data = $data;
    }

    /**
     * Gets the name of the event.
     *
     * @return string The event name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the timestamp when the event was created.
     *
     * @return int The Unix timestamp.
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Gets the data payload of the event.
     *
     * @return array The event data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Checks if event propagation has been stopped.
     *
     * @return bool True if propagation is stopped, false otherwise.
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further listeners.
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}