<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Domain\Event\NotificationInterface;

/**
 * Defines the contract for a notification service.
 *
 * This interface provides methods for sending notifications both synchronously
 * and asynchronously.
 */
interface NotificationServiceInterface
{
    /**
     * Sends a notification immediately.
     *
     * @param NotificationInterface $notification The notification to send.
     * @return bool True on success, false on failure.
     */
    public function send(NotificationInterface $notification): bool;

    /**
     * Sends a notification asynchronously, typically via a message queue.
     *
     * @param NotificationInterface $notification The notification to send.
     */
    public function sendAsync(NotificationInterface $notification): void;
}