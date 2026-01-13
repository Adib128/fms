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
    
    $busId = $data['bus_id'] ?? 0;
    $indexKm = $data['index_km'] ?? 0;
    $ficheId = $data['fiche_id'] ?? 0;
    
    if (!$busId || !$ficheId) {
        throw new Exception('Missing required fields');
    }
    
    // Check if vehicle already exists in this fiche
    $checkStmt = $db->prepare("SELECT id FROM maintenance_records WHERE fiche_id = ? AND id_bus = ?");
    $checkStmt->execute([$ficheId, $busId]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        throw new Exception('Ce véhicule est déjà ajouté à cette fiche');
    }
    
    // Get fiche date to use for the record
    $ficheStmt = $db->prepare("SELECT date FROM fiche_entretien WHERE id_fiche = ?");
    $ficheStmt->execute([$ficheId]);
    $fiche = $ficheStmt->fetch();
    
    if (!$fiche) {
        throw new Exception('Fiche non trouvée');
    }
    
    // Add vehicle to fiche with date
    $stmt = $db->prepare("INSERT INTO maintenance_records (fiche_id, id_bus, index_km, date) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$ficheId, $busId, $indexKm, $fiche['date']]);
    
    if (!$result) {
        throw new Exception('Failed to add vehicle to fiche');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Véhicule ajouté avec succès',
        'record_id' => $db->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
