<?php
require_once 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS demande (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero VARCHAR(50) NOT NULL UNIQUE,
        type ENUM('Maintenance préventive', 'Maintenance curative', 'Maintenance palliative') NOT NULL,
        id_atelier INT NOT NULL,
        id_vehicule INT NOT NULL,
        id_chauffeur INT NOT NULL,
        id_station INT NOT NULL,
        date DATE NOT NULL,
        etat ENUM('en cours', 'validée') DEFAULT 'en cours',
        priorite ENUM('Basse', 'Moyenne', 'Haute', 'Critique') NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_atelier) REFERENCES atelier(id),
        FOREIGN KEY (id_vehicule) REFERENCES bus(id_bus),
        FOREIGN KEY (id_chauffeur) REFERENCES chauffeur(id_chauffeur),
        FOREIGN KEY (id_station) REFERENCES station(id_station)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    $db->exec($sql);
    echo "Table 'demande' created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
