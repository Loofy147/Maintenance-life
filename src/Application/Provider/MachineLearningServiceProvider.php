<?php

declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Domain\Contracts\AnomalyDetectorInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Infrastructure\MachineLearning\MovingAverageAnomalyDetector;
use MaintenancePro\Application\ServiceContainer;

/**
 * Registers the machine learning services for the application.
 *
 * This provider is responsible for setting up services related to machine
 * learning, such as the anomaly detector.
 */
class MachineLearningServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the anomaly detector service in the service container.
     *
     * @param ServiceContainer $container The service container.
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