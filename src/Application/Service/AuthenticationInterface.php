<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Domain\Entity\UserInterface;

interface AuthenticationInterface
{
    public function authenticate(string $username, string $password): bool;
    public function isAuthenticated(): bool;
    public function getCurrentUser(): ?UserInterface;
    public function logout(): void;
}