<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Infrastructure\Configuration\JsonConfiguration;

class ConfigurationServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(ConfigurationInterface::class, function ($c) {
            $paths = $c->get('paths');
            $configPath = $paths['config'] . '/config.json';

            if (!file_exists($configPath)) {
                $defaultConfig = [
                    'maintenance.enabled' => false,
                    'maintenance.title' => 'Site Under Maintenance',
                    'maintenance.message' => 'We are currently performing scheduled maintenance. We should be back online shortly.',
                    'maintenance.allowed_ips' => [],
                    'maintenance.strategy' => 'default',
                    'maintenance.intelligent' => [
                        'error_rate_threshold' => 5.0,
                        'response_time_threshold' => 1000,
                    ],
                    'app.timezone' => 'UTC',
                    'app.debug' => false,
                    'security.rate_limiting.max_requests' => 100,
                    'security.rate_limiting.time_window' => 60,
                ];
                file_put_contents($configPath, json_encode($defaultConfig, JSON_PRETTY_PRINT));
            }

            $schema = [
                'maintenance.enabled' => ['type' => 'boolean', 'required' => true],
                'security.rate_limiting.max_requests' => ['type' => 'integer'],
                'app.debug' => ['type' => 'boolean'],
            ];

            try {
                return new JsonConfiguration($configPath, $schema);
            } catch (\Exception $e) {
                /** @var LoggerInterface $logger */
                $logger = $c->get(LoggerInterface::class);
                $logger->critical('Failed to load or validate configuration.', ['error' => $e->getMessage()]);
                throw new \RuntimeException('Application could not be initialized due to a configuration error: ' . $e->getMessage(), 0, $e);
            }
        });
    }
}