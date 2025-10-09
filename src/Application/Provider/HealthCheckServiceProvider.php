<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\HealthCheckInterface;
use MaintenancePro\Infrastructure\Health\CacheHealthCheck;
use MaintenancePro\Infrastructure\Health\DatabaseHealthCheck;
use MaintenancePro\Infrastructure\Health\DiskSpaceHealthCheck;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;
use MaintenancePro\Infrastructure\FileSystem\FileSystemProvider;

/**
 * Registers the application's health check services.
 *
 * This provider sets up the HealthCheckAggregator and registers individual
 * health checks for different components of the system, such as the database,
 * cache, and disk space.
 */
class HealthCheckServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the health check services in the service container.
     *
     * @param ServiceContainer $container The service container.
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(FileSystemProvider::class, fn () => new FileSystemProvider());

        $container->singleton(HealthCheckInterface::class, function ($c) {
            $paths = $c->get('paths');
            $aggregator = new HealthCheckAggregator();
            $aggregator->addCheck(new DatabaseHealthCheck($c->get(\PDO::class)));
            $aggregator->addCheck(new CacheHealthCheck($c->get(CacheInterface::class)));
            $aggregator->addCheck(
                new DiskSpaceHealthCheck(
                    $paths['storage'],
                    $c->get(CacheInterface::class),
                    $c->get(FileSystemProvider::class)
                )
            );
            return $aggregator;
        });
    }
}