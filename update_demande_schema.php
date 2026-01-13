<?php
require_once __DIR__ . '/config.php';

try {
    $db->beginTransaction();

    // Add columns to demande if they don't exist
    $columns = [
        'id_atelier' => 'INT NULL',
        'id_system' => 'INT NULL'
    ];

    foreach ($columns as $col => $def) {
        try {
            $db->query("SELECT $col FROM demande LIMIT 1");
        } catch (PDOException $e) {
            // Column doesn't exist, add it
            echo "Adding column $col to table demande...\n";
            $db->exec("ALTER TABLE demande ADD COLUMN $col $def");
            
            // Add FKs
            if ($col === 'id_atelier') {
                $db->exec("ALTER TABLE demande ADD CONSTRAINT fk_demande_atelier FOREIGN KEY (id_atelier) REFERENCES atelier(id) ON DELETE SET NULL");
            }
            if ($col === 'id_system') {
                $db->exec("ALTER TABLE demande ADD CONSTRAINT fk_demande_system FOREIGN KEY (id_system) REFERENCES systeme(id) ON DELETE SET NULL");
            }
        }
    }

    // Create demande_anomalie table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS demande_anomalie (
        id_demande INT NOT NULL,
        id_anomalie INT NOT NULL,
        PRIMARY KEY (id_demande, id_anomalie),
        FOREIGN KEY (id_demande) REFERENCES demande(id) ON DELETE CASCADE,
        FOREIGN KEY (id_anomalie) REFERENCES anomalie(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    $db->exec($sql);
    echo "Table 'demande_anomalie' created/verified successfully.\n";

    $db->commit();
    echo "Database schema updated successfully.\n";

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Error updating database: " . $e->getMessage() . "\n";
}
