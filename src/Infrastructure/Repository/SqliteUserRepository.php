<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Repository;

use MaintenancePro\Domain\Entity\User;
use MaintenancePro\Domain\Repository\UserRepositoryInterface;
use PDO;

/**
 * A SQLite-based implementation of the UserRepositoryInterface.
 *
 * This repository manages the persistence of User entities in a SQLite database.
 */
class SqliteUserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    /**
     * SqliteUserRepository constructor.
     *
     * @param PDO $pdo The PDO database connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->createTable();
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $user = new User($data['username'], $data['password']);
        $user->setTwoFactorSecret($data['two_factor_secret']);
        // This is a hack, as we don't have a proper hydrator
        $reflector = new \ReflectionProperty(User::class, 'id');
        $reflector->setAccessible(true);
        $reflector->setValue($user, $data['id']);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $user = new User($data['username'], $data['password']);
        $user->setTwoFactorSecret($data['two_factor_secret']);
        // This is a hack, as we don't have a proper hydrator
        $reflector = new \ReflectionProperty(User::class, 'id');
        $reflector->setAccessible(true);
        $reflector->setValue($user, $data['id']);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function save(User $user): void
    {
        if ($user->getId()) {
            $stmt = $this->pdo->prepare(
                'UPDATE users SET password = :password, two_factor_secret = :two_factor_secret WHERE id = :id'
            );
            $stmt->execute([
                'password' => $user->getPassword(),
                'two_factor_secret' => $user->getTwoFactorSecret(),
                'id' => $user->getId(),
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (username, password, two_factor_secret) VALUES (:username, :password, :two_factor_secret)'
            );
            $stmt->execute([
                'username' => $user->getUsername(),
                'password' => $user->getPassword(),
                'two_factor_secret' => $user->getTwoFactorSecret(),
            ]);
        }
    }

    /**
     * Creates the `users` table in the database if it doesn't already exist.
     */
    private function createTable(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                two_factor_secret VARCHAR(255)
            )
        ');
    }
}