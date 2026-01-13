<?php
require_once __DIR__ . '/config.php';

try {
    $db->beginTransaction();

    // Add columns if they don't exist
    $columns = [
        'id_technicien' => 'INT NULL',
        'id_article' => 'INT NULL',
        'description' => 'TEXT NULL',
        'realised_at' => 'DATETIME NULL',
        'status' => "VARCHAR(20) DEFAULT 'en cours'"
    ];

    foreach ($columns as $col => $def) {
        try {
            $db->query("SELECT $col FROM ordre_intervention LIMIT 1");
        } catch (PDOException $e) {
            // Column doesn't exist, add it
            echo "Adding column $col...\n";
            $db->exec("ALTER TABLE ordre_intervention ADD COLUMN $col $def");
            
            // Add FKs if needed
            if ($col === 'id_technicien') {
                $db->exec("ALTER TABLE ordre_intervention ADD CONSTRAINT fk_oi_technicien FOREIGN KEY (id_technicien) REFERENCES maintenance(id) ON DELETE SET NULL");
            }
            if ($col === 'id_article') {
                $db->exec("ALTER TABLE ordre_intervention ADD CONSTRAINT fk_oi_article FOREIGN KEY (id_article) REFERENCES article(id) ON DELETE SET NULL");
            }
        }
    }

    $db->commit();
    echo "Database schema updated successfully.\n";

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Error updating database: " . $e->getMessage() . "\n";
}
