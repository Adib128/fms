<?php
require '../config.php';

header('Content-Type: application/json');

try {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    $recordId = (int)($data['record_id'] ?? 0);
    $busId = (int)($data['bus_id'] ?? 0);
    $indexKm = (int)($data['index_km'] ?? 0);
    
    if (!$recordId || !$busId) {
        throw new Exception('Missing required fields');
    }
    
    // Check if record exists
    $checkStmt = $db->prepare("SELECT id FROM maintenance_records WHERE id = ?");
    $checkStmt->execute([$recordId]);
    $existing = $checkStmt->fetch();
    
    if (!$existing) {
        throw new Exception('Enregistrement non trouvé');
    }
    
    // Update maintenance record
    $stmt = $db->prepare("UPDATE maintenance_records SET id_bus = :bus_id, index_km = :index_km WHERE id = :record_id");
    $result = $stmt->execute([
        ':bus_id' => $busId,
        ':index_km' => $indexKm,
        ':record_id' => $recordId
    ]);
    
    if (!$result) {
        throw new Exception('Failed to update record');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Véhicule modifié avec succès'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

