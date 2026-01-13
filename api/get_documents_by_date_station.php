<?php
require '../config.php';

header('Content-Type: application/json');

try {
    $date = $_GET['date'] ?? '';
    $stationId = $_GET['station_id'] ?? '';

    if (empty($date) || empty($stationId)) {
        echo json_encode([]);
        exit;
    }

    $stmt = $db->prepare('
        SELECT id_doc_carburant, num_doc_carburant, date, type 
        FROM doc_carburant 
        WHERE date = ? AND id_station = ?
        ORDER BY num_doc_carburant ASC
    ');
    
    $stmt->execute([$date, $stationId]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($documents);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
