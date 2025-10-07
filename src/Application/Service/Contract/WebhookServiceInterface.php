<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service\Contract;

/**
 * Defines the contract for a service that sends webhook notifications.
 */
interface WebhookServiceInterface
{
    /**
     * Sends a payload to all configured webhooks for a specific event.
     *
     * @param string               $event   The name of the event being triggered (e.g., 'maintenance.enabled').
     * @param array<string, mixed> $payload The data to be sent in the webhook payload.
     */
    public function send(string $event, array $payload): void;
}