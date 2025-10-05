<?php
declare(strict_types=1);

namespace Tests\Integration\Application\Listener;

use MaintenancePro\Application\Listener\WebhookNotificationListener;
use MaintenancePro\Application\Service\Contract\WebhookServiceInterface;
use MaintenancePro\Domain\Event\MaintenanceDisabledEvent;
use MaintenancePro\Domain\Event\MaintenanceEnabledEvent;
use MaintenancePro\Domain\Entity\MaintenanceSession;
use MaintenancePro\Domain\ValueObjects\TimePeriod;
use PHPUnit\Framework\TestCase;

class WebhookNotificationListenerTest extends TestCase
{
    public function testOnMaintenanceEnabledSendsWebhook(): void
    {
        $webhookService = $this->createMock(WebhookServiceInterface::class);
        $webhookService->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo('maintenance.enabled'),
                $this->isType('array')
            );

        $listener = new WebhookNotificationListener($webhookService);

        $session = new MaintenanceSession(
            new TimePeriod(new \DateTimeImmutable(), new \DateTimeImmutable()),
            'Test reason'
        );
        $event = new MaintenanceEnabledEvent($session);

        $listener->onMaintenanceEnabled($event);
    }

    public function testOnMaintenanceDisabledSendsWebhook(): void
    {
        $webhookService = $this->createMock(WebhookServiceInterface::class);
        $webhookService->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo('maintenance.disabled'),
                $this->isType('array')
            );

        $listener = new WebhookNotificationListener($webhookService);

        $session = new MaintenanceSession(
            new TimePeriod(new \DateTimeImmutable(), new \DateTimeImmutable()),
            'Test reason'
        );
        $event = new MaintenanceDisabledEvent($session);

        $listener->onMaintenanceDisabled($event);
    }
}