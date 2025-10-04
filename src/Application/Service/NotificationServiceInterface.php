<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Domain\Event\NotificationInterface;

interface NotificationServiceInterface
{
    public function send(NotificationInterface $notification): bool;
    public function sendAsync(NotificationInterface $notification): void;
}