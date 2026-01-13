<?php
require_once 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS intervention (
        id INT AUTO_INCREMENT PRIMARY KEY,
        libelle VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    $db->exec($sql);
    echo "Table 'intervention' created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
