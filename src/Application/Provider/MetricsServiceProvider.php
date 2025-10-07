<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Infrastructure\Metrics\BufferedMetricsService;

/**
 * Registers the application's metrics service.
 *
 * This provider binds the MetricsInterface to the BufferedMetricsService,
 * which collects and buffers metrics before persisting them.
 */
class MetricsServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the metrics service in the service container.
     *
     * @param ServiceContainer $container The service container.
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(MetricsInterface::class, function ($c) {
            return new BufferedMetricsService($c->get(CacheInterface::class));
        });
    }
}