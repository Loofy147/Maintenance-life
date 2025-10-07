<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Health;

use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Infrastructure\FileSystem\FileSystemProvider;
use MaintenancePro\Infrastructure\Health\DiskSpaceHealthCheck;
use PHPUnit\Framework\TestCase;

class DiskSpaceHealthCheckTest extends TestCase
{
    private $fileSystemProviderMock;
    private $cacheMock;
    private DiskSpaceHealthCheck $healthCheck;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystemProviderMock = $this->createMock(FileSystemProvider::class);
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $path = '/var/www';
        $this->healthCheck = new DiskSpaceHealthCheck(
            $path,
            $this->cacheMock,
            $this->fileSystemProviderMock
        );
    }

    public function testFormatBytesWithOneMillionBytes()
    {
        $this->assertFormattedBytes(1000000, '976.56 KB');
    }

    public function testFormatBytesWithZeroBytes()
    {
        $this->assertFormattedBytes(0, '0 B');
    }

    public function testFormatBytesWithLessThanOneKilobyte()
    {
        $this->assertFormattedBytes(500, '500.00 B');
    }

    public function testFormatBytesWithGigabytes()
    {
        $this->assertFormattedBytes(1073741824, '1.00 GB');
    }

    public function testFormatBytesWithTerabytes()
    {
        $this->assertFormattedBytes(1099511627776, '1.00 TB');
    }

    private function assertFormattedBytes(float $bytes, string $expected): void
    {
        $method = new \ReflectionMethod(DiskSpaceHealthCheck::class, 'formatBytes');
        $method->setAccessible(true);
        $actual = $method->invoke($this->healthCheck, $bytes);
        $this->assertEquals($expected, $actual);
    }
}