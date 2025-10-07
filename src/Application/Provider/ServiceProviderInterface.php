<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;

/**
 * Defines the contract for a service provider.
 *
 * Service providers are used to register services into the service container.
 */
interface ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * @param ServiceContainer $container The service container.
     */
    public function register(ServiceContainer $container): void;
}