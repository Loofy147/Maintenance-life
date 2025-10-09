<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Strategy;

use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Domain\Entity\UserInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\ValueObjects\IPAddress;
use MaintenancePro\Domain\ValueObjects\TimePeriod;

/**
 * A default maintenance strategy that checks for manual overrides and scheduled windows.
 */
class DefaultMaintenanceStrategy implements MaintenanceStrategyInterface
{
    private ConfigurationInterface $config;
    private AccessControlService $accessControl;

    /**
     * DefaultMaintenanceStrategy constructor.
     *
     * @param ConfigurationInterface $config The application configuration.
     * @param AccessControlService   $accessControl The access control service.
     */
    public function __construct(
        ConfigurationInterface $config,
        AccessControlService $accessControl
    ) {
        $this->config = $config;
        $this->accessControl = $accessControl;
    }

    /**
     * Determines if maintenance should be activated based on configuration.
     *
     * @param array<string, mixed> $context The current application context.
     * @return bool True if maintenance should be activated, false otherwise.
     */
    public function shouldEnterMaintenance(array $context): bool
    {
        // Check if manually enabled
        if ($this->config->get('maintenance.manual_mode', false)) {
            return true;
        }

        // Check scheduled maintenance
        if ($this->config->get('maintenance.scheduled.enabled', false)) {
            return $this->isInMaintenanceWindow();
        }

        return false;
    }

    /**
     * Determines if a request should bypass maintenance based on IP, access key, or user role.
     *
     * @param array<string, mixed> $context The request context.
     * @return bool True if the request should bypass maintenance, false otherwise.
     */
    public function shouldBypassMaintenance(array $context): bool
    {
        // Check IP whitelist
        if (isset($context['ip'])) {
            try {
                $ip = new IPAddress($context['ip']);
                if ($this->accessControl->isIPWhitelisted($ip)) {
                    return true;
                }
            } catch (\Exception $e) {
                // Invalid IP, don't bypass
            }
        }

        // Check access key
        if (isset($context['access_key'])) {
            if ($this->accessControl->isValidAccessKey($context['access_key'])) {
                return true;
            }
        }

        // Check user role
        if (isset($context['user']) && $context['user'] instanceof UserInterface) {
            if ($context['user']->hasRole('ROLE_ADMIN')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the default maintenance duration from the configuration.
     *
     * @return int The duration in seconds.
     */
    public function getMaintenanceDuration(): int
    {
        return $this->config->get('maintenance.default_duration', 3600);
    }

    /**
     * Checks if the current time is within the configured maintenance window.
     *
     * @return bool True if within the maintenance window, false otherwise.
     */
    private function isInMaintenanceWindow(): bool
    {
        $now = new \DateTimeImmutable();
        $start = $this->config->get('maintenance.scheduled.start_time');
        $end = $this->config->get('maintenance.scheduled.end_time');

        if (!$start || !$end) {
            return false;
        }

        try {
            $startTime = new \DateTimeImmutable($start);
            $endTime = new \DateTimeImmutable($end);
            $period = new TimePeriod($startTime, $endTime);

            return $period->contains($now);
        } catch (\Exception $e) {
            return false;
        }
    }
}