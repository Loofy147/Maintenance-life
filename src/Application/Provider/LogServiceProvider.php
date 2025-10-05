<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Infrastructure\Logger\MonologLogger;

class LogServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(LoggerInterface::class, function($c) {
            $paths = $c->get('paths');
            return new MonologLogger($paths['logs'] . '/app.log');
        });
    }
}