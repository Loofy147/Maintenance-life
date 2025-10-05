<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\ValueObjects;

/**
 * Represents an immutable IP address (IPv4 or IPv6).
 * Provides validation and utility methods for working with IP addresses.
 */
final class IPAddress
{
    private string $address;

    /**
     * @param string $address The IP address string.
     * @throws \InvalidArgumentException If the address is not a valid IP.
     */
    public function __construct(string $address)
    {
        if (!$this->isValid($address)) {
            throw new \InvalidArgumentException("Invalid IP address: {$address}");
        }
        $this->address = $address;
    }

    /**
     * Checks if a given string is a valid CIDR notation for IPv4 or IPv6.
     *
     * @param string $cidr The CIDR string to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function isValidCIDR(string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return false;
        }

        [$ip, $bits] = explode('/', $cidr, 2);

        if (!is_numeric($bits)) {
            return false;
        }

        $bits = (int) $bits;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $bits >= 0 && $bits <= 32;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $bits >= 0 && $bits <= 128;
        }

        return false;
    }

    /**
     * Validates an IP address.
     *
     * @param string $address
     * @return bool
     */
    private function isValid(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Returns the string representation of the IP address.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->address;
    }

    /**
     * Checks if the address is an IPv4 address.
     *
     * @return bool
     */
    public function isIPv4(): bool
    {
        return filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Checks if the address is an IPv6 address.
     *
     * @return bool
     */
    public function isIPv6(): bool
    {
        return filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Compares this IP address to another for equality.
     *
     * @param IPAddress $other
     * @return bool
     */
    public function equals(IPAddress $other): bool
    {
        return $this->address === $other->address;
    }

    /**
     * Checks if this IP address falls within a given CIDR range.
     *
     * @param string $cidr The CIDR range to check against.
     * @return bool
     */
    public function inRange(string $cidr): bool
    {
        if (!self::isValidCIDR($cidr)) {
            return $this->address === $cidr;
        }

        list($subnet, $bits) = explode('/', $cidr);
        $bits = (int)$bits;

        if ($this->isIPv6()) {
            return $this->ipv6InRange($subnet, $bits);
        }

        $ip = ip2long($this->address);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);

        return ($ip & $mask) === ($subnet & $mask);
    }

    /**
     * Checks if an IPv6 address is within a given subnet.
     *
     * @param string $subnet
     * @param int $bits
     * @return bool
     */
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

    /**
     * Checks if the IP address is a private address.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return filter_var(
            $this->address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}