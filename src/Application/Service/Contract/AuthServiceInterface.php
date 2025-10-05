<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service\Contract;

use MaintenancePro\Domain\Entity\User;

interface AuthServiceInterface
{
    public function login(string $username, string $password): bool;
    public function logout(): void;
    public function isLoggedIn(): bool;
    public function getLoggedInUser(): ?User;
    public function verifyTwoFactorCode(User $user, string $code): bool;
    public function generateTwoFactorSecret(User $user): string;
}