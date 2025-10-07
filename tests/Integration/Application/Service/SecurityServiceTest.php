<?php
declare(strict_types=1);

namespace Tests\Integration\Application\Service;

use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Application\Service\SecurityService;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Infrastructure\Cache\AdaptiveCache;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Infrastructure\Cache\FileCache;
use MaintenancePro\Infrastructure\Configuration\JsonConfiguration;
use MaintenancePro\Infrastructure\Logger\MonologLogger;
use PHPUnit\Framework\TestCase;

class SecurityServiceTest extends TestCase
{
    private string $configFile;
    private JsonConfiguration $config;
    private CacheInterface $cache;
    private string $cacheDir;
    private SecurityService $service;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        // Config
        $this->configFile = tempnam(sys_get_temp_dir(), 'config') . '.json';
        file_put_contents($this->configFile, json_encode([]));
        $this->config = new JsonConfiguration($this->configFile);

        // Cache
        $this->cacheDir = sys_get_temp_dir() . '/maintenance-pro-test-cache-' . uniqid();
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
        $persistentCache = new FileCache($this->cacheDir);
        $this->cache = new AdaptiveCache($persistentCache);
        $this->cache->clear(); // Ensure cache is empty before each test

        // Logger
        $logFile = sys_get_temp_dir() . '/test-' . uniqid() . '.log';
        $logger = new MonologLogger($logFile);

        // Event Dispatcher Mock
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // Service
        $this->service = new SecurityService(
            $this->config,
            $this->cache,
            $logger,
            $this->eventDispatcher
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
        $this->clearCacheDirectory($this->cacheDir);
    }

    private function clearCacheDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($dir);
    }

    public function testIsIPBlockedUsesFreshDataFromCache()
    {
        $ipToBlock = '192.168.1.100';

        // Initial state: IP is not blocked
        $this->assertFalse($this->service->isIPBlocked($ipToBlock), "IP should not be blocked initially.");

        // Simulate another process updating the cache directly
        $this->cache->set('blocked_ips', [$ipToBlock], 86400);

        // The service should now see the newly blocked IP.
        // This assertion will fail because the service is using its stale internal state.
        $this->assertTrue($this->service->isIPBlocked($ipToBlock), "IP should be blocked after cache is updated externally.");
    }
}