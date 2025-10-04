<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\ValueObject;

final class IPAddress
{
    private string $address;

    public function __construct(string $address)
    {
        if (!$this->isValid($address)) {
            throw new \InvalidArgumentException("Invalid IP address: {$address}");
        }
        $this->address = $address;
    }

    private function isValid(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP) !== false;
    }

    public function toString(): string
    {
        return $this->address;
    }

    public function isIPv4(): bool
    {
        return filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function isIPv6(): bool
    {
        return filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function equals(IPAddress $other): bool
    {
        return $this->address === $other->address;
    }

    public function inRange(string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return $this->address === $cidr;
        }

        list($subnet, $bits) = explode('/', $cidr);

        if ($this->isIPv6()) {
            return $this->ipv6InRange($subnet, (int)$bits);
        }

        $ip = ip2long($this->address);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);

        return ($ip & $mask) === ($subnet & $mask);
    }

    private function ipv6InRange(string $subnet, int $bits): bool
    {
        $ip = inet_pton($this->address);
        $subnet = inet_pton($subnet);

        $bytes = intval($bits / 8);
        $remainder = $bits % 8;

        if ($bytes > 0 && substr($ip, 0, $bytes) !== substr($subnet, 0, $bytes)) {
            return false;
        }

        if ($remainder > 0) {
            $mask = 0xFF << (8 - $remainder);
            return (ord($ip[$bytes]) & $mask) === (ord($subnet[$bytes]) & $mask);
        }

        return true;
    }
}