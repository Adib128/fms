<?php
require_once 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS ordre_type_panne (
        id_ordre INT NOT NULL,
        id_type_panne INT NOT NULL,
        PRIMARY KEY (id_ordre, id_type_panne),
        FOREIGN KEY (id_ordre) REFERENCES ordre(id) ON DELETE CASCADE,
        FOREIGN KEY (id_type_panne) REFERENCES type_panne(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    $db->exec($sql);
    echo "Table 'ordre_type_panne' created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
