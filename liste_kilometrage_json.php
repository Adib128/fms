<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

if (!isset($db)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection unavailable.']);
    exit;
}

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$start = isset($_POST['start']) ? max((int) $_POST['start'], 0) : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

$baseQuery = 'FROM kilometrage k INNER JOIN bus b ON k.id_bus = b.id_bus LEFT JOIN station s ON b.id_station = s.id_station';
$whereClause = '';
$params = [];

if ($searchValue !== '') {
    $whereClause = ' WHERE b.matricule_interne LIKE :search1 OR s.lib LIKE :search2 OR DATE_FORMAT(k.date_kilometrage, "%d/%m/%Y") LIKE :search3 OR k.kilometrage LIKE :search4';
    $params[':search1'] = '%' . $searchValue . '%';
    $params[':search2'] = '%' . $searchValue . '%';
    $params[':search3'] = '%' . $searchValue . '%';
    $params[':search4'] = '%' . $searchValue . '%';
}

$recordsTotal = (int) $db->query('SELECT COUNT(*) FROM kilometrage')->fetchColumn();

if ($whereClause !== '') {
    $stmtFiltered = $db->prepare("SELECT COUNT(*) $baseQuery $whereClause");
    foreach ($params as $key => $value) {
        $stmtFiltered->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmtFiltered->execute();
    $recordsFiltered = (int) $stmtFiltered->fetchColumn();
} else {
    $recordsFiltered = $recordsTotal;
}

if ($length < 0) {
    $length = $recordsFiltered > 0 ? $recordsFiltered : $recordsTotal;
}

$query = "SELECT k.id_kilometrage, k.date_kilometrage, k.kilometrage, b.matricule_interne, COALESCE(s.lib, '-') AS station_lib $baseQuery $whereClause ORDER BY k.id_kilometrage DESC LIMIT :start, :length";
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':length', $length, PDO::PARAM_INT);

$stmt->execute();

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $date = date_create($row['date_kilometrage']);
    $data[] = [
        'id' => (int) $row['id_kilometrage'],
        'date' => $date ? $date->format('d/m/Y') : '',
        'station' => $row['station_lib'],
        'bus' => $row['matricule_interne'],
        'kilometrage' => (int) $row['kilometrage'],
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>