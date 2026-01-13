<?php
require_once '../config.php';

header('Content-Type: application/json');

$id_atelier = isset($_GET['id_atelier']) ? (int)$_GET['id_atelier'] : 0;

if ($id_atelier > 0) {
    try {
        $stmt = $db->prepare("
            SELECT an.id, an.designation, s.designation as systeme_nom 
            FROM anomalie an 
            JOIN systeme s ON an.id_system = s.id 
            WHERE s.id_atelier = ? 
            ORDER BY s.designation, an.designation
        ");
        $stmt->execute([$id_atelier]);
        $anomalies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($anomalies);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
