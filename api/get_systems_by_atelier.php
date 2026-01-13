<?php
require_once '../config.php';

header('Content-Type: application/json');

$id_atelier = isset($_GET['id_atelier']) ? (int)$_GET['id_atelier'] : 0;

if ($id_atelier > 0) {
    try {
        $stmt = $db->prepare("SELECT id, designation FROM systeme WHERE id_atelier = ? ORDER BY designation");
        $stmt->execute([$id_atelier]);
        $systems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($systems);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
