<?php
require_once 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS reclamation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        id_vehicule INT NOT NULL,
        id_chauffeur INT NOT NULL,
        id_station INT NOT NULL,
        description TEXT NOT NULL,
        etat VARCHAR(20) DEFAULT 'en cours',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_vehicule) REFERENCES bus(id_bus),
        FOREIGN KEY (id_chauffeur) REFERENCES chauffeur(id_chauffeur),
        FOREIGN KEY (id_station) REFERENCES station(id_station)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    $db->exec($sql);
    echo "Table 'reclamation' created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
