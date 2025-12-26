<?php


require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Member.php';
require_once __DIR__ . '/../models/ProjectCourt.php';
require_once __DIR__ . '/../models/ProjectLong.php';
require_once __DIR__ . '/../models/Activite.php';


use \PDO;

// Set DB connection for all models
$pdo = DB::connect();
BaseModel::setConnection($pdo);

function prompt(string $message): string {
    echo $message;
    return trim(fgets(STDIN));
}

function menu(): void {
    echo "\n=== Metis CLI ===\n";
    echo "1. List Members\n";
    echo "2. Add Member\n";
    echo "3. List Projects\n";
    echo "4. Add Project\n";
    echo "5. List Activities\n";
    echo "6. Add Activity\n";
    echo "7. Exit\n";
    echo "Choose an option: ";
}

while (true) {
    menu();
    $choice = trim(fgets(STDIN));

    switch ($choice) {
        case '1':
            $members = Member::all();
            echo "\n--- Members ---\n";
            foreach ($members as $m) {
                echo "[{$m->getId()}] {$m->getName()} ({$m->getEmail()})\n";
            }
            break;

        case '2':
            $name = prompt("Enter name: ");
            $email = prompt("Enter email: ");
            try {
                $member = new Member($name, $email);
                $member->save();
                echo "Member created with ID {$member->getId()}\n";
            } catch (Exception $e) {
                echo "Error: {$e->getMessage()}\n";
            }
            break;

        case '3':
            $projects = array_merge(ProjectCourt::all(), ProjectLong::all());
            echo "\n--- Projects ---\n";
            foreach ($projects as $p) {
                echo "[{$p->getId()}] {$p->getTitle()} ({$p->getType()})\n";
            }
            break;

        case '4':
            $memberId = (int) prompt("Enter member ID: ");
            $title = prompt("Enter project title: ");
            $type = strtolower(prompt("Enter project type (court/long): "));
            try {
                if ($type === 'court') {
                    $proj = new ProjectCourt($memberId, $title);
                } else {
                    $proj = new ProjectLong($memberId, $title);
                }
                $proj->save();
                echo "Project created with ID {$proj->getId()}\n";
            } catch (Exception $e) {
                echo "Error: {$e->getMessage()}\n";
            }
            break;

        case '5':
            $projectId = (int) prompt("Enter project ID: ");
            $activities = Activite::getByProject($projectId);
            echo "\n--- Activities for Project {$projectId} ---\n";
            foreach ($activities as $a) {
                echo "[{$a->getId()}] {$a->getDescription()} ({$a->getStatus()})\n";
            }
            break;

        case '6':
            $projectId = (int) prompt("Enter project ID: ");
            $desc = prompt("Enter activity description: ");
            try {
                $act = Activite::ajouterActivite($projectId, $desc);
                echo "Activity created with ID {$act->getId()}\n";
            } catch (Exception $e) {
                echo "Error: {$e->getMessage()}\n";
            }
            break;

        case '7':
            echo "Goodbye!\n";
            exit;

        default:
            echo "Invalid choice.\n";
    }
}
