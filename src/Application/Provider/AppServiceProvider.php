<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Application\Service\MaintenanceService;
use MaintenancePro\Application\Service\SecurityService;
use MaintenancePro\Application\Service\SecurityServiceInterface;
use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Domain\Strategy\DefaultMaintenanceStrategy;
use MaintenancePro\Domain\Strategy\IntelligentMaintenanceStrategy;
use MaintenancePro\Domain\Strategy\MaintenanceStrategyInterface;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;

class AppServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(AccessControlService::class, function($c) {
            return new AccessControlService(
                $c->get(ConfigurationInterface::class),
                $c->get(CacheInterface::class),
                $c->get(LoggerInterface::class)
            );
        });

        $container->singleton(SecurityServiceInterface::class, function($c) {
            return new SecurityService(
                $c->get(ConfigurationInterface::class),
                $c->get(CacheInterface::class),
                $c->get(LoggerInterface::class),
                $c->get(EventDispatcherInterface::class)
            );
        });

        $container->singleton(MaintenanceStrategyInterface::class, function ($c) {
            $config = $c->get(ConfigurationInterface::class);
            if ($config->get('maintenance.strategy') === 'intelligent') {
                return new IntelligentMaintenanceStrategy(
                    $config,
                    $c->get(MetricsInterface::class),
                    $c->get(HealthCheckAggregator::class)
                );
            }

            return new DefaultMaintenanceStrategy(
                $config,
                $c->get(AccessControlService::class)
            );
        });

        $container->singleton(MaintenanceService::class, function($c) {
            return new MaintenanceService(
                $c->get(ConfigurationInterface::class),
                $c->get(EventDispatcherInterface::class),
                $c->get(LoggerInterface::class),
                $c->get(MaintenanceStrategyInterface::class)
            );
        });
    }
}