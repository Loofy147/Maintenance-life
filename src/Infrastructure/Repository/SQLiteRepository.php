<?php
declare(strict_types=1);

namespace MaintenancePro\Infrastructure\Repository;

use MaintenancePro\Domain\Repository\RepositoryInterface;
use MaintenancePro\Infrastructure\Logger\LoggerInterface;

abstract class SQLiteRepository implements RepositoryInterface
{
    protected \PDO $db;
    protected string $table;
    protected LoggerInterface $logger;

    public function __construct(\PDO $db, string $table, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->table = $table;
        $this->logger = $logger;
    }

    public function find(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        $results = [];

        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($data);
        }

        return $results;
    }

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

    public function save($entity): void
    {
        $data = $this->extract($entity);

        if (isset($data['id']) && $data['id']) {
            $this->update($data);
        } else {
            $this->insert($data);
        }
    }

    public function delete($entity): void
    {
        $data = $this->extract($entity);

        if (!isset($data['id'])) {
            throw new \LogicException('Cannot delete entity without ID');
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $data['id']]);
    }

    protected function insert(array $data): void
    {
        unset($data['id']);

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

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

    abstract protected function hydrate(array $data);
    abstract protected function extract($entity): array;
}