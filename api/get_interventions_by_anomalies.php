<?php
require_once '../config.php';

header('Content-Type: application/json');

// Get raw POST data
$input = json_decode(file_get_contents('php://input'), true);
$anomalie_ids = $input['anomalie_ids'] ?? [];

if (!empty($anomalie_ids)) {
    try {
        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($anomalie_ids), '?'));
        
        $stmt = $db->prepare("
            SELECT id, libelle 
            FROM intervention 
            WHERE id_anomalie IN ($placeholders) 
            ORDER BY libelle ASC
        ");
        
        $stmt->execute($anomalie_ids);
        $interventions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($interventions);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
