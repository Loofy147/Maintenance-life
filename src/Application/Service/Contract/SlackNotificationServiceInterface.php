<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service\Contract;

/**
 * Defines the contract for a service that sends notifications to Slack.
 */
interface SlackNotificationServiceInterface
{
    /**
     * Sends a message to a pre-configured Slack channel.
     *
     * @param string               $message    The main text of the message.
     * @param array<string, mixed> $attachment An optional array representing a Slack message attachment for rich formatting.
     */
    public function send(string $message, array $attachment = []): void;
}