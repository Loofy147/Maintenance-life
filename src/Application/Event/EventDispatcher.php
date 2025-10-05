<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Event;

use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Domain\Event\EventInterface;

class EventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][] = [
            'callback' => $listener,
            'priority' => $priority
        ];
    }

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