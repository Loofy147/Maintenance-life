<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Repository;

use MaintenancePro\Domain\Entity\User;

/**
 * Interface for a repository that manages User entities.
 */
interface UserRepositoryInterface
{
    /**
     * Finds a user by their username.
     *
     * @param string $username The username to search for.
     * @return User|null The User entity or null if not found.
     */
    public function findByUsername(string $username): ?User;

    /**
     * Finds a user by their ID.
     *
     * @param int $id The ID of the user.
     * @return User|null The User entity or null if not found.
     */
    public function findById(int $id): ?User;

    /**
     * Saves a user entity.
     *
     * @param User $user The user entity to save.
     */
    public function save(User $user): void;
}