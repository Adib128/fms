<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

$inputDate = $_POST['date'] ?? null;
$inputStation = $_POST['station'] ?? null;

$metadata = [
    'input' => [
        'date' => $inputDate,
        'station' => $inputStation
    ],
    'query' => null
];

$station = $inputStation;

if ($inputDate) {
    $timestamp = strtotime($inputDate);
    if ($timestamp !== false) {
        $date = date('Y-m-d', $timestamp);
    }
}

$metadata['query'] = [
    'date' => $date ?? null,
    'station' => $station
];

$results = [];
if (!empty($date) && !empty($station)) {
    $stmt = $db->prepare('SELECT id_doc_carburant, num_doc_carburant FROM doc_carburant WHERE date = :date AND id_station = :station ORDER BY id_doc_carburant DESC');
    $stmt->execute([
        ':date' => $date,
        ':station' => $station
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    'data' => $results,
    'debug' => $metadata
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>