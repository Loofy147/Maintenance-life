<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Entity;

use MaintenancePro\Domain\ValueObject\Email;

class User implements UserInterface
{
    private int $id;
    private string $username;
    private Email $email;
    private string $passwordHash;
    private array $roles;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        int $id,
        string $username,
        Email $email,
        string $passwordHash,
        array $roles = ['ROLE_USER']
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->roles = $roles;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email->toString();
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }
}