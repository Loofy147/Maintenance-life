<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service\Contract;

interface WebhookServiceInterface
{
    public function send(string $event, array $payload): void;
}