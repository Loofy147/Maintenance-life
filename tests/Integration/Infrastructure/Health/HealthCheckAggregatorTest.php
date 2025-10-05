<?php
declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Health;

use MaintenancePro\Application\Kernel;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HealthCheckAggregator::class)]
class HealthCheckAggregatorTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();
        $tempDir = sys_get_temp_dir() . '/mp_integration_tests_' . uniqid();
        mkdir($tempDir, 0755, true);
        $this->kernel = new Kernel($tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore original handlers to avoid "risky" test warnings
        restore_error_handler();
        restore_exception_handler();

        // Cleanup the temporary directory
        $tempDir = $this->kernel->getContainer()->get('paths')['root'];
        if (is_dir($tempDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
            rmdir($tempDir);
        }
    }

    #[Test]
    public function it_runs_all_registered_health_checks_and_returns_a_report(): void
    {
        /** @var HealthCheckAggregator $aggregator */
        $aggregator = $this->kernel->getContainer()->get(HealthCheckAggregator::class);
        $report = $aggregator->runAll();

        $this->assertArrayHasKey('status', $report);
        $this->assertArrayHasKey('checks', $report);

        $this->assertArrayHasKey('database', $report['checks']);
        $this->assertArrayHasKey('cache', $report['checks']);
        $this->assertArrayHasKey('disk_space', $report['checks']);

        $this->assertTrue($report['checks']['database']['healthy']);
        $this->assertTrue($report['checks']['cache']['healthy']);
        $this->assertTrue($report['checks']['disk_space']['healthy']);
    }
}