<div class="form-section">
    <h2>📝 Gérer les activités</h2>
    
    <h3>Ajouter une activité</h3>
    <form method="POST" action="">
        <input type="hidden" name="action_type" value="ajouter">
        
        <div>
            <label>ID du projet:</label>
            <input type="number" name="project_id" required>
        </div>
        
        <div>
            <label>Description:</label>
            <textarea name="description" rows="3" required placeholder="Description de l'activité..."></textarea>
        </div>
        
        <button type="submit">Ajouter l'activité</button>
    </form>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if ($_POST['action_type'] === 'ajouter') {
                $activite = Activite::ajouterActivite($_POST['project_id'], $_POST['description']);
                
                echo '<div class="result">';
                echo '✓ Activité créée avec succès !<br>';
                echo 'ID: ' . $activite->getId() . '<br>';
                echo 'Description: ' . $activite->getDescription() . '<br>';
                echo 'Statut: ' . $activite->getStatus();
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">✗ Erreur: ' . $e->getMessage() . '</div>';
        }
    }
    ?>
    
    <h3>Liste des activités récentes</h3>
    <?php
    try {
        // Récupérer les projets pour afficher leurs activités
        $projets = Project::all();
        
        foreach ($projets as $projet) {
            $activites = Activite::getRecentActivities($projet->getId(), 5);
            
            if (!empty($activites)) {
                echo '<h4>Projet: ' . $projet->getTitle() . ' (ID: ' . $projet->getId() . ')</h4>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Description</th><th>Statut</th><th>Créée le</th><th>Actions</th></tr>';
                
                foreach ($activites as $activite) {
                    echo '<tr>';
                    echo '<td>' . $activite->getId() . '</td>';
                    echo '<td>' . htmlspecialchars($activite->getDescription()) . '</td>';
                    echo '<td>' . $activite->getStatus() . '</td>';
                    echo '<td>' . $activite->getCreatedAt() . '</td>';
                    echo '<td>';
                    echo '<button onclick="modifierActivite(' . $activite->getId() . ')">✏️</button> ';
                    echo '<button onclick="supprimerActivite(' . $activite->getId() . ')">🗑️</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
    } catch (Exception $e) {
        echo '<div class="error">Erreur: ' . $e->getMessage() . '</div>';
    }
    ?>
    
    <a href="?action=menu">← Retour au menu</a>
</div>