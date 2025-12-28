<?php


require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/BaseModel.php';

require_once __DIR__ . '/../models/Member.php';
require_once __DIR__ . '/../models/ProjectCourt.php';
require_once __DIR__ . '/../models/ProjectLong.php';
require_once __DIR__ . '/../models/Activite.php';



$pdo = DB::connect();
BaseModel::setConnection($pdo);


//    CLI HELPER

function ask(string $label): string {
    echo $label;
    return trim(fgets(STDIN));
}



while (true) {

    echo "\n========= METIS CLI =========\n";
    echo "1. Members\n";
    echo "2. Projects\n";
    echo "3. Activities\n";
    echo "0. Exit\n";

    $choice = ask("Choice: ");

 
    //    MEMBERS
 
    if ($choice === '1') {

        echo "\n--- MEMBERS ---\n";
        echo "1. Create member\n";
        echo "2. List members\n";
        echo "3. Update member\n";
        echo "4. Delete member\n";

        $c = ask("Choice: ");

        // CREATE
        if ($c === '1') {
            try {
                $name  = ask("Full name: ");
                $email = ask("Email: ");

                $member = new Member($name, $email);
                $member->save();

                echo "Member created (ID {$member->getId()})\n";
            } catch (Exception $e) {
                echo "Error: {$e->getMessage()}\n";
            }
        }

        // LIST
        if ($c === '2') {
            $members = Member::all();
            foreach ($members as $m) {
                echo "[{$m->getId()}] {$m->getFullName()} - {$m->getEmail()}\n";
            }
        }

        // UPDATE
        if ($c === '3') {
            $id = (int) ask("Member ID: ");
            $member = Member::findById($id);

            if (!$member) {
                echo "Member not found\n";
                continue;
            }

            $name  = ask("New name (enter to skip): ");
            $email = ask("New email (enter to skip): ");

            if ($name !== '')  $member->setFullName($name);
            if ($email !== '') $member->setEmail($email);

            $member->save();
            echo "Member updated\n";
        }

        // DELETE
        if ($c === '4') {
            $id = (int) ask("Member ID: ");
            $member = Member::findById($id);

            if (!$member) {
                echo "Member not found\n";
                continue;
            }

            // c'ant delete a mbr with a prjct

            foreach (array_merge(ProjectCourt::all(), ProjectLong::all()) as $p) {
                if ($p->getMemberId() === $member->getId()) {
                    echo "Cannot delete: member has projects\n";
                    continue 2;
                }
            }

            $member->delete();
            echo "Member deleted\n";
        }
    }

    //    PROJECTS

    if ($choice === '2') {

        echo "\n--- PROJECTS ---\n";
        echo "1. Create project\n";
        echo "2. List projects\n";
        echo "3. Delete project\n";

        $c = ask("Choice: ");

        // CREATE
        if ($c === '1') {
            try {
                $memberId = (int) ask("Member ID: ");
                if (!Member::exists($memberId)) {
                    echo "Member not found\n";
                    continue;
                }

                $title = ask("Project title: ");
                $type  = ask("Type (court / long): ");

                $project = ($type === 'court')
                    ? new ProjectCourt($memberId, $title)
                    : new ProjectLong($memberId, $title);

                $project->save();
                echo "Project created (ID {$project->getId()})\n";
            } catch (Exception $e) {
                echo "Error: {$e->getMessage()}\n";
            }
        }

        // LIST
        if ($c === '2') {
            foreach (array_merge(ProjectCourt::all(), ProjectLong::all()) as $p) {
                echo "[{$p->getId()}] {$p->getTitle()} ({$p->getType()}) | Member {$p->getMemberId()}\n";
            }
        }

        // DELETE
        if ($c === '3') {
            $id = (int) ask("Project ID: ");

            $project = ProjectCourt::findById($id) ?? ProjectLong::findById($id);

            if (!$project) {
                echo "Project not found\n";
                continue;
            }

            // Rule: no active activities
            if (Activite::countByStatus($project->getId(), 'en_cours') > 0) {
                echo "Cannot delete: active activities exist\n";
                continue;
            }

            $project->delete();
            echo "Project deleted\n";
        }
    }

    //    ACTIVITIES
  
    if ($choice === '3') {

        echo "\n--- ACTIVITIES ---\n";
        echo "1. Add activity\n";
        echo "2. List activities\n";
        echo "3. Update activity\n";
        echo "4. Delete activity\n";

        $c = ask("Choice: ");

        // ADD
        if ($c === '1') {
            try {
                $projectId = (int) ask("Project ID: ");
                $desc = ask("Description: ");

                $act = Activite::ajouterActivite($projectId, $desc);
                echo "Activity created (ID {$act->getId()})\n";
            } catch (Exception $e) {
                echo "Error: {$e->getMessage()}\n";
            }
        }

        // LIST
        if ($c === '2') {
            $projectId = (int) ask("Project ID: ");
            $acts = Activite::getByProject($projectId);

            foreach ($acts as $a) {
                echo "[{$a->getId()}] {$a->getDescription()} ({$a->getStatus()})\n";
            }
        }

        // UPDATE
        if ($c === '3') {
            $id = (int) ask("Activity ID: ");
            $act = Activite::findById($id);

            if (!$act) {
                echo "Activity not found\n";
                continue;
            }

            $desc = ask("New description (enter to skip): ");
            $status = ask("Status (en_cours / terminee): ");

            $data = [];
            if ($desc !== '')   $data['description'] = $desc;
            if ($status !== '') $data['status'] = $status;

            $act->modifierActivite($data);
            echo "Activity updated\n";
        }

        // DELETE
        if ($c === '4') {
            $id = (int) ask("Activity ID: ");
            $act = Activite::findById($id);

            if (!$act) {
                echo "Activity not found\n";
                continue;
            }

            $act->supprimerActivite();
            echo "Activity deleted\n";
        }
    }

    if ($choice === '0') {
        exit("\nBye 👋\n");
    }
}
