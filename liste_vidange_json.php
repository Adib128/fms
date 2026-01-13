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

$baseQuery = 'FROM vidange v INNER JOIN bus b ON v.id_bus = b.id_bus LEFT JOIN station s ON b.id_station = s.id_station LEFT JOIN operations_vidange ov ON v.id_vidange = ov.id_vidange LEFT JOIN huile h ON ov.id_huile = h.id_huile';
$whereClause = '';
$params = [];

if ($searchValue !== '') {
    $whereClause = ' WHERE DATE_FORMAT(v.date_vidange, "%d/%m/%Y") LIKE :search OR s.lib LIKE :search OR b.matricule_interne LIKE :search OR v.indexe LIKE :search OR v.ref_doc LIKE :search OR ov.categorie LIKE :search OR ov.type_huile LIKE :search OR ov.type_filtre LIKE :search';
    $params[':search'] = '%' . $searchValue . '%';
}

$recordsTotal = (int) $db->query('SELECT COUNT(*) FROM vidange')->fetchColumn();

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

$columns = ['v.id_vidange', 'v.date_vidange', 's.lib', 'b.matricule_interne', 'v.indexe'];
$orderColumnIndex = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
$orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'v.id_vidange';

// Default order by id_vidange DESC to show newest records first
$defaultOrder = $orderColumnIndex === 0 ? 'DESC' : $orderDir;
if ($orderColumnIndex === 0) {
    $orderDir = 'DESC';
}

$query = "SELECT v.id_vidange, v.date_vidange, v.indexe, v.ref_doc, b.matricule_interne, COALESCE(s.lib, '-') AS station_lib, GROUP_CONCAT(DISTINCT CASE WHEN ov.categorie = 'Huiles' THEN CONCAT(ov.type_huile, ' - ', ov.nature_operation, ' - ', COALESCE(h.libelle, ''), ' - ', ov.quantite) ELSE NULL END ORDER BY ov.id_operation SEPARATOR ' | ') AS huiles_operations, GROUP_CONCAT(DISTINCT CASE WHEN ov.categorie = 'Filtres' THEN CONCAT(ov.type_filtre, ' - ', ov.action_filtre) ELSE NULL END ORDER BY ov.id_operation SEPARATOR ' | ') AS filtres_operations $baseQuery $whereClause GROUP BY v.id_vidange, v.date_vidange, v.indexe, v.ref_doc, b.matricule_interne, s.lib ORDER BY v.id_vidange DESC, $orderColumn $orderDir LIMIT :start, :length";
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':length', $length, PDO::PARAM_INT);

$stmt->execute();

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $date = date_create($row['date_vidange']);
    
    // Build operations display
    $operations_display = [];
    if (!empty($row['huiles_operations'])) {
        $operations_display[] = '<div class="mb-1"><strong>Huiles:</strong> ' . htmlspecialchars($row['huiles_operations']) . '</div>';
    }
    if (!empty($row['filtres_operations'])) {
        $operations_display[] = '<div><strong>Filtres:</strong> ' . htmlspecialchars($row['filtres_operations']) . '</div>';
    }
    
    $operations_html = !empty($operations_display) ? implode('', $operations_display) : '<div class="text-gray-500">Aucune opération</div>';
    
    $data[] = [
        'id' => (int) $row['id_vidange'],
        'date' => $date ? $date->format('d/m/Y') : '',
        'station' => $row['station_lib'],
        'bus' => $row['matricule_interne'],
        'indexe' => $row['indexe'],
        'ref_doc' => $row['ref_doc'] ? htmlspecialchars($row['ref_doc']) : '-',
        'operations' => $operations_html,
        'actions' => '<div class="inline-flex items-center gap-2">
                        <a href="/detail-vidange?id=' . $row['id_vidange'] . '" class="btn-info py-1 px-2" title="Voir détails">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </a>
                        <a href="/modifier-vidange?id=' . $row['id_vidange'] . '" class="btn-success py-1 px-2" title="Modifier">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z" />
                            </svg>
                        </a>
                        <a href="#" onclick="confirmDelete(' . $row['id_vidange'] . ')" class="btn-danger py-1 px-2" title="Supprimer">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </a>
                    </div>'
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
