<?php
require 'config.php';

try {
    echo "Starting ordre status migration...\n";

    // 1. Change column to VARCHAR to allow temporary values
    echo "Changing column to VARCHAR...\n";
    $db->exec("ALTER TABLE ordre MODIFY COLUMN etat VARCHAR(50)");

    // 2. Update existing values to capitalized format
    echo "Updating existing values...\n";
    $db->exec("UPDATE ordre SET etat = 'Ouvert' WHERE etat = 'ouvert'");
    $db->exec("UPDATE ordre SET etat = 'Valider' WHERE etat = 'valider'");
    $db->exec("UPDATE ordre SET etat = 'Cloturer' WHERE etat = 'cloturer'");

    // 3. Change column back to ENUM with new values
    echo "Changing column back to ENUM...\n";
    $db->exec("ALTER TABLE ordre MODIFY COLUMN etat ENUM('Ouvert', 'Valider', 'Cloturer') DEFAULT 'Ouvert'");

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
