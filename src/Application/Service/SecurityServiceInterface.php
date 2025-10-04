<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

interface SecurityServiceInterface
{
    public function validateRequest(): bool;
    public function detectThreats(array $context): array;
    public function blockIP(string $ip): void;
    public function isIPBlocked(string $ip): bool;
}