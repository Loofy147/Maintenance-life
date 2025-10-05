<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Infrastructure\CircuitBreaker\CacheableCircuitBreaker;
use MaintenancePro\Infrastructure\CircuitBreaker\CircuitBreakerInterface;

class CircuitBreakerServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(CircuitBreakerInterface::class, function ($c) {
            return new CacheableCircuitBreaker($c->get(CacheInterface::class));
        });
    }
}