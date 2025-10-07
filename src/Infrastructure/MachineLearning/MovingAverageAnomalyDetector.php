<?php

declare(strict_types=1);

namespace MaintenancePro\Infrastructure\MachineLearning;

use MaintenancePro\Domain\Contracts\AnomalyDetectorInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;

/**
 * Detects anomalies in a time series using a moving average and standard deviation.
 *
 * This detector calculates the Z-score of a new data point relative to a moving
 * window of previous points. If the Z-score exceeds a configured threshold, the
 * point is considered an anomaly.
 */
class MovingAverageAnomalyDetector implements AnomalyDetectorInterface
{
    private int $windowSize;
    private float $thresholdMultiplier;

    /**
     * MovingAverageAnomalyDetector constructor.
     *
     * @param ConfigurationInterface $config The application configuration to get parameters from.
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->windowSize = $config->get('machine_learning.anomaly_detection.moving_average.window_size', 20);
        $this->thresholdMultiplier = $config->get('machine_learning.anomaly_detection.moving_average.threshold_multiplier', 3.0);
    }

    /**
     * {@inheritdoc}
     *
     * This implementation uses a moving average and standard deviation to calculate
     * the Z-score of the current value.
     */
    public function isAnomalous(array $dataPoints, float $currentValue): bool
    {
        if (count($dataPoints) < $this->windowSize) {
            return false; // Not enough data to make a determination
        }

        $window = array_slice($dataPoints, -$this->windowSize);
        $mean = array_sum($window) / count($window);
        $stdDev = $this->calculateStandardDeviation($window, $mean);

        if ($stdDev === 0.0) {
            return false; // Cannot determine anomaly with zero standard deviation
        }

        $zScore = abs(($currentValue - $mean) / $stdDev);

        return $zScore > $this->thresholdMultiplier;
    }

    /**
     * Calculates the standard deviation of an array of numbers.
     *
     * @param float[] $data The array of numbers.
     * @param float   $mean The mean of the numbers in the array.
     * @return float The standard deviation.
     */
    private function calculateStandardDeviation(array $data, float $mean): float
    {
        $variance = array_reduce($data, function ($carry, $item) use ($mean) {
            return $carry + pow($item - $mean, 2);
        }, 0) / count($data);

        return sqrt($variance);
    }
}