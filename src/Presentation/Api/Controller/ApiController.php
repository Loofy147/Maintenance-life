<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Api\Controller;

use MaintenancePro\Domain\Contracts\CircuitBreakerInterface;
use MaintenancePro\Domain\Contracts\HealthCheckInterface;
use MaintenancePro\Domain\Contracts\MaintenanceServiceInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Domain\Exceptions\ValidationException;

class ApiController
{
    private MaintenanceServiceInterface $maintenanceService;
    private HealthCheckInterface $healthCheck;
    private CircuitBreakerInterface $circuitBreaker;
    private MetricsInterface $metrics;

    public function __construct(
        MaintenanceServiceInterface $maintenanceService,
        HealthCheckInterface $healthCheck,
        CircuitBreakerInterface $circuitBreaker,
        MetricsInterface $metrics
    ) {
        $this->maintenanceService = $maintenanceService;
        $this->healthCheck = $healthCheck;
        $this->circuitBreaker = $circuitBreaker;
        $this->metrics = $metrics;
    }

    // Maintenance Management
    public function getMaintenanceStatus()
    {
        $status = $this->maintenanceService->getStatus();
        return [
            'is_active' => $status['is_active'],
            'reason' => $status['reason'] ?? null,
            'scheduled_at' => $status['scheduled_at'] ?? null,
        ];
    }

    public function enableMaintenance()
    {
        $data = $this->getJsonBody();
        $reason = $data['reason'] ?? 'Scheduled maintenance';
        $duration = isset($data['duration']) ? (int)$data['duration'] : 3600;

        $endTime = (new \DateTimeImmutable())->setTimestamp(time() + $duration);
        $this->maintenanceService->enable($reason, $endTime);

        return ['message' => 'Maintenance mode enabled.'];
    }

    public function disableMaintenance()
    {
        $this->maintenanceService->disable();
        return ['message' => 'Maintenance mode disabled.'];
    }

    // IP Whitelist
    public function addIpToWhitelist()
    {
        $data = $this->getJsonBody();
        $ip = $data['ip'] ?? null;

        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new ValidationException("Invalid or missing IP address.");
        }

        $this->maintenanceService->addWhitelistedIp($ip);
        return ['message' => "IP {$ip} added to whitelist."];
    }

    public function removeIpFromWhitelist()
    {
        $data = $this->getJsonBody();
        $ip = $data['ip'] ?? null;

        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new ValidationException("Invalid or missing IP address.");
        }

        $this->maintenanceService->removeWhitelistedIp($ip);
        return ['message' => "IP {$ip} removed from whitelist."];
    }

    // Health Checks
    public function getHealthCheck()
    {
        return $this->healthCheck->run();
    }

    // Circuit Breakers
    public function getCircuitBreakers()
    {
        return $this->circuitBreaker->getAllStatuses();
    }

    public function resetCircuitBreaker(string $service)
    {
        $this->circuitBreaker->reset($service);
        return ['message' => "Circuit breaker for '{$service}' has been reset."];
    }

    // Metrics
    public function getMetrics()
    {
        $range = $_GET['range'] ?? '24h'; // e.g., '1h', '24h', '7d'
        return $this->metrics->getSummary($range);
    }

    public function getMetricsReport()
    {
        return $this->metrics->generateReport();
    }

    /**
     * @return array
     */
    private function getJsonBody(): array
    {
        $body = file_get_contents('php://input');
        if (empty($body)) {
            return [];
        }
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON provided.');
        }

        return $data;
    }
}