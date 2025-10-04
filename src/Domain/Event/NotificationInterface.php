<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Event;

interface NotificationInterface
{
    public function getRecipients(): array;
    public function getSubject(): string;
    public function getMessage(): string;
    public function getChannel(): string;
}