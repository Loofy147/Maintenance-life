<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Listener;

use MaintenancePro\Application\Service\Contract\SlackNotificationServiceInterface;
use MaintenancePro\Domain\Event\MaintenanceDisabledEvent;
use MaintenancePro\Domain\Event\MaintenanceEnabledEvent;

class SlackNotificationListener
{
    private SlackNotificationServiceInterface $slackService;

    public function __construct(SlackNotificationServiceInterface $slackService)
    {
        $this->slackService = $slackService;
    }

    public function onMaintenanceEnabled(MaintenanceEnabledEvent $event): void
    {
        $data = $event->getData();
        $reason = $data['reason'] ?? 'N/A';
        $endTime = $data['period']['end'] ?? 'N/A';

        $message = "Maintenance mode has been enabled.";
        $attachment = [
            'color' => 'warning',
            'fields' => [
                [
                    'title' => 'Reason',
                    'value' => $reason,
                    'short' => false,
                ],
                [
                    'title' => 'End Time',
                    'value' => $endTime,
                    'short' => false,
                ],
            ],
        ];

        $this->slackService->send($message, $attachment);
    }

    public function onMaintenanceDisabled(MaintenanceDisabledEvent $event): void
    {
        $message = "Maintenance mode has been disabled.";
        $attachment = [
            'color' => 'good',
        ];

        $this->slackService->send($message, $attachment);
    }
}