<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$id_bus = isset($_GET['id_bus']) ? (int)$_GET['id_bus'] : 0;

if ($id_bus <= 0) {
    echo json_encode(['error' => 'ID véhicule invalide']);
    exit;
}

try {
    // 1. Get bus frequency and info
    $stmtBus = $db->prepare("SELECT freq_vidange_moteur, matricule_interne FROM bus WHERE id_bus = ?");
    $stmtBus->execute([$id_bus]);
    $bus = $stmtBus->fetch(PDO::FETCH_ASSOC);

    if (!$bus) {
        echo json_encode(['error' => 'Véhicule non trouvé']);
        exit;
    }

    $freq_vidange = (int)$bus['freq_vidange_moteur'];

    // 2. Find the last vidange operation for 'Moteur'
    $lastVidangeStmt = $db->prepare("
        SELECT mr.date
        FROM maintenance_operations mo
        INNER JOIN maintenance_records mr ON mo.record_id = mr.id
        LEFT JOIN compartiments c ON mo.compartiment_id = c.id
        WHERE mr.id_bus = ?
        AND LOWER(TRIM(c.name)) = 'moteur'
        AND mo.oil_operation = 'Vidange'
        ORDER BY mr.date DESC, mr.id DESC
        LIMIT 1
    ");
    $lastVidangeStmt->execute([$id_bus]);
    $lastVidange = $lastVidangeStmt->fetch(PDO::FETCH_ASSOC);
    
    $km_parcouru = 0;
    $date_vidange = null;
    
    if ($lastVidange) {
        $date_vidange = $lastVidange['date'];
        
        // 3. Sum kilometrage after last vidange date
        $kmStmt = $db->prepare("
            SELECT COALESCE(SUM(kilometrage), 0) as total 
            FROM kilometrage 
            WHERE id_bus = ? 
            AND date_kilometrage > ?
        ");
        $kmStmt->execute([$id_bus, $date_vidange]);
        $km_parcouru = (int)$kmStmt->fetchColumn();
    } else {
        // If no vidange record found, sum all kilometrage
        $kmStmt = $db->prepare("SELECT COALESCE(SUM(kilometrage), 0) as total FROM kilometrage WHERE id_bus = ?");
        $kmStmt->execute([$id_bus]);
        $km_parcouru = (int)$kmStmt->fetchColumn();
    }

    // 4. Calculate remaining KM
    $diff_index = $freq_vidange - $km_parcouru;

    echo json_encode([
        'success' => true,
        'id_bus' => $id_bus,
        'matricule' => $bus['matricule_interne'],
        'freq_vidange' => $freq_vidange,
        'km_parcouru' => $km_parcouru,
        'date_derniere_vidange' => $date_vidange,
        'klm_reste_moteur' => $diff_index
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur base de données : ' . $e->getMessage()]);
}
