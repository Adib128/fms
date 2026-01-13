<?php
require_once __DIR__ . '/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS demande_passation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero VARCHAR(20) NOT NULL UNIQUE,
        date DATETIME NOT NULL,
        id_vehicule INT NOT NULL,
        etat ENUM('En cours', 'Accepter', 'Rejeter', 'Cloturer') DEFAULT 'En cours',
        id_chauffeur_cedant INT NOT NULL,
        id_chauffeur_repreneur INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_vehicule) REFERENCES bus(id_bus),
        FOREIGN KEY (id_chauffeur_cedant) REFERENCES chauffeur(id_chauffeur),
        FOREIGN KEY (id_chauffeur_repreneur) REFERENCES chauffeur(id_chauffeur)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql);
    echo "Table 'demande_passation' created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
