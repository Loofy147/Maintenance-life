<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\Repository;

use MaintenancePro\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function findByUsername(string $username): ?User;
    public function findById(int $id): ?User;
    public function save(User $user): void;
}