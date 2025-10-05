<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Application\Service\MaintenanceService;
use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Application\Service\Contract\AuthServiceInterface;
use MaintenancePro\Domain\Repository\UserRepositoryInterface;
use MaintenancePro\Infrastructure\CircuitBreaker\CircuitBreakerInterface;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;
use MaintenancePro\Presentation\Template\TemplateRendererInterface;
use MaintenancePro\Presentation\Web\Controller\AdminController;

class ControllerServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(AdminController::class, function($c) {
            return new AdminController(
                $c->get(TemplateRendererInterface::class),
                $c->get(MaintenanceService::class),
                $c->get(AccessControlService::class),
                $c->get(MetricsInterface::class),
                $c->get(ConfigurationInterface::class),
                $c->get(HealthCheckAggregator::class),
                $c->get(CircuitBreakerInterface::class),
                $c->get(AuthServiceInterface::class),
                $c->get(UserRepositoryInterface::class)
            );
        });
    }
}