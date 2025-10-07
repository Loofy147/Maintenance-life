<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

/**
 * Defines the contract for all events within the application.
 *
 * Events are used to notify other parts of the system that something has happened.
 */
interface EventInterface
{
    /**
     * Gets the name of the event.
     *
     * @return string The event name.
     */
    public function getName(): string;

    /**
     * Gets the timestamp when the event was created.
     *
     * @return int The Unix timestamp.
     */
    public function getTimestamp(): int;

    /**
     * Gets the data payload of the event.
     *
     * @return array The event data.
     */
    public function getData(): array;

    /**
     * Checks if event propagation has been stopped.
     *
     * @return bool True if propagation is stopped, false otherwise.
     */
    public function isPropagationStopped(): bool;

    /**
     * Stops the propagation of the event to further listeners.
     */
    public function stopPropagation(): void;
}