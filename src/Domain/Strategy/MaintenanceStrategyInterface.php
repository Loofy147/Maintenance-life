<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Strategy;

interface MaintenanceStrategyInterface
{
    public function shouldEnterMaintenance(array $context): bool;
    public function shouldBypassMaintenance(array $context): bool;
    public function getMaintenanceDuration(): int;
}