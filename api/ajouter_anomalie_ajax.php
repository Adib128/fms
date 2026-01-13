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
$designation = trim($data['designation'] ?? '');
$id_system = isset($data['id_system']) ? (int)$data['id_system'] : 0;

// Validate
if (empty($designation)) {
    echo json_encode(['error' => 'La désignation est requise']);
    exit;
}

if ($id_system <= 0) {
    echo json_encode(['error' => 'Le système est requis']);
    exit;
}

try {
    // Check if already exists for this system
    $stmt = $db->prepare("SELECT id FROM anomalie WHERE designation = ? AND id_system = ?");
    $stmt->execute([$designation, $id_system]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Cette anomalie existe déjà pour ce système']);
        exit;
    }

    // Insert
    $stmt = $db->prepare("INSERT INTO anomalie (designation, id_system) VALUES (?, ?)");
    $stmt->execute([$designation, $id_system]);
    $id = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'id' => $id,
        'designation' => $designation,
        'id_system' => $id_system
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur base de données: ' . $e->getMessage()]);
}
