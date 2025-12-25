<?php

abstract class BaseModel
{
    protected static string $table;
    protected static PDO $conn;

    protected static array $ignoredFields = ['id', 'created_at'];
    protected static array $immutableFields = ['id', 'created_at'];

    public static function setConnection(PDO $pdo): void
    {
        self::$conn = $pdo;
    }

    public function save(): bool
    {
        $props = get_object_vars($this);

        $columns = [];
        $placeholders = [];
        $values = [];

        foreach ($props as $key => $value) {
            if (in_array($key, static::$ignoredFields, true) || $value === null) {
                continue;
            }

            $columns[] = $key;
            $placeholders[] = ':' . $key;
            $values[':' . $key] = $value;
        }

        if (empty($columns)) {
            throw new RuntimeException("No data to persist");
        }

        if (!empty($this->id)) {
            $set = [];

            foreach ($columns as $column) {
                if (!in_array($column, static::$immutableFields, true)) {
                    $set[] = "$column = :$column";
                }
            }

            $sql = "UPDATE " . static::$table .
                   " SET " . implode(', ', $set) .
                   " WHERE id = :id";

            $values[':id'] = $this->id;
        } else {
            $sql = "INSERT INTO " . static::$table .
                   " (" . implode(', ', $columns) . ")" .
                   " VALUES (" . implode(', ', $placeholders) . ")";
        }

        $stmt = self::$conn->prepare($sql);
        $success = $stmt->execute($values);

        if (empty($this->id)) {
            $this->id = (int) self::$conn->lastInsertId();
        }

        return $success;
    }

    public function delete(): bool
    {
        if (empty($this->id)) {
            return false;
        }

        $stmt = self::$conn->prepare(
            "DELETE FROM " . static::$table . " WHERE id = :id"
        );

        $success = $stmt->execute([':id' => $this->id]);

        if ($success) {
            $this->id = null;
        }

        return $success;
    }

    public static function findById(int $id): ?static
    {
        $stmt = self::$conn->prepare(
            "SELECT * FROM " . static::$table . " WHERE id = :id"
        );

        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        return $data ? static::hydrate($data) : null;
    }

    public static function all(): array
    {
        $stmt = self::$conn->query(
            "SELECT * FROM " . static::$table . " ORDER BY id DESC"
        );

        return array_map(
            fn($row) => static::hydrate($row),
            $stmt->fetchAll()
        );
    }

    protected static function hydrate(array $data): static
    {
        $instance = (new ReflectionClass(static::class))
            ->newInstanceWithoutConstructor();

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);

            if (method_exists($instance, $setter)) {
                $instance->$setter($value);
            } elseif (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }

        return $instance;
    }

    public static function exists(int $id): bool
    {
        $stmt = self::$conn->prepare(
            "SELECT 1 FROM " . static::$table . " WHERE id = :id"
        );

        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }
}
