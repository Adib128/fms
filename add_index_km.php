<?php
require_once __DIR__ . '/config.php';

try {
    $db->exec("ALTER TABLE demande ADD COLUMN index_km INT NULL DEFAULT NULL AFTER id_station");
    echo "Column index_km added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
