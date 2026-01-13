<?php
require_once 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS maintenance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        specialite VARCHAR(255) NULL,
        id_atelier INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_atelier) REFERENCES atelier(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    $db->exec($sql);
    echo "Table 'maintenance' created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
