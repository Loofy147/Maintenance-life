<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Application\Service\Contract\SlackNotificationServiceInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;

class SlackNotificationService implements SlackNotificationServiceInterface
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

    public function send(string $message, array $attachment = []): void
    {
        $webhookUrl = $this->config->get('slack.webhook_url');

        if (empty($webhookUrl)) {
            return;
        }

        $payload = [
            'text' => $message,
            'attachments' => [$attachment],
        ];

        try {
            $this->client->post($webhookUrl, [
                'json' => $payload,
                'timeout' => 10,
            ]);
            $this->logger->info("Slack notification sent successfully");
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to send Slack notification", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}