<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use MaintenancePro\Domain\ValueObjects\IPAddress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IPAddress::class)]
class IPAddressTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_a_valid_ipv4_address(): void
    {
        $ip = new IPAddress('192.168.1.1');
        $this->assertSame('192.168.1.1', $ip->toString());
    }

    #[Test]
    public function it_correctly_identifies_an_ipv4_address(): void
    {
        $ip = new IPAddress('10.0.0.1');
        $this->assertTrue($ip->isIPv4());
        $this->assertFalse($ip->isIPv6());
    }

    #[Test]
    public function it_can_be_created_from_a_valid_ipv6_address(): void
    {
        $ip = new IPAddress('2001:0db8:85a3::8a2e:0370:7334');
        $this->assertTrue($ip->isIPv6());
    }

    #[Test]
    public function it_throws_an_exception_for_an_invalid_ip_address(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IPAddress('999.999.999.999');
    }

    #[Test]
    public function it_can_check_if_it_is_in_a_cidr_range(): void
    {
        $ip = new IPAddress('192.168.1.50');
        $this->assertTrue($ip->inRange('192.168.1.0/24'));
        $this->assertFalse($ip->inRange('192.168.2.0/24'));
    }

    #[Test]
    public function it_can_detect_a_private_ip_address(): void
    {
        $privateIp = new IPAddress('192.168.1.1');
        $publicIp = new IPAddress('8.8.8.8');
        $this->assertTrue($privateIp->isPrivate());
        $this->assertFalse($publicIp->isPrivate());
    }
}