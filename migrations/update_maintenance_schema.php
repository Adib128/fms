<?php
require_once __DIR__ . '/../config.php';

try {
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM maintenance LIKE 'matricule'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Add column
        $db->exec("ALTER TABLE maintenance ADD COLUMN matricule VARCHAR(50) NULL AFTER nom");
        echo "Column 'matricule' added successfully to 'maintenance' table.\n";
    } else {
        echo "Column 'matricule' already exists in 'maintenance' table.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
