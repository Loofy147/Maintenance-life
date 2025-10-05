<?php
declare(strict_types=1);

namespace Tests\Integration\Application\Listener;

use MaintenancePro\Application\Listener\SlackNotificationListener;
use MaintenancePro\Application\Service\Contract\SlackNotificationServiceInterface;
use MaintenancePro\Domain\Event\MaintenanceDisabledEvent;
use MaintenancePro\Domain\Event\MaintenanceEnabledEvent;
use MaintenancePro\Domain\Entity\MaintenanceSession;
use MaintenancePro\Domain\ValueObjects\TimePeriod;
use PHPUnit\Framework\TestCase;

class SlackNotificationListenerTest extends TestCase
{
    public function testOnMaintenanceEnabledSendsSlackNotification(): void
    {
        $slackService = $this->createMock(SlackNotificationServiceInterface::class);
        $slackService->expects($this->once())
            ->method('send')
            ->with(
                $this->isType('string'),
                $this->isType('array')
            );

        $listener = new SlackNotificationListener($slackService);

        $session = new MaintenanceSession(
            new TimePeriod(new \DateTimeImmutable(), new \DateTimeImmutable()),
            'Test reason'
        );
        $event = new MaintenanceEnabledEvent($session);

        $listener->onMaintenanceEnabled($event);
    }

    public function testOnMaintenanceDisabledSendsSlackNotification(): void
    {
        $slackService = $this->createMock(SlackNotificationServiceInterface::class);
        $slackService->expects($this->once())
            ->method('send')
            ->with(
                $this->isType('string'),
                $this->isType('array')
            );

        $listener = new SlackNotificationListener($slackService);

        $session = new MaintenanceSession(
            new TimePeriod(new \DateTimeImmutable(), new \DateTimeImmutable()),
            'Test reason'
        );
        $event = new MaintenanceDisabledEvent($session);

        $listener->onMaintenanceDisabled($event);
    }
}