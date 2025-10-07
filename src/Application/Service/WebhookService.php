<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Application\Service\Contract\WebhookServiceInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;

/**
 * Service for sending notifications to generic webhooks.
 */
class WebhookService implements WebhookServiceInterface
{
    private Client $client;
    private ConfigurationInterface $config;
    private LoggerInterface $logger;

    /**
     * WebhookService constructor.
     *
     * @param Client                 $client The Guzzle HTTP client for making requests.
     * @param ConfigurationInterface $config The application configuration.
     * @param LoggerInterface        $logger The logger for recording webhook status.
     */
    public function __construct(
        Client $client,
        ConfigurationInterface $config,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Sends a payload to all configured webhook URLs.
     *
     * @param string               $event   The name of the event being triggered.
     * @param array<string, mixed> $payload The data to be sent in the webhook payload.
     */
    public function send(string $event, array $payload): void
    {
        $webhooks = $this->config->get('webhooks', []);

        if (empty($webhooks)) {
            return;
        }

        $data = [
            'event' => $event,
            'payload' => $payload,
            'timestamp' => (new \DateTime())->format('c'),
        ];

        foreach ($webhooks as $url) {
            try {
                $this->client->post($url, [
                    'json' => $data,
                    'timeout' => 10,
                ]);
                $this->logger->info("Webhook sent successfully to {$url}");
            } catch (GuzzleException $e) {
                $this->logger->error("Failed to send webhook to {$url}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}