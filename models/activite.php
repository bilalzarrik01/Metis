<?php
require_once __DIR__ . '/../core/BaseModel.php';

class Activite extends BaseModel
{
    protected static string $table = 'activites';
    
    // Ajuster les champs ignorés selon la structure de la table
    protected static array $ignoredFields = ['id', 'created_at'];
    protected static array $immutableFields = ['id', 'created_at'];
    
    protected ?int $id = null;
    protected int $project_id;  // Note: 'project_id' pas 'projet_id'
    protected string $description;
    protected string $status = 'en_cours'; // Seulement 'en_cours' ou 'terminee'
    protected ?string $created_at = null;
    
    public function __construct(int $project_id, string $description)
    {
        $this->setProjectId($project_id);
        $this->setDescription($description);
    }
    
    // Getters
    public function getId(): ?int { return $this->id; }
    public function getProjectId(): int { return $this->project_id; }
    public function getDescription(): string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    
    // Setters avec validation
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
        if (empty($description)) {
            throw new InvalidArgumentException("La description ne peut pas être vide");
        }
        $this->description = $description;
    }
    
    public function setStatus(string $status): void
    {
        $status = strtolower($status);
        if (!in_array($status, ['en_cours', 'terminee'])) {
            throw new InvalidArgumentException("Statut invalide. Doit être 'en_cours' ou 'terminee'");
        }
        $this->status = $status;
    }
    
    public function setCreatedAt(?string $created_at): void
    {
        $this->created_at = $created_at;
    }
    
    // Méthodes métier
    
    /**
     * Ajoute une activité à un projet
     */
    public static function ajouterActivite(int $project_id, string $description): self
    {
        // Vérifier si le projet existe
        if (!Project::exists($project_id)) {
            throw new InvalidArgumentException("Le projet spécifié n'existe pas");
        }
        
        $activite = new self($project_id, $description);
        $activite->setCreatedAt(date('Y-m-d H:i:s'));
        
        if ($activite->save()) {
            // Enregistrer dans l'historique
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
    
    /**
     * Modifie une activité existante
     */
    public function modifierActivite(array $modifications): bool
    {
        if (!$this->id) {
            throw new RuntimeException("Activité non enregistrée");
        }
        
        $historiqueModifications = [];
        
        // Enregistrer les modifications avant de les appliquer
        foreach ($modifications as $champ => $valeur) {
            $setter = 'set' . ucfirst($champ);
            
            if (method_exists($this, $setter)) {
                $ancienneValeur = $this->$champ ?? null;
                
                // Appliquer la modification
                $this->$setter($valeur);
                
                // Enregistrer dans l'historique si changement
                if ((string)$ancienneValeur !== (string)$valeur) {
                    $historiqueModifications[] = [
                        'champ' => $champ,
                        'ancien' => $ancienneValeur,
                        'nouveau' => $valeur
                    ];
                }
            }
        }
        
        if ($this->save() && !empty($historiqueModifications)) {
            // Enregistrer les modifications dans l'historique
            foreach ($historiqueModifications as $modif) {
                self::enregistrerHistorique(
                    $this->project_id,
                    $this->id,
                    'modification',
                    "{$modif['champ']} modifié: '{$modif['ancien']}' → '{$modif['nouveau']}'"
                );
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Supprime une activité
     */
    public function supprimerActivite(): bool
    {
        if (!$this->id) {
            return false;
        }
        
        // Vérification métier : peut-on supprimer cette activité ?
        if ($this->status === 'terminee') {
            // Logique métier spécifique si nécessaire
            // Par exemple : vérifier si l'activité terminée peut être supprimée
        }
        
        // Enregistrer dans l'historique avant suppression
        self::enregistrerHistorique(
            $this->project_id,
            $this->id,
            'suppression',
            "Activité supprimée: {$this->description}"
        );
        
        return $this->delete();
    }
    
    /**
     * Consulte l'historique d'un projet
     */
    public static function consulterHistoriqueProjet(int $project_id): array
    {
        if (!Project::exists($project_id)) {
            throw new InvalidArgumentException("Le projet spécifié n'existe pas");
        }
        
        // Vérifier si la table d'historique existe
        try {
            $stmt = self::$conn->prepare("
                SELECT * FROM historique_projets 
                WHERE projet_id = :project_id 
                ORDER BY date_action DESC
            ");
            
            $stmt->execute([':project_id' => $project_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Si la table n'existe pas, on peut retourner un historique basique
            // ou créer la table dynamiquement
            self::creerTableHistorique();
            return [];
        }
    }
    
    /**
     * Marque une activité comme terminée
     */
    public function marquerTerminee(): bool
    {
        return $this->modifierActivite(['status' => 'terminee']);
    }
    
    /**
     * Marque une activité comme en cours
     */
    public function marquerEnCours(): bool
    {
        return $this->modifierActivite(['status' => 'en_cours']);
    }
    
    /**
     * Récupère toutes les activités d'un projet
     */
    public static function getByProject(int $project_id): array
    {
        $stmt = self::$conn->prepare("
            SELECT * FROM activites 
            WHERE project_id = :project_id 
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([':project_id' => $project_id]);
        
        return array_map(
            fn($row) => self::hydrate($row),
            $stmt->fetchAll()
        );
    }
    
    /**
     * Récupère les activités par statut
     */
    public static function getByStatus(int $project_id, string $status): array
    {
        $stmt = self::$conn->prepare("
            SELECT * FROM activites 
            WHERE project_id = :project_id AND status = :status 
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([
            ':project_id' => $project_id,
            ':status' => $status
        ]);
        
        return array_map(
            fn($row) => self::hydrate($row),
            $stmt->fetchAll()
        );
    }
    
    /**
     * Compte le nombre d'activités par projet
     */
    public static function countByProject(int $project_id): int
    {
        $stmt = self::$conn->prepare("
            SELECT COUNT(*) FROM activites 
            WHERE project_id = :project_id
        ");
        
        $stmt->execute([':project_id' => $project_id]);
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Compte les activités par statut dans un projet
     */
    public static function countByStatus(int $project_id, string $status): int
    {
        $stmt = self::$conn->prepare("
            SELECT COUNT(*) FROM activites 
            WHERE project_id = :project_id AND status = :status
        ");
        
        $stmt->execute([
            ':project_id' => $project_id,
            ':status' => $status
        ]);
        
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Enregistre une action dans l'historique
     * Méthode privée car utilisée en interne
     */
    private static function enregistrerHistorique(
        int $project_id,
        int $activite_id,
        string $type_action,
        string $description
    ): bool {
        // Vérifier si la table d'historique existe
        self::creerTableHistoriqueSiNecessaire();
        
        try {
            $stmt = self::$conn->prepare("
                INSERT INTO historique_projets 
                (projet_id, activite_id, type_action, description, date_action) 
                VALUES (:project_id, :activite_id, :type_action, :description, :date_action)
            ");
            
            return $stmt->execute([
                ':project_id' => $project_id,
                ':activite_id' => $activite_id,
                ':type_action' => $type_action,
                ':description' => $description,
                ':date_action' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Log l'erreur mais ne pas bloquer l'opération principale
            error_log("Erreur enregistrement historique: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crée la table d'historique si elle n'existe pas
     */
    private static function creerTableHistoriqueSiNecessaire(): void
    {
        try {
            // Vérifier si la table existe
            $stmt = self::$conn->query("SHOW TABLES LIKE 'historique_projets'");
            if ($stmt->rowCount() === 0) {
                self::creerTableHistorique();
            }
        } catch (Exception $e) {
            // En cas d'erreur, on tente de créer la table
            self::creerTableHistorique();
        }
    }
    
    /**
     * Crée la table d'historique
     */
    private static function creerTableHistorique(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS historique_projets (
                id INT PRIMARY KEY AUTO_INCREMENT,
                projet_id INT NOT NULL,
                activite_id INT,
                type_action VARCHAR(50) NOT NULL,
                description TEXT NOT NULL,
                date_action DATETIME NOT NULL,
                INDEX idx_historique_projet (projet_id),
                INDEX idx_historique_date (date_action DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        self::$conn->exec($sql);
    }
    
    /**
     * Recherche des activités par mot-clé dans la description
     */
    public static function searchByKeyword(int $project_id, string $keyword): array
    {
        $stmt = self::$conn->prepare("
            SELECT * FROM activites 
            WHERE project_id = :project_id 
            AND description LIKE :keyword
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([
            ':project_id' => $project_id,
            ':keyword' => '%' . $keyword . '%'
        ]);
        
        return array_map(
            fn($row) => self::hydrate($row),
            $stmt->fetchAll()
        );
    }
    
    /**
     * Récupère les activités récentes
     */
    public static function getRecentActivities(int $project_id, int $limit = 10): array
    {
        $stmt = self::$conn->prepare("
            SELECT * FROM activites 
            WHERE project_id = :project_id 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        
        $stmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_map(
            fn($row) => self::hydrate($row),
            $stmt->fetchAll()
        );
    }
}