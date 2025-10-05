<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Infrastructure\Health\CacheHealthCheck;
use MaintenancePro\Infrastructure\Health\DatabaseHealthCheck;
use MaintenancePro\Infrastructure\Health\DiskSpaceHealthCheck;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;

class HealthCheckServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(HealthCheckAggregator::class, function ($c) {
            $paths = $c->get('paths');
            $aggregator = new HealthCheckAggregator();
            $aggregator->addCheck(new DatabaseHealthCheck($c->get(\PDO::class)));
            $aggregator->addCheck(new CacheHealthCheck($c->get(CacheInterface::class)));
            $aggregator->addCheck(new DiskSpaceHealthCheck($paths['storage']));
            return $aggregator;
        });
    }
}