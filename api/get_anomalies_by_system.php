<?php
require_once '../config.php';

header('Content-Type: application/json');

$id_system = isset($_GET['id_system']) ? (int)$_GET['id_system'] : 0;

if ($id_system > 0) {
    try {
        $stmt = $db->prepare("SELECT id, designation FROM anomalie WHERE id_system = ? ORDER BY designation");
        $stmt->execute([$id_system]);
        $anomalies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($anomalies);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
