<?php
require_once 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS article (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        designiation VARCHAR(255) NOT NULL,
        famille VARCHAR(100) NOT NULL,
        etat ENUM('Neuf','Récupérer','Rénover') NOT NULL DEFAULT 'Neuf',
        etat_pourcentage INT NOT NULL DEFAULT 100,
        valeur DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        id_vehicule INT NULL,
        CONSTRAINT fk_article_vehicule FOREIGN KEY (id_vehicule) REFERENCES bus(id_bus) ON DELETE SET NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    $db->exec($sql);
    echo "Table 'article' created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
