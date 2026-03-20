<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Resource
{
    private PDO $connection;

    private string $table = 'resources';

    private array $columns = [
        'campo1',
        'campo2',
        'campo3',
        'campo4',
        'campo5',
        'campo6',
    ];

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? database();
    }

    public function all(): array
    {
        $statement = $this->connection->query(
            "SELECT id, campo1, campo2, campo3, campo4, campo5, campo6
             FROM {$this->table}
             ORDER BY id ASC"
        );

        return array_map([$this, 'mapRow'], $statement->fetchAll());
    }

    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, campo1, campo2, campo3, campo4, campo5, campo6
             FROM {$this->table}
             WHERE id = :id"
        );

        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    public function create(array $data): array
    {
        $statement = $this->connection->prepare(
            "INSERT INTO {$this->table} (campo1, campo2, campo3, campo4, campo5, campo6)
             VALUES (:campo1, :campo2, :campo3, :campo4, :campo5, :campo6)
             RETURNING id, campo1, campo2, campo3, campo4, campo5, campo6"
        );

        $this->executeStatement($statement, $this->bindableData($data));

        return $this->mapRow($statement->fetch());
    }

    public function update(int $id, array $data): ?array
    {
        $statement = $this->connection->prepare(
            "UPDATE {$this->table}
             SET campo1 = :campo1,
                 campo2 = :campo2,
                 campo3 = :campo3,
                 campo4 = :campo4,
                 campo5 = :campo5,
                 campo6 = :campo6
             WHERE id = :id
             RETURNING id, campo1, campo2, campo3, campo4, campo5, campo6"
        );

        $this->executeStatement($statement, $this->bindableData($data, $id));
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    public function patch(int $id, array $data): ?array
    {
        if ($data === []) {
            return $this->find($id);
        }

        $sets = [];
        $params = ['id' => $id];

        foreach ($data as $field => $value) {
            if (!in_array($field, $this->columns, true)) {
                continue;
            }

            $sets[] = sprintf('%s = :%s', $field, $field);
            $params[$field] = $value;
        }

        if ($sets === []) {
            return $this->find($id);
        }

        $statement = $this->connection->prepare(
            sprintf(
                "UPDATE %s SET %s WHERE id = :id RETURNING id, campo1, campo2, campo3, campo4, campo5, campo6",
                $this->table,
                implode(', ', $sets)
            )
        );

        $this->executeStatement($statement, $params);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare(
            "DELETE FROM {$this->table} WHERE id = :id"
        );

        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function bindableData(array $data, ?int $id = null): array
    {
        $payload = [];

        foreach ($this->columns as $column) {
            $payload[$column] = $data[$column];
        }

        if ($id !== null) {
            $payload['id'] = $id;
        }

        return $payload;
    }

    private function executeStatement(PDOStatement $statement, array $params): void
    {
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, $this->pdoTypeFor($value));
        }

        $statement->execute();
    }

    private function pdoTypeFor(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }

    private function mapRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'campo1' => (string) $row['campo1'],
            'campo2' => (string) $row['campo2'],
            'campo3' => (string) $row['campo3'],
            'campo4' => (int) $row['campo4'],
            'campo5' => (float) $row['campo5'],
            'campo6' => $this->toBoolean($row['campo6']),
        ];
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return in_array($value, ['t', 'true', '1', 1], true);
    }
}
