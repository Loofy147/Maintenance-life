<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Listener;

use MaintenancePro\Application\Service\Contract\SlackNotificationServiceInterface;
use MaintenancePro\Domain\Event\MaintenanceDisabledEvent;
use MaintenancePro\Domain\Event\MaintenanceEnabledEvent;

/**
 * Listens for maintenance events and sends notifications to Slack.
 */
class SlackNotificationListener
{
    private SlackNotificationServiceInterface $slackService;

    /**
     * SlackNotificationListener constructor.
     *
     * @param SlackNotificationServiceInterface $slackService The service used to send Slack messages.
     */
    public function __construct(SlackNotificationServiceInterface $slackService)
    {
        $this->slackService = $slackService;
    }

    /**
     * Handles the event when maintenance mode is enabled.
     *
     * @param MaintenanceEnabledEvent $event The event object.
     */
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

    /**
     * Handles the event when maintenance mode is disabled.
     *
     * @param MaintenanceDisabledEvent $event The event object.
     */
    public function onMaintenanceDisabled(MaintenanceDisabledEvent $event): void
    {
        $message = "Maintenance mode has been disabled.";
        $attachment = [
            'color' => 'good',
        ];

        $this->slackService->send($message, $attachment);
    }
}