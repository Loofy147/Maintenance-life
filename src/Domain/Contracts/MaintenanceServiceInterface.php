<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Contracts;

use MaintenancePro\Domain\Entity\MaintenanceSession;

interface MaintenanceServiceInterface
{
    /**
     * Enables maintenance mode.
     *
     * @param string $reason The reason for enabling maintenance mode.
     * @param \DateTimeImmutable|null $endTime The time when maintenance mode should end.
     * @return MaintenanceSession The created maintenance session.
     */
    public function enable(string $reason, ?\DateTimeImmutable $endTime = null): MaintenanceSession;

    /**
     * Disables maintenance mode.
     */
    public function disable(): void;

    /**
     * Checks if maintenance mode is currently enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Determines whether an incoming request should be blocked.
     *
     * @param array<string, mixed> $context The request context (e.g., IP address).
     * @return bool
     */
    public function shouldBlock(array $context): bool;

    /**
     * Gets the current active maintenance session.
     *
     * @return MaintenanceSession|null
     */
    public function getCurrentSession(): ?MaintenanceSession;

    /**
     * Gets the current maintenance status.
     *
     * @return array{is_active: bool, reason: ?string, scheduled_at: ?string}
     */
    public function getStatus(): array;

    /**
     * Adds an IP address to the whitelist.
     *
     * @param string $ip
     */
    public function addWhitelistedIp(string $ip): void;

    /**
     * Removes an IP address from the whitelist.
     *
     * @param string $ip
     */
    public function removeWhitelistedIp(string $ip): void;
}