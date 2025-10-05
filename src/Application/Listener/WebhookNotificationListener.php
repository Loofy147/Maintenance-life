<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Listener;

use MaintenancePro\Application\Service\Contract\WebhookServiceInterface;
use MaintenancePro\Domain\Event\MaintenanceDisabledEvent;
use MaintenancePro\Domain\Event\MaintenanceEnabledEvent;

class WebhookNotificationListener
{
    private WebhookServiceInterface $webhookService;

    public function __construct(WebhookServiceInterface $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function onMaintenanceEnabled(MaintenanceEnabledEvent $event): void
    {
        $this->webhookService->send('maintenance.enabled', $event->getData());
    }

    public function onMaintenanceDisabled(MaintenanceDisabledEvent $event): void
    {
        $this->webhookService->send('maintenance.disabled', $event->getData());
    }
}