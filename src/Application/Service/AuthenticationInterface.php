<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Domain\Entity\UserInterface;

/**
 * Defines the contract for a basic authentication service.
 *
 * @deprecated This interface is being replaced by AuthServiceInterface.
 */
interface AuthenticationInterface
{
    /**
     * Authenticates a user with the given credentials.
     *
     * @param string $username The user's username.
     * @param string $password The user's password.
     * @return bool True on success, false on failure.
     */
    public function authenticate(string $username, string $password): bool;

    /**
     * Checks if a user is currently authenticated.
     *
     * @return bool True if a user is authenticated, false otherwise.
     */
    public function isAuthenticated(): bool;

    /**
     * Gets the currently authenticated user.
     *
     * @return UserInterface|null The current user, or null if not authenticated.
     */
    public function getCurrentUser(): ?UserInterface;

    /**
     * Logs the current user out.
     */
    public function logout(): void;
}