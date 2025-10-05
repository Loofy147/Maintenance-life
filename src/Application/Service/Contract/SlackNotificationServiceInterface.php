<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service\Contract;

interface SlackNotificationServiceInterface
{
    public function send(string $message, array $attachment = []): void;
}