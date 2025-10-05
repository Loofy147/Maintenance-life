<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cache;

use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Infrastructure\Cache\AdaptiveCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdaptiveCache::class)]
class AdaptiveCacheTest extends TestCase
{
    #[Test]
    public function it_can_set_and_get_a_value(): void
    {
        $persistentCache = $this->createMock(CacheInterface::class);
        $persistentCache->expects($this->once())
            ->method('set')
            ->with('test_key', 'test_value', 3600)
            ->willReturn(true);

        $persistentCache->expects($this->never()) // Should be hit from memory on second get
            ->method('get');

        $cache = new AdaptiveCache($persistentCache);

        $cache->set('test_key', 'test_value', 3600);
        $this->assertSame('test_value', $cache->get('test_key'));
        // Second get should hit the memory cache
        $this->assertSame('test_value', $cache->get('test_key'));
    }

    #[Test]
    public function it_falls_back_to_persistent_cache_if_not_in_memory(): void
    {
        $persistentCache = $this->createMock(CacheInterface::class);
        $persistentCache->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->willReturn('persistent_value');

        $cache = new AdaptiveCache($persistentCache);

        $this->assertSame('persistent_value', $cache->get('test_key'));
    }
}