<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Entity;

class User
{
    private ?int $id = null;
    private string $username;
    private string $password;
    private ?string $twoFactorSecret = null;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function setTwoFactorSecret(?string $secret): void
    {
        $this->twoFactorSecret = $secret;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorSecret !== null;
    }
}