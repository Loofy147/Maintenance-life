<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Event;

use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Domain\Event\EventInterface;

/**
 * Manages and dispatches events to registered listeners.
 *
 * This class allows listeners to subscribe to specific events and ensures they are
 * called in the correct order when an event is dispatched.
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, array<int, array{callback: callable, priority: int}>>
     */
    private array $listeners = [];
    private LoggerInterface $logger;

    /**
     * EventDispatcher constructor.
     *
     * @param LoggerInterface $logger The logger for recording event activity.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * Listeners are executed in order of priority (higher first). If a listener
     * stops propagation, subsequent listeners will not be called.
     *
     * @param EventInterface $event The event to dispatch.
     */
    public function dispatch(EventInterface $event): void
    {
        $eventName = $event->getName();

        $this->logger->debug("Dispatching event: {$eventName}", $event->getData());

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        // Sort listeners by priority (higher first)
        uasort($this->listeners[$eventName], function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        foreach ($this->listeners[$eventName] as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }

            try {
                call_user_func($listener['callback'], $event);
            } catch (\Exception $e) {
                $this->logger->error("Error in event listener", [
                    'event' => $eventName,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Adds an event listener that listens on a specific event.
     *
     * @param string   $eventName The name of the event to listen for.
     * @param callable $listener  The listener callback.
     * @param int      $priority  The higher the priority, the earlier the listener is executed.
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][] = [
            'callback' => $listener,
            'priority' => $priority
        ];
    }

    /**
     * Removes an event listener from a specific event.
     *
     * @param string   $eventName The name of the event.
     * @param callable $listener  The listener to remove.
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $key => $listenerData) {
            if ($listenerData['callback'] === $listener) {
                unset($this->listeners[$eventName][$key]);
            }
        }
    }
}