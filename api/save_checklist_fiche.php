<?php
require __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Debug: Log the raw input
error_log('=== API DEBUG ===');
error_log('Raw input: ' . file_get_contents('php://input'));

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Debug: Log decoded data
    error_log('Decoded data: ' . print_r($data, true));
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    $busId = (int)($data['bus_id'] ?? 0);
    $ficheId = (int)($data['fiche_id'] ?? 0);
    $checklistItems = $data['checklist_items'] ?? [];
    
    // Debug: Log extracted values
    error_log('Extracted bus_id: ' . $busId);
    error_log('Extracted fiche_id: ' . $ficheId);
    error_log('Extracted checklist_items: ' . print_r($checklistItems, true));
    
    if ($busId === 0 || $ficheId === 0) {
        error_log('ERROR: Missing required parameters - bus_id=' . $busId . ', fiche_id=' . $ficheId);
        throw new Exception('Missing required parameters');
    }
    
    // Get the maintenance record ID for this bus and fiche
    $recordQuery = $db->prepare("
        SELECT id FROM maintenance_records 
        WHERE id_bus = ? AND fiche_id = ?
        LIMIT 1
    ");
    $recordQuery->execute([$busId, $ficheId]);
    $record = $recordQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        error_log('ERROR: No maintenance record found for bus_id=' . $busId . ', fiche_id=' . $ficheId);
        throw new Exception('No maintenance record found for this vehicle and fiche');
    }
    
    $recordId = $record['id'];
    error_log('Found maintenance record ID: ' . $recordId);
    
    // Start transaction
    $db->beginTransaction();
    
    // Delete existing checklist data for this bus/record combination
    $deleteStmt = $db->prepare("
        DELETE FROM maintenance_checklist_status 
        WHERE id_bus = ? AND id_fiche = ?
    ");
    $deleteStmt->execute([$busId, $recordId]);
    
    // Insert new checklist data
    if (!empty($checklistItems)) {
        $insertStmt = $db->prepare("
            INSERT INTO maintenance_checklist_status 
            (id_bus, id_fiche, checklist_item_id, is_checked, checked_at) 
            VALUES (?, ?, ?, 1, NOW())
        ");
        
        foreach ($checklistItems as $itemId) {
            $itemId = (int)$itemId;
            if ($itemId > 0) {
                error_log("Inserting: bus_id=$busId, record_id=$recordId, item_id=$itemId");
                $insertStmt->execute([$busId, $recordId, $itemId]);
            }
        }
    }
    
    // Commit transaction
    $db->commit();
    
    error_log('SUCCESS: Checklist saved successfully');
    echo json_encode([
        'success' => true,
        'message' => 'Checklist enregistrée avec succès'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if started
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log('ERROR: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('=== END API DEBUG ===');
}
