<?php
require_once __DIR__ . '/config.php';

try {
    // Update demande table
    echo "Updating demande table statuses...\n";
    
    // 1. Change to VARCHAR to allow any value
    $db->exec("ALTER TABLE demande MODIFY COLUMN etat VARCHAR(50)");
    
    // 2. Map existing statuses
    $db->exec("UPDATE demande SET etat = 'valide' WHERE etat = 'validée'");
    
    // 3. Change back to new ENUM
    $db->exec("ALTER TABLE demande MODIFY COLUMN etat ENUM('en cours', 'valide', 'cloturer') DEFAULT 'en cours'");
    
    echo "Demande table updated successfully.\n";

    // Update ordre table
    echo "Updating ordre table statuses...\n";
    
    // 1. Change to VARCHAR
    $db->exec("ALTER TABLE ordre MODIFY COLUMN etat VARCHAR(50)");
    
    // 2. Map existing statuses
    $db->exec("UPDATE ordre SET etat = 'ouvert' WHERE etat = 'Ouvert'");
    $db->exec("UPDATE ordre SET etat = 'valider' WHERE etat = 'Validé'");
    $db->exec("UPDATE ordre SET etat = 'cloturer' WHERE etat = 'Fermé'");
    
    // 3. Change back to new ENUM
    $db->exec("ALTER TABLE ordre MODIFY COLUMN etat ENUM('ouvert', 'valider', 'cloturer') DEFAULT 'ouvert'");
    
    echo "Ordre table updated successfully.\n";

} catch (PDOException $e) {
    die("Error during migration: " . $e->getMessage() . "\n");
}
