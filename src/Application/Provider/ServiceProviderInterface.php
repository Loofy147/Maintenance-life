<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;

interface ServiceProviderInterface
{
    public function register(ServiceContainer $container): void;
}