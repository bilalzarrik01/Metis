<?php
// check_php.php
echo "=== Vérification PHP ===\n\n";
echo "PHP Version: " . phpversion() . "\n\n";

echo "Extensions chargées:\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    if (strpos($ext, 'pdo') !== false || strpos($ext, 'mysql') !== false) {
        echo "- $ext\n";
    }
}

echo "\nDrivers PDO disponibles:\n";
if (extension_loaded('pdo')) {
    try {
        $drivers = PDO::getAvailableDrivers();
        foreach ($drivers as $driver) {
            echo "- $driver\n";
        }
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
    }
} else {
    echo "PDO n'est pas activé!\n";
}