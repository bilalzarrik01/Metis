<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/ProjectCourt.php';
require_once __DIR__ . '/../models/ProjectLong.php';

$db = new DB();
$pdo = $db->connect();
BaseModel::setConnection($pdo);

// Create a short project
$projectCourt = new ProjectCourt(1, "Mini Website");
$projectCourt->save();
echo "ProjectCourt ID: " . $projectCourt->getId() . "\n";

// Create a long project
$projectLong = new ProjectLong(1, "Enterprise App");
$projectLong->setStartDate("2025-01-01");
$projectLong->setEndDate("2025-12-31");
$projectLong->save();
echo "ProjectLong ID: " . $projectLong->getId() . "\n";

// Access methods
echo $projectCourt->getShortDescription() . "\n";
echo $projectLong->getDuration() . "\n";



