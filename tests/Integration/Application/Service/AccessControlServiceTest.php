<?php
declare(strict_types=1);

namespace Tests\Integration\Application\Service;

use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Domain\ValueObjects\IPAddress;
use MaintenancePro\Infrastructure\Cache\AdaptiveCache;
use MaintenancePro\Infrastructure\Cache\FileCache;
use MaintenancePro\Infrastructure\Configuration\JsonConfiguration;
use MaintenancePro\Infrastructure\Logger\MonologLogger;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class AccessControlServiceTest extends TestCase
{
    private string $configFile;
    private JsonConfiguration $config;
    private AdaptiveCache $cache;
    private AccessControlService $service;
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        // Set up a temporary config file
        $this->configFile = tempnam(sys_get_temp_dir(), 'config') . '.json';
        file_put_contents($this->configFile, json_encode([
            'access' => [
                'whitelist' => [
                    'ips' => []
                ]
            ]
        ]));
        $this->config = new JsonConfiguration($this->configFile);

        // Set up cache
        $this->cacheDir = sys_get_temp_dir() . '/maintenance-pro-test-cache-' . uniqid();
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
        $persistentCache = new FileCache($this->cacheDir);
        $this->cache = new AdaptiveCache($persistentCache);

        // Set up logger
        $logFile = sys_get_temp_dir() . '/test-' . uniqid() . '.log';
        $logger = new MonologLogger($logFile);

        // Create the service
        $this->service = new AccessControlService($this->config, $this->cache, $logger);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up the config file
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }

        // Clean up cache
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

    public function testAddToWhitelistShouldInvalidateCache()
    {
        $ip = '192.168.1.10';
        $ipAddress = new IPAddress($ip);

        // First check, which caches the 'false' result
        $this->assertFalse($this->service->isIPWhitelisted($ipAddress), 'IP should not be whitelisted initially.');

        // Add the IP to the whitelist
        $this->service->addToWhitelist($ip);

        // Second check, which should now be true
        $this->assertTrue($this->service->isIPWhitelisted($ipAddress), 'IP should be whitelisted after being added.');
    }

    public function testRemoveFromWhitelistShouldInvalidateCache()
    {
        $ip = '192.168.1.20';
        $ipAddress = new IPAddress($ip);

        // Add the IP and check to cache the 'true' result
        $this->service->addToWhitelist($ip);
        $this->assertTrue($this->service->isIPWhitelisted($ipAddress), 'IP should be whitelisted initially.');

        // Remove the IP from the whitelist
        $this->service->removeFromWhitelist($ip);

        // Second check, which should now be false
        $this->assertFalse($this->service->isIPWhitelisted($ipAddress), 'IP should not be whitelisted after being removed.');
    }
}