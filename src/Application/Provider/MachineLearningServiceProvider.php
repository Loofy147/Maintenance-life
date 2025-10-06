<?php

declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Domain\Contracts\AnomalyDetectorInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Infrastructure\MachineLearning\MovingAverageAnomalyDetector;
use MaintenancePro\Application\ServiceContainer;

class MachineLearningServiceProvider implements ServiceProviderInterface
{
    /**
     * @param ServiceContainer $container
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(AnomalyDetectorInterface::class, function (ServiceContainer $c) {
            return new MovingAverageAnomalyDetector(
                $c->get(ConfigurationInterface::class)
            );
        });
    }
}