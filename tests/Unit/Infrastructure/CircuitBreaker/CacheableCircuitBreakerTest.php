<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\CircuitBreaker;

use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Infrastructure\CircuitBreaker\CacheableCircuitBreaker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheableCircuitBreaker::class)]
class CacheableCircuitBreakerTest extends TestCase
{
    private CacheInterface $cache;
    private CacheableCircuitBreaker $breaker;
    private int $failureThreshold = 3;
    private int $openTimeout = 10; // 10 seconds

    protected function setUp(): void
    {
        parent::setUp();
        // Use an in-memory array as a mock cache for testing
        $this->cache = new class implements CacheInterface {
            private array $store = [];
            public function get(string $key, $default = null) { return $this->store[$key] ?? $default; }
            public function set(string $key, $value, int $ttl = 3600): bool { $this->store[$key] = $value; return true; }
            public function has(string $key): bool { return isset($this->store[$key]); }
            public function delete(string $key): bool { unset($this->store[$key]); return true; }
            public function clear(): bool { $this->store = []; return true; }
            public function getStats(): array { return []; }
        };

        $this->breaker = new CacheableCircuitBreaker($this->cache, $this->failureThreshold, $this->openTimeout);
    }

    #[Test]
    public function it_is_available_when_the_circuit_is_closed(): void
    {
        $this->assertTrue($this->breaker->isAvailable('test_service'));
    }

    #[Test]
    public function it_trips_the_circuit_after_the_failure_threshold_is_reached(): void
    {
        $serviceName = 'failing_service';

        for ($i = 0; $i < $this->failureThreshold - 1; $i++) {
            $this->breaker->recordFailure($serviceName);
            $this->assertTrue($this->breaker->isAvailable($serviceName), "Should be available after " . ($i + 1) . " failures.");
        }

        $this->breaker->recordFailure($serviceName);

        $this->assertFalse($this->breaker->isAvailable($serviceName), "Should be unavailable after " . $this->failureThreshold . " failures.");
        $status = $this->breaker->getStatus($serviceName);
        $this->assertSame('OPEN', $status['state']);
    }

    #[Test]
    public function it_resets_the_circuit_after_a_success(): void
    {
        $serviceName = 'recovering_service';

        $this->breaker->recordFailure($serviceName);
        $this->breaker->recordFailure($serviceName);
        $this->breaker->recordSuccess($serviceName);

        $status = $this->breaker->getStatus($serviceName);
        $this->assertSame(0, $status['failures']);
        $this->assertSame('CLOSED', $status['state']);
        $this->assertTrue($this->breaker->isAvailable($serviceName));
    }

    #[Test]
    public function it_transitions_to_half_open_state_and_recovers_on_success(): void
    {
        $serviceName = 'half_open_service';

        // 1. Trip the circuit to OPEN
        for ($i = 0; $i < $this->failureThreshold; $i++) {
            $this->breaker->recordFailure($serviceName);
        }
        $this->assertSame('OPEN', $this->breaker->getStatus($serviceName)['state']);

        // 2. Simulate time passing by manually setting the last failure time in the past
        $cacheKey = "circuit_breaker.{$serviceName}.last_failure";
        $this->cache->set($cacheKey, time() - ($this->openTimeout + 1));

        // 3. Verify it is now HALF_OPEN and available
        $status = $this->breaker->getStatus($serviceName);
        $this->assertSame('HALF_OPEN', $status['state']);
        $this->assertTrue($this->breaker->isAvailable($serviceName));

        // 4. Record a success and verify it returns to CLOSED
        $this->breaker->recordSuccess($serviceName);
        $status = $this->breaker->getStatus($serviceName);
        $this->assertSame('CLOSED', $status['state']);
        $this->assertSame(0, $status['failures']);
    }

    #[Test]
    public function it_transitions_to_half_open_and_re_opens_on_failure(): void
    {
        $serviceName = 'half_open_failing_service';

        // 1. Trip the circuit to OPEN
        for ($i = 0; $i < $this->failureThreshold; $i++) {
            $this->breaker->recordFailure($serviceName);
        }

        // 2. Simulate time passing for it to become HALF_OPEN
        $cacheKey = "circuit_breaker.{$serviceName}.last_failure";
        $this->cache->set($cacheKey, time() - ($this->openTimeout + 1));
        $this->assertSame('HALF_OPEN', $this->breaker->getStatus($serviceName)['state']);

        // 3. Record another failure
        $this->breaker->recordFailure($serviceName);

        // 4. Verify it is back to OPEN state
        $status = $this->breaker->getStatus($serviceName);
        $this->assertSame('OPEN', $status['state']);
        $this->assertFalse($this->breaker->isAvailable($serviceName));
    }
}