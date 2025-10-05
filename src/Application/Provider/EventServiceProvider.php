<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\Event\EventDispatcher;
use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Application\Listener\SlackNotificationListener;
use MaintenancePro\Application\Listener\WebhookNotificationListener;
use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Application\Service\Contract\SlackNotificationServiceInterface;
use MaintenancePro\Application\Service\Contract\WebhookServiceInterface;
use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Event\MaintenanceDisabledEvent;
use MaintenancePro\Domain\Event\MaintenanceEnabledEvent;

class EventServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(WebhookNotificationListener::class, function ($c) {
            return new WebhookNotificationListener($c->get(WebhookServiceInterface::class));
        });

        $container->singleton(SlackNotificationListener::class, function ($c) {
            return new SlackNotificationListener($c->get(SlackNotificationServiceInterface::class));
        });

        $container->singleton(EventDispatcherInterface::class, function($c) {
            $dispatcher = new EventDispatcher($c->get(LoggerInterface::class));

            // Register listeners
            $webhookListener = $c->get(WebhookNotificationListener::class);
            $dispatcher->addListener(MaintenanceEnabledEvent::class, [$webhookListener, 'onMaintenanceEnabled']);
            $dispatcher->addListener(MaintenanceDisabledEvent::class, [$webhookListener, 'onMaintenanceDisabled']);

            $slackListener = $c->get(SlackNotificationListener::class);
            $dispatcher->addListener(MaintenanceEnabledEvent::class, [$slackListener, 'onMaintenanceEnabled']);
            $dispatcher->addListener(MaintenanceDisabledEvent::class, [$slackListener, 'onMaintenanceDisabled']);

            return $dispatcher;
        });
    }
}