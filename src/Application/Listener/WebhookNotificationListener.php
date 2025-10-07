<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Listener;

use MaintenancePro\Application\Service\Contract\WebhookServiceInterface;
use MaintenancePro\Domain\Event\MaintenanceDisabledEvent;
use MaintenancePro\Domain\Event\MaintenanceEnabledEvent;

/**
 * Listens for maintenance events and sends notifications to configured webhooks.
 */
class WebhookNotificationListener
{
    private WebhookServiceInterface $webhookService;

    /**
     * WebhookNotificationListener constructor.
     *
     * @param WebhookServiceInterface $webhookService The service used to send webhook notifications.
     */
    public function __construct(WebhookServiceInterface $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handles the event when maintenance mode is enabled.
     *
     * @param MaintenanceEnabledEvent $event The event object.
     */
    public function onMaintenanceEnabled(MaintenanceEnabledEvent $event): void
    {
        $this->webhookService->send('maintenance.enabled', $event->getData());
    }

    /**
     * Handles the event when maintenance mode is disabled.
     *
     * @param MaintenanceDisabledEvent $event The event object.
     */
    public function onMaintenanceDisabled(MaintenanceDisabledEvent $event): void
    {
        $this->webhookService->send('maintenance.disabled', $event->getData());
    }
}