<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Entity;

interface UserInterface
{
    public function getId(): int;
    public function getUsername(): string;
    public function getEmail(): string;
    public function getRoles(): array;
    public function hasRole(string $role): bool;
}