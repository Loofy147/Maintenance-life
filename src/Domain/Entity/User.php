<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Entity;

/**
 * Represents a user in the system.
 */
class User
{
    private ?int $id = null;
    private string $username;
    private string $password;
    private ?string $twoFactorSecret = null;

    /**
     * @param string $username The username.
     * @param string $password The user's password hash.
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Returns the user's ID.
     *
     * @return int|null The user ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the username.
     *
     * @return string The username.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Returns the user's password hash.
     *
     * @return string The password hash.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Returns the two-factor authentication secret.
     *
     * @return string|null The two-factor secret.
     */
    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    /**
     * Sets the two-factor authentication secret.
     *
     * @param string|null $secret The two-factor secret.
     */
    public function setTwoFactorSecret(?string $secret): void
    {
        $this->twoFactorSecret = $secret;
    }

    /**
     * Checks if two-factor authentication is enabled.
     *
     * @return bool True if 2FA is enabled, false otherwise.
     */
    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorSecret !== null;
    }
}