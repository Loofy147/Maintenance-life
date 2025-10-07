<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Repository;

use MaintenancePro\Domain\Repository\RepositoryInterface;
use MaintenancePro\Infrastructure\Logger\LoggerInterface;

/**
 * An abstract base class for repositories that use a SQLite database.
 *
 * This class provides a generic implementation of the RepositoryInterface, handling
 * common CRUD operations. Subclasses must implement the `hydrate` and `extract`
 * methods to map between database rows and entity objects.
 */
abstract class SQLiteRepository implements RepositoryInterface
{
    protected \PDO $db;
    protected string $table;
    protected LoggerInterface $logger;

    /**
     * SQLiteRepository constructor.
     *
     * @param \PDO            $db     The PDO database connection.
     * @param string          $table  The name of the database table for this repository.
     * @param LoggerInterface $logger The logger for recording repository activity.
     */
    public function __construct(\PDO $db, string $table, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->table = $table;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        $results = [];

        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($data);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria): array
    {
        $conditions = [];
        $params = [];

        foreach ($criteria as $key => $value) {
            $conditions[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $where = implode(' AND ', $conditions);
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$where}");
        $stmt->execute($params);

        $results = [];
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($data);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity): void
    {
        $data = $this->extract($entity);

        if (isset($data['id']) && $data['id']) {
            $this->update($data);
        } else {
            $this->insert($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity): void
    {
        $data = $this->extract($entity);

        if (!isset($data['id'])) {
            throw new \LogicException('Cannot delete entity without ID');
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $data['id']]);
    }

    /**
     * Inserts a new record into the database.
     *
     * @param array<string, mixed> $data The data to insert.
     */
    protected function insert(array $data): void
    {
        unset($data['id']);

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    /**
     * Updates an existing record in the database.
     *
     * @param array<string, mixed> $data The data to update, including the 'id'.
     */
    protected function update(array $data): void
    {
        $id = $data['id'];
        unset($data['id']);

        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id";
        $data['id'] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    /**
     * Creates an entity object from an array of database data.
     *
     * @param array<string, mixed> $data The raw data from the database.
     * @return object The hydrated entity object.
     */
    abstract protected function hydrate(array $data);

    /**
     * Extracts an array of data from an entity object for database storage.
     *
     * @param object $entity The entity object.
     * @return array<string, mixed> The extracted data.
     */
    abstract protected function extract($entity): array;
}