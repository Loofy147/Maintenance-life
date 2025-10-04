<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Event;

use MaintenancePro\Domain\Event\EventInterface;

interface EventDispatcherInterface
{
    public function dispatch(EventInterface $event): void;
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;
    public function removeListener(string $eventName, callable $listener): void;
}