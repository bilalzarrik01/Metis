<?php
require_once __DIR__ . '/../core/BaseModel.php';

class Activite extends BaseModel
{
    protected static string $table = 'activites';
    protected static array $ignoredFields = ['id', 'created_at'];
    protected static array $immutableFields = ['id', 'created_at'];

    protected ?int $id = null;
    protected int $project_id;
    protected string $description;
    protected string $status = 'en_cours';
    protected ?string $created_at = null;

    public function __construct(int $project_id, string $description)
    {
        $this->setProjectId($project_id);
        $this->setDescription($description);
    }

    public function getId(): ?int { return $this->id; }
    public function getProjectId(): int { return $this->project_id; }
    public function getDescription(): string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): ?string { return $this->created_at; }

    public function setProjectId(int $project_id): void
    {
        if ($project_id <= 0) {
            throw new InvalidArgumentException("ID de projet invalide");
        }
        $this->project_id = $project_id;
    }

    public function setDescription(string $description): void
    {
        $description = trim($description);
        if ($description === '') {
            throw new InvalidArgumentException("La description ne peut pas être vide");
        }
        $this->description = $description;
    }

    public function setStatus(string $status): void
    {
        $status = strtolower($status);
        if (!in_array($status, ['en_cours', 'terminee'])) {
            throw new InvalidArgumentException("Statut invalide");
        }
        $this->status = $status;
    }

    public function setCreatedAt(?string $created_at): void
    {
        $this->created_at = $created_at;
    }

    public static function ajouterActivite(int $project_id, string $description): self
    {
        if (!Project::exists($project_id)) {
            throw new InvalidArgumentException("Le projet spécifié n'existe pas");
        }

        $activite = new self($project_id, $description);
        $activite->setCreatedAt(date('Y-m-d H:i:s'));

        if ($activite->save()) {
            self::enregistrerHistorique(
                $project_id,
                $activite->id,
                'creation',
                "Activité créée: {$description}"
            );
            return $activite;
        }

        throw new RuntimeException("Erreur lors de la création de l'activité");
    }

    public function modifierActivite(array $modifications): bool
    {
        if (!$this->id) {
            throw new RuntimeException("Activité non enregistrée");
        }

        $changes = [];

        foreach ($modifications as $champ => $valeur) {
            $setter = 'set' . ucfirst($champ);
            if (method_exists($this, $setter)) {
                $old = $this->$champ ?? null;
                $this->$setter($valeur);
                if ((string)$old !== (string)$valeur) {
                    $changes[] = [$champ, $old, $valeur];
                }
            }
        }

        if ($this->save() && $changes) {
            foreach ($changes as [$c, $o, $n]) {
                self::enregistrerHistorique(
                    $this->project_id,
                    $this->id,
                    'modification',
                    "$c modifié: '$o' → '$n'"
                );
            }
            return true;
        }

        return false;
    }

    public function supprimerActivite(): bool
    {
        if (!$this->id) {
            return false;
        }

        self::enregistrerHistorique(
            $this->project_id,
            $this->id,
            'suppression',
            "Activité supprimée: {$this->description}"
        );

        return $this->delete();
    }

    public static function consulterHistoriqueProjet(int $project_id): array
    {
        if (!Project::exists($project_id)) {
            throw new InvalidArgumentException("Le projet spécifié n'existe pas");
        }

        try {
            $stmt = self::$conn->prepare(
                "SELECT * FROM historique_projets 
                 WHERE projet_id = :project_id 
                 ORDER BY date_action DESC"
            );
            $stmt->execute([':project_id' => $project_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            self::creerTableHistorique();
            return [];
        }
    }

    public function marquerTerminee(): bool
    {
        return $this->modifierActivite(['status' => 'terminee']);
    }

    public function marquerEnCours(): bool
    {
        return $this->modifierActivite(['status' => 'en_cours']);
    }

    public static function getByProject(int $project_id): array
    {
        $stmt = self::$conn->prepare(
            "SELECT * FROM activites 
             WHERE project_id = :project_id 
             ORDER BY created_at DESC"
        );
        $stmt->execute([':project_id' => $project_id]);

        return array_map(fn($r) => self::hydrate($r), $stmt->fetchAll());
    }

    public static function getByStatus(int $project_id, string $status): array
    {
        $stmt = self::$conn->prepare(
            "SELECT * FROM activites 
             WHERE project_id = :project_id AND status = :status 
             ORDER BY created_at DESC"
        );
        $stmt->execute([
            ':project_id' => $project_id,
            ':status' => $status
        ]);

        return array_map(fn($r) => self::hydrate($r), $stmt->fetchAll());
    }

    public static function countByProject(int $project_id): int
    {
        $stmt = self::$conn->prepare(
            "SELECT COUNT(*) FROM activites WHERE project_id = :project_id"
        );
        $stmt->execute([':project_id' => $project_id]);
        return (int) $stmt->fetchColumn();
    }

    public static function countByStatus(int $project_id, string $status): int
    {
        $stmt = self::$conn->prepare(
            "SELECT COUNT(*) FROM activites 
             WHERE project_id = :project_id AND status = :status"
        );
        $stmt->execute([
            ':project_id' => $project_id,
            ':status' => $status
        ]);
        return (int) $stmt->fetchColumn();
    }

    private static function enregistrerHistorique(
        int $project_id,
        int $activite_id,
        string $type_action,
        string $description
    ): bool {
        self::creerTableHistoriqueSiNecessaire();

        try {
            $stmt = self::$conn->prepare(
                "INSERT INTO historique_projets
                 (projet_id, activite_id, type_action, description, date_action)
                 VALUES (:p, :a, :t, :d, :dt)"
            );
            return $stmt->execute([
                ':p' => $project_id,
                ':a' => $activite_id,
                ':t' => $type_action,
                ':d' => $description,
                ':dt' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    private static function creerTableHistoriqueSiNecessaire(): void
    {
        try {
            $stmt = self::$conn->query("SHOW TABLES LIKE 'historique_projets'");
            if ($stmt->rowCount() === 0) {
                self::creerTableHistorique();
            }
        } catch (Exception $e) {
            self::creerTableHistorique();
        }
    }

    private static function creerTableHistorique(): void
    {
        self::$conn->exec(
            "CREATE TABLE IF NOT EXISTS historique_projets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                projet_id INT NOT NULL,
                activite_id INT,
                type_action VARCHAR(50) NOT NULL,
                description TEXT NOT NULL,
                date_action DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public static function searchByKeyword(int $project_id, string $keyword): array
    {
        $stmt = self::$conn->prepare(
            "SELECT * FROM activites
             WHERE project_id = :project_id
             AND description LIKE :keyword
             ORDER BY created_at DESC"
        );
        $stmt->execute([
            ':project_id' => $project_id,
            ':keyword' => "%$keyword%"
        ]);

        return array_map(fn($r) => self::hydrate($r), $stmt->fetchAll());
    }

    public static function getRecentActivities(int $project_id, int $limit = 10): array
    {
        $stmt = self::$conn->prepare(
            "SELECT * FROM activites
             WHERE project_id = :project_id
             ORDER BY created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn($r) => self::hydrate($r), $stmt->fetchAll());
    }
}
