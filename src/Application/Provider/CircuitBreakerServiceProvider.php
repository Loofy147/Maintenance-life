<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Infrastructure\CircuitBreaker\CacheableCircuitBreaker;
use MaintenancePro\Infrastructure\CircuitBreaker\CircuitBreakerInterface;

/**
 * Registers the Circuit Breaker service.
 *
 * This provider binds the CircuitBreakerInterface to the CacheableCircuitBreaker
 * implementation, making it available throughout the application.
 */
class CircuitBreakerServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the circuit breaker service in the service container.
     *
     * @param ServiceContainer $container The service container.
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(CircuitBreakerInterface::class, function ($c) {
            return new CacheableCircuitBreaker($c->get(CacheInterface::class));
        });
    }
}