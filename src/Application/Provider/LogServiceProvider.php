<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Infrastructure\Logger\MonologLogger;

/**
 * Registers the application's logging service.
 *
 * This provider binds the LoggerInterface to the MonologLogger implementation,
 * ensuring a consistent logging mechanism is available throughout the application.
 */
class LogServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the logger service in the service container.
     *
     * @param ServiceContainer $container The service container.
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(LoggerInterface::class, function ($c) {
            $paths = $c->get('paths');
            return new MonologLogger($paths['logs'] . '/app.log');
        });
    }
}