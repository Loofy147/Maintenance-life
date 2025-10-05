<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Application\Service\Contract\WebhookServiceInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;

class WebhookService implements WebhookServiceInterface
{
    private Client $client;
    private ConfigurationInterface $config;
    private LoggerInterface $logger;

    public function __construct(
        Client $client,
        ConfigurationInterface $config,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

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