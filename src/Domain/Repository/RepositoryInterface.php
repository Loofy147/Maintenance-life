<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Repository;

/**
 * Defines a generic interface for repositories.
 *
 * This interface provides a standard contract for data access operations
 * for a specific type of entity.
 *
 * @template T of object
 */
interface RepositoryInterface
{
    /**
     * Finds an entity by its primary identifier.
     *
     * @param int $id The identifier.
     * @return object|null The entity instance or null if not found.
     * @psalm-return T|null
     */
    public function find(int $id);

    /**
     * Finds all entities in the repository.
     *
     * @return array<int, object> An array of entity instances.
     * @psalm-return array<array-key, T>
     */
    public function findAll(): array;

    /**
     * Finds entities by a set of criteria.
     *
     * @param array<string, mixed> $criteria
     * @return array<int, object> An array of entity instances.
     * @psalm-return array<array-key, T>
     */
    public function findBy(array $criteria): array;

    /**
     * Saves an entity (creates or updates).
     *
     * @param object $entity The entity to save.
     * @psalm-param T $entity
     */
    public function save($entity): void;

    /**
     * Deletes an entity.
     *
     * @param object $entity The entity to delete.
     * @psalm-param T $entity
     */
    public function delete($entity): void;
}