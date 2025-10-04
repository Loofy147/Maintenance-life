<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Repository;

interface RepositoryInterface
{
    public function find(int $id);
    public function findAll(): array;
    public function findBy(array $criteria): array;
    public function save($entity): void;
    public function delete($entity): void;
}