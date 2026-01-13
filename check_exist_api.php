<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$numDoc = filter_input(INPUT_POST, 'num_doc', FILTER_DEFAULT);
$numDoc = $numDoc !== null ? trim($numDoc) : null;

if ($numDoc === null || $numDoc === false || $numDoc === '') {
    echo json_encode(['exist' => '0']);
    exit;
}

$stmt = $db->prepare('SELECT 1 FROM doc_carburant WHERE num_doc_carburant = :num_doc LIMIT 1');
$stmt->execute([':num_doc' => $numDoc]);

echo json_encode(['exist' => $stmt->fetchColumn() ? '1' : '0']);
