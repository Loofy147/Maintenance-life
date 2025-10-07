<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Event;

use MaintenancePro\Domain\Event\EventInterface;

/**
 * Defines the contract for an event dispatcher.
 *
 * An event dispatcher is responsible for managing listeners and dispatching events.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatches an event to all registered listeners.
     *
     * @param EventInterface $event The event to dispatch.
     */
    public function dispatch(EventInterface $event): void;

    /**
     * Adds an event listener that listens on a specific event.
     *
     * @param string   $eventName The name of the event to listen for.
     * @param callable $listener  The listener callback.
     * @param int      $priority  The higher the priority, the earlier the listener is executed.
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;

    /**
     * Removes an event listener from a specific event.
     *
     * @param string   $eventName The name of the event.
     * @param callable $listener  The listener to remove.
     */
    public function removeListener(string $eventName, callable $listener): void;
}