<?php
require 'config.php';

try {
    echo "Fixing demande table schema and data...\n";
    
    // 1. Temporarily change column to VARCHAR to allow data manipulation
    echo "Changing etat column to VARCHAR...\n";
    $db->exec("ALTER TABLE demande MODIFY COLUMN etat VARCHAR(50)");
    
    // 2. Update existing data to match new enum values
    echo "Updating existing data...\n";
    $db->exec("UPDATE demande SET etat = 'En cours' WHERE etat IN ('en cours', 'Disponible', 'En réparation', 'Immobiliser') OR etat IS NULL");
    $db->exec("UPDATE demande SET etat = 'Valider' WHERE etat = 'valide'");
    $db->exec("UPDATE demande SET etat = 'Cloturer' WHERE etat = 'cloturer'");
    
    // 3. Modify the column to the new ENUM
    echo "Modifying etat column to new ENUM('En cours', 'Valider', 'Cloturer')...\n";
    $db->exec("ALTER TABLE demande MODIFY COLUMN etat ENUM('En cours', 'Valider', 'Cloturer') DEFAULT 'En cours'");
    
    echo "Demande table schema and data fixed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
