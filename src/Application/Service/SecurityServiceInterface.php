<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

/**
 * Defines the contract for a security service.
 *
 * This interface outlines the methods for request validation, threat detection,
 * and IP address management.
 */
interface SecurityServiceInterface
{
    /**
     * Validates an incoming request against configured security rules.
     *
     * @return bool True if the request is valid, false otherwise.
     */
    public function validateRequest(): bool;

    /**
     * Detects potential security threats based on the request context.
     *
     * @param array<string, mixed> $context The request context to analyze.
     * @return array<int, array<string, mixed>> A list of detected threats.
     */
    public function detectThreats(array $context): array;

    /**
     * Adds an IP address to the blocklist.
     *
     * @param string $ip The IP address to block.
     */
    public function blockIP(string $ip): void;

    /**
     * Checks if an IP address is currently blocked.
     *
     * @param string $ip The IP address to check.
     * @return bool True if the IP is blocked, false otherwise.
     */
    public function isIPBlocked(string $ip): bool;
}