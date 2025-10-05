<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Infrastructure\Metrics\BufferedMetricsService;

class MetricsServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(MetricsInterface::class, function ($c) {
            return new BufferedMetricsService($c->get(CacheInterface::class));
        });
    }
}