<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Strategy;

use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Application\Service\AnalyticsServiceInterface;
use MaintenancePro\Infrastructure\Config\ConfigurationManagerInterface;

class IntelligentMaintenanceStrategy implements MaintenanceStrategyInterface
{
    private ConfigurationManagerInterface $config;
    private AccessControlService $accessControl;
    private AnalyticsServiceInterface $analytics;

    public function __construct(
        ConfigurationManagerInterface $config,
        AccessControlService $accessControl,
        AnalyticsServiceInterface $analytics
    ) {
        $this->config = $config;
        $this->accessControl = $accessControl;
        $this->analytics = $analytics;
    }

    public function shouldEnterMaintenance(array $context): bool
    {
        // Use AI/ML to determine optimal maintenance timing
        $metrics = $this->analytics->getMetrics('traffic', ['period' => 'last_hour']);

        // Enter maintenance if traffic is low
        if (isset($metrics['value']) && $metrics['value'] < 100) {
            return true;
        }

        // Check system health indicators
        if (isset($context['error_rate']) && $context['error_rate'] > 0.05) {
            return true;
        }

        return false;
    }

    public function shouldBypassMaintenance(array $context): bool
    {
        // First check standard bypass rules
        $strategy = new DefaultMaintenanceStrategy($this->config, $this->accessControl);
        if ($strategy->shouldBypassMaintenance($context)) {
            return true;
        }

        // AI-powered user segmentation
        if (isset($context['user_segment'])) {
            $prioritySegments = $this->config->get('ai.priority_user_segments', []);
            if (in_array($context['user_segment'], $prioritySegments, true)) {
                return true;
            }
        }

        return false;
    }

    public function getMaintenanceDuration(): int
    {
        // Predict optimal duration based on historical data
        $metrics = $this->analytics->getMetrics('maintenance_duration', ['limit' => 10]);

        // Use average of last 10 maintenance sessions
        if (!empty($metrics)) {
            $sum = array_sum(array_column($metrics, 'value'));
            return (int)($sum / count($metrics));
        }

        return 3600; // Default 1 hour
    }
}