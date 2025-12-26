<?php
require_once __DIR__ ."/../config/db.php";
require_once __DIR__ . '/../core/BaseModel.php';

class Member extends BaseModel
{
    protected static string $table = 'members';

    protected ?int $id = null;
    protected string $name;
    protected string $email;
    protected ?string $created_at = null;

    public function __construct(string $name, string $email)
    {
        $this->setName($name);
        $this->setEmail($email);
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }

    public function setName(string $name): void
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException("Name is required");
        }
        $this->name = $name;
    }

    public function setEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email");
        }

        if (self::emailExists($email, $this->id)) {
            throw new InvalidArgumentException("Email already exists");
        }

        $this->email = $email;
    }

    public static function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM members WHERE email = :email";
        $params = [':email' => $email];

        if ($ignoreId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignoreId;
        }

        $stmt = self::$conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }
}


