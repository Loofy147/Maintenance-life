<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Presentation\Api\Controller\ApiController;
use MaintenancePro\Domain\Contracts\MaintenanceServiceInterface;
use MaintenancePro\Domain\Contracts\HealthCheckInterface;
use MaintenancePro\Domain\Contracts\CircuitBreakerInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;

class ApiServiceProvider implements ServiceProviderInterface
{
    /**
     * @param ServiceContainer $container
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(ApiController::class, function (ServiceContainer $c) {
            return new ApiController(
                $c->get(MaintenanceServiceInterface::class),
                $c->get(HealthCheckInterface::class),
                $c->get(CircuitBreakerInterface::class),
                $c->get(MetricsInterface::class)
            );
        });
    }
}