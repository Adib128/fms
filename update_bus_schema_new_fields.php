<?php
require_once "config.php";

try {
    $db->exec("ALTER TABLE bus ADD COLUMN contenance_reservoir INT NULL AFTER conso_huile");
    $db->exec("ALTER TABLE bus ADD COLUMN date_mise_circulation DATE NULL AFTER contenance_reservoir");
    echo "Database schema updated successfully.";
} catch (PDOException $e) {
    echo "Error updating database schema: " . $e->getMessage();
}
?>
