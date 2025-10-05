<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\Event\EventDispatcher;
use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Application\ServiceContainer;

class EventServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(EventDispatcherInterface::class, function($c) {
            return new EventDispatcher($c->get(LoggerInterface::class));
        });
    }
}