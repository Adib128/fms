<?php
require_once 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS ordre_anomalie (
        id_ordre INT NOT NULL,
        id_anomalie INT NOT NULL,
        PRIMARY KEY (id_ordre, id_anomalie),
        FOREIGN KEY (id_ordre) REFERENCES ordre(id) ON DELETE CASCADE,
        FOREIGN KEY (id_anomalie) REFERENCES anomalie(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    $db->exec($sql);
    echo "Table 'ordre_anomalie' created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
