<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Strategy;

use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Domain\Entity\UserInterface;
use MaintenancePro\Domain\ValueObject\IPAddress;
use MaintenancePro\Domain\ValueObject\TimePeriod;
use MaintenancePro\Infrastructure\Config\ConfigurationManagerInterface;

class DefaultMaintenanceStrategy implements MaintenanceStrategyInterface
{
    private ConfigurationManagerInterface $config;
    private AccessControlService $accessControl;

    public function __construct(
        ConfigurationManagerInterface $config,
        AccessControlService $accessControl
    ) {
        $this->config = $config;
        $this->accessControl = $accessControl;
    }

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

    public function getMaintenanceDuration(): int
    {
        return $this->config->get('maintenance.default_duration', 3600);
    }

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