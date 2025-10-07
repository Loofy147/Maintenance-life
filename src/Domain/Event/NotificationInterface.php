<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

/**
 * Defines the contract for a notification.
 *
 * A notification represents a message to be sent to a set of recipients
 * through a specific channel.
 */
interface NotificationInterface
{
    /**
     * Gets the list of recipients for the notification.
     *
     * @return array The list of recipients.
     */
    public function getRecipients(): array;

    /**
     * Gets the subject of the notification.
     *
     * @return string The notification subject.
     */
    public function getSubject(): string;

    /**
     * Gets the message body of the notification.
     *
     * @return string The notification message.
     */
    public function getMessage(): string;

    /**
     * Gets the channel through which the notification should be sent.
     *
     * @return string The notification channel (e.g., 'slack', 'email').
     */
    public function getChannel(): string;
}