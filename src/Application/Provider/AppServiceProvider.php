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
use MaintenancePro\Domain\Contracts\AnomalyDetectorInterface;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Domain\Strategy\DefaultMaintenanceStrategy;
use MaintenancePro\Domain\Strategy\IntelligentMaintenanceStrategy;
use MaintenancePro\Application\Service\Contract\WebhookServiceInterface;
use MaintenancePro\Application\Service\Contract\SlackNotificationServiceInterface;
use MaintenancePro\Application\Service\SlackNotificationService;
use MaintenancePro\Application\Service\WebhookService;
use MaintenancePro\Domain\Strategy\MaintenanceStrategyInterface;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;
use MaintenancePro\Application\Service\AuthService;
use MaintenancePro\Application\Service\Contract\AuthServiceInterface;
use MaintenancePro\Domain\Repository\UserRepositoryInterface;
use MaintenancePro\Infrastructure\Repository\SqliteUserRepository;
use GuzzleHttp\Client;

class AppServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(UserRepositoryInterface::class, function($c) {
            return new SqliteUserRepository($c->get(\PDO::class));
        });

        $container->singleton(AuthServiceInterface::class, function($c) {
            return new AuthService($c->get(UserRepositoryInterface::class));
        });

        $container->singleton(Client::class, function ($c) {
            return new Client();
        });

        $container->singleton(WebhookServiceInterface::class, function($c) {
            return new WebhookService(
                $c->get(Client::class),
                $c->get(ConfigurationInterface::class),
                $c->get(LoggerInterface::class)
            );
        });

        $container->singleton(SlackNotificationServiceInterface::class, function($c) {
            return new SlackNotificationService(
                $c->get(Client::class),
                $c->get(ConfigurationInterface::class),
                $c->get(LoggerInterface::class)
            );
        });

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
                    $c->get(HealthCheckAggregator::class),
                    $c->get(AnomalyDetectorInterface::class)
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