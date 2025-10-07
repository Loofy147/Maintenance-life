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

/**
 * Registers the event dispatcher and its listeners.
 *
 * This provider sets up the eventing system by registering the event dispatcher,
 * notification listeners (like Slack and Webhooks), and attaching those listeners
 * to the relevant maintenance events.
 */
class EventServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the event services in the service container.
     *
     * @param ServiceContainer $container The service container.
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(WebhookNotificationListener::class, function ($c) {
            return new WebhookNotificationListener($c->get(WebhookServiceInterface::class));
        });

        $container->singleton(SlackNotificationListener::class, function ($c) {
            return new SlackNotificationListener($c->get(SlackNotificationServiceInterface::class));
        });

        $container->singleton(EventDispatcherInterface::class, function ($c) {
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