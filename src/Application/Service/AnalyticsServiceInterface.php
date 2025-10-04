<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

interface AnalyticsServiceInterface
{
    public function track(string $event, array $properties = []): void;
    public function identify(string $userId, array $traits = []): void;
    public function getMetrics(string $metric, array $filters = []): array;
}