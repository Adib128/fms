<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT 
            d.*,
            s.lib as station_nom,
            b.matricule_interne as bus_code,
            b.matricule as bus_immatriculation,
            c.nom_prenom as chauffeur_nom
        FROM demande d
        LEFT JOIN station s ON d.id_station = s.id_station
        LEFT JOIN bus b ON d.id_vehicule = b.id_bus
        LEFT JOIN chauffeur c ON d.id_chauffeur = c.id_chauffeur
        WHERE d.id = ?
    ");
    $stmt->execute([$id]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($demande) {
        // Fetch anomalies
        $stmtAn = $db->prepare("SELECT id_anomalie FROM demande_anomalie WHERE id_demande = ?");
        $stmtAn->execute([$id]);
        $demande['anomalies'] = $stmtAn->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode($demande);
    } else {
        echo json_encode(['error' => 'Demande introuvable']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur base de données: ' . $e->getMessage()]);
}
