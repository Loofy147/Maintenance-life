<?php

declare(strict_types=1);

namespace MaintenancePro\Domain\Contracts;

interface AnomalyDetectorInterface
{
    /**
     * Analyzes a series of data points to determine if the latest value is an anomaly.
     *
     * @param float[] $dataPoints A series of historical data points.
     * @param float $currentValue The current value to check.
     * @return bool True if the current value is considered an anomaly, false otherwise.
     */
    public function isAnomalous(array $dataPoints, float $currentValue): bool;
}