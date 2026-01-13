<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$libelle = trim($data['libelle'] ?? '');
$id_anomalie = isset($data['id_anomalie']) ? (int)$data['id_anomalie'] : null;

// Validate
if (empty($libelle)) {
    echo json_encode(['error' => 'Le libellé est requis']);
    exit;
}

try {
    // Check if already exists (optional: check per anomaly or globally? Usually globally unique name is enough, or name+anomaly. Let's stick to name for now to avoid duplicates)
    $stmt = $db->prepare("SELECT id FROM intervention WHERE libelle = ?");
    $stmt->execute([$libelle]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Cette intervention existe déjà']);
        exit;
    }

    // Insert
    $stmt = $db->prepare("INSERT INTO intervention (libelle, id_anomalie) VALUES (?, ?)");
    $stmt->execute([$libelle, $id_anomalie]);
    $id = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'id' => $id,
        'libelle' => $libelle,
        'id_anomalie' => $id_anomalie
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur base de données: ' . $e->getMessage()]);
}
