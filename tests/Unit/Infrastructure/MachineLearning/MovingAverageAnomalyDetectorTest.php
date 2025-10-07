<?php

declare(strict_types=1);

namespace MaintenancePro\Tests\Unit\Infrastructure\MachineLearning;

use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Infrastructure\MachineLearning\MovingAverageAnomalyDetector;
use PHPUnit\Framework\TestCase;

class MovingAverageAnomalyDetectorTest extends TestCase
{
    private $config;
    private $detector;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigurationInterface::class);
        $this->config->method('get')->willReturnMap([
            ['machine_learning.anomaly_detection.moving_average.window_size', 20, 20],
            ['machine_learning.anomaly_detection.moving_average.threshold_multiplier', 3.0, 3.0],
        ]);
        $this->detector = new MovingAverageAnomalyDetector($this->config);
    }

    public function testIsAnomalousReturnsFalseWhenNotEnoughDataPoints()
    {
        $dataPoints = [10.0, 10.0, 10.0];
        $this->assertFalse($this->detector->isAnomalous($dataPoints, 100.0));
    }

    public function testIsAnomalousDetectsClearAnomalyWithNormalData()
    {
        // Data with mean ~50, stddev is not 0
        $dataPoints = [45, 55, 50, 48, 52, 47, 53, 49, 51, 46, 54, 48, 52, 47, 53, 49, 51, 50, 48, 52];
        $currentValue = 80.0; // Clearly outside 3 standard deviations
        $this->assertTrue($this->detector->isAnomalous($dataPoints, $currentValue));
    }

    public function testIsAnomalousCorrectlyIdentifiesNonAnomalyWithNormalData()
    {
        // Data with mean ~50, stddev is not 0
        $dataPoints = [45, 55, 50, 48, 52, 47, 53, 49, 51, 46, 54, 48, 52, 47, 53, 49, 51, 50, 48, 52];
        $currentValue = 55.0; // Within 3 standard deviations
        $this->assertFalse($this->detector->isAnomalous($dataPoints, $currentValue));
    }

    public function testIsAnomalousReturnsFalseForZeroStandardDeviation()
    {
        $dataPoints = array_fill(0, 20, 10.0);
        // With zero standard deviation, z-score is undefined, so it should not be considered an anomaly.
        $this->assertFalse($this->detector->isAnomalous($dataPoints, 11.0));
        $this->assertFalse($this->detector->isAnomalous($dataPoints, 10.0));
    }
}