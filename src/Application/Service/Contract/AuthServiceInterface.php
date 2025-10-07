<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service\Contract;

use MaintenancePro\Domain\Entity\User;

/**
 * Defines the contract for an authentication service.
 *
 * This interface outlines the methods required for managing user authentication,
 * including login, logout, session state, and two-factor authentication.
 */
interface AuthServiceInterface
{
    /**
     * Attempts to log a user in with their credentials.
     *
     * @param string $username The user's username.
     * @param string $password The user's password.
     * @return bool True on successful login, false otherwise.
     */
    public function login(string $username, string $password): bool;

    /**
     * Logs the current user out.
     */
    public function logout(): void;

    /**
     * Checks if a user is currently logged in.
     *
     * @return bool True if a user is logged in, false otherwise.
     */
    public function isLoggedIn(): bool;

    /**
     * Gets the currently logged-in user.
     *
     * @return User|null The logged-in User entity, or null if no user is logged in.
     */
    public function getLoggedInUser(): ?User;

    /**
     * Verifies a two-factor authentication code for a given user.
     *
     * @param User   $user The user attempting to verify.
     * @param string $code The two-factor code to verify.
     * @return bool True if the code is valid, false otherwise.
     */
    public function verifyTwoFactorCode(User $user, string $code): bool;

    /**
     * Generates a new two-factor authentication secret for a user.
     *
     * @param User $user The user for whom to generate the secret.
     * @return string The new two-factor secret.
     */
    public function generateTwoFactorSecret(User $user): string;
}