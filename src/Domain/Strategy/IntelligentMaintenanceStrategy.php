<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Strategy;

use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Application\Service\MetricsServiceInterface;
use MaintenancePro\Infrastructure\Config\ConfigurationManagerInterface;

class IntelligentMaintenanceStrategy implements MaintenanceStrategyInterface
{
    private ConfigurationManagerInterface $config;
    private AccessControlService $accessControl;
    private MetricsServiceInterface $metrics;

    public function __construct(
        ConfigurationManagerInterface $config,
        AccessControlService $accessControl,
        MetricsServiceInterface $metrics
    ) {
        $this->config = $config;
        $this->accessControl = $accessControl;
        $this->metrics = $metrics;
    }

    public function shouldEnterMaintenance(array $context): bool
    {
        $report = $this->metrics->generateReport();
        $traffic = $report['request.count'] ?? null;

        // Automatically enable if traffic in the last 24 hours is below a threshold.
        // This is a simple heuristic. A real-world implementation would be more complex.
        $trafficThreshold = $this->config->get('maintenance.intelligent.traffic_threshold', 100);
        if ($traffic && $traffic['total'] < $trafficThreshold) {
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
        $report = $this->metrics->generateReport();
        $durationMetric = $report['maintenance.duration'] ?? null;

        if ($durationMetric && isset($durationMetric['average'])) {
            return (int) $durationMetric['average'];
        }

        return 3600; // Default 1 hour
    }
}