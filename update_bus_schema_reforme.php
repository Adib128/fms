<?php
require 'config.php';

try {
    echo "Updating bus table schema...\n";
    
    // Update bus.etat enum
    $sql = "ALTER TABLE bus MODIFY COLUMN etat ENUM('Disponible', 'En réparation', 'Immobiliser', 'Réformé') DEFAULT 'Disponible'";
    $db->exec($sql);
    
    echo "Successfully updated bus.etat column.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
