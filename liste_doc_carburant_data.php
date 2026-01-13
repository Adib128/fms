<?php
// Define ROUTED constant to indicate this file was included via the router
define('ROUTED', true);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include security check
require_once __DIR__ . '/app/security.php';

// Include helpers and config
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $request = $_GET + $_POST;

    $draw = isset($request['draw']) ? (int) $request['draw'] : 0;
    $start = isset($request['start']) ? max((int) $request['start'], 0) : 0;
    $length = isset($request['length']) ? (int) $request['length'] : 10;
    $length = $length < 0 ? 10 : $length;

    $searchValue = isset($request['search']['value']) ? trim($request['search']['value']) : '';
    $stationFilter = isset($request['station']) && $request['station'] !== '' ? (int) $request['station'] : null;
    $typeFilter = isset($request['type']) && $request['type'] !== '' ? trim($request['type']) : null;

    $columns = [
        0 => 'd.type',
        1 => 'd.num_doc_carburant',
        2 => 'd.date',
        3 => 'd.index_debut',
        4 => 'd.index_fin',
        5 => 's.lib'
    ];

    $orderColumnIndex = isset($request['order'][0]['column']) ? (int) $request['order'][0]['column'] : -1;
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'd.id_doc_carburant';
    $orderDir = isset($request['order'][0]['dir']) && strtolower($request['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';

    $whereClauses = [];
    $params = [];

    if ($stationFilter !== null) {
        $whereClauses[] = 'd.id_station = :station';
        $params[':station'] = $stationFilter;
    }

    if ($typeFilter !== null) {
        $whereClauses[] = 'd.type = :type';
        $params[':type'] = $typeFilter;
    }

    if ($searchValue !== '') {
        $whereClauses[] = '(
            d.type LIKE :search1
            OR d.num_doc_carburant LIKE :search2
            OR DATE_FORMAT(d.date, \'%d/%m/%Y\') LIKE :search3
            OR s.lib LIKE :search4
        )';
        $params[':search1'] = '%' . $searchValue . '%';
        $params[':search2'] = '%' . $searchValue . '%';
        $params[':search3'] = '%' . $searchValue . '%';
        $params[':search4'] = '%' . $searchValue . '%';
    }

    $whereSql = '';
    if (!empty($whereClauses)) {
        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    }

    // Get total records
    try {
        $totalRecordsStmt = $db->query('SELECT COUNT(*) AS total FROM doc_carburant');
        $totalRecords = $totalRecordsStmt ? (int) $totalRecordsStmt->fetch(PDO::FETCH_ASSOC)['total'] : 0;
    } catch (Exception $e) {
        throw new Exception('Error getting total records: ' . $e->getMessage());
    }

    // Get filtered records count
    try {
        $countFilteredSql = 'SELECT COUNT(*) AS total
            FROM doc_carburant d
            LEFT JOIN station s ON s.id_station = d.id_station
            ' . $whereSql;
        $countFilteredStmt = $db->prepare($countFilteredSql);
        
        // Only bind parameters if they exist
        if (!empty($params)) {
            $countFilteredStmt->execute($params);
        } else {
            $countFilteredStmt->execute();
        }
        
        $recordsFiltered = (int) $countFilteredStmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        throw new Exception('Error getting filtered records: ' . $e->getMessage());
    }

    // Get data
    try {
        $dataSql = 'SELECT
            d.id_doc_carburant,
            d.type,
            d.num_doc_carburant,
            d.date,
            d.index_debut,
            d.index_fin,
            s.lib AS station
        FROM doc_carburant d
        LEFT JOIN station s ON s.id_station = d.id_station
        ' . $whereSql . '
        ORDER BY ' . $orderColumn . ' ' . $orderDir . '
        LIMIT :start, :length';

        $dataStmt = $db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $dataStmt->bindValue($key, $value);
        }
        $dataStmt->bindValue(':start', $start, PDO::PARAM_INT);
        $dataStmt->bindValue(':length', $length, PDO::PARAM_INT);
        $dataStmt->execute();

        $rows = [];
        while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int) $row['id_doc_carburant'];
            $date = $row['date'] ? date('d/m/Y', strtotime($row['date'])) : '';

            $typeValue = $row['type'] ?? '';
            $displayType = $typeValue === 'Vrac' ? 'Vrac' : ($typeValue === 'Carte' ? 'Carte' : 'Carte'); // Default to Carte if empty or invalid
            
            $rows[] = [
                'type' => htmlspecialchars($displayType, ENT_QUOTES, 'UTF-8'),
                'num_doc_carburant' => htmlspecialchars($row['num_doc_carburant'] ?? '', ENT_QUOTES, 'UTF-8'),
                'date' => $date,
                'index_debut' => $row['index_debut'] == 0 || $row['index_debut'] === null ? '' : htmlspecialchars($row['index_debut'], ENT_QUOTES, 'UTF-8'),
                'index_fin' => $row['index_fin'] == 0 || $row['index_fin'] === null ? '' : htmlspecialchars($row['index_fin'], ENT_QUOTES, 'UTF-8'),
                'station' => htmlspecialchars($row['station'] ?? '', ENT_QUOTES, 'UTF-8'),
                'actions' => '<div class="inline-flex items-center justify-center gap-2">
                    <a class="btn-info py-1 px-2" href="/consulter-doc-carburant?id=' . $id . '" title="Consulter">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </a>
                    <a class="btn-success py-1 px-2" href="/modifier-doc-carburant?id=' . $id . '" title="Modifier">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z" />
                        </svg>
                    </a>
                    <a class="btn-danger py-1 px-2" href="/supprimer-doc-carburant?id=' . $id . '" onclick="return confirm(\'Voulez vous vraiment supprimer l\u0027enrégistrement de carburant ?\');" title="Supprimer">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </a>
                </div>'
            ];
        }
    } catch (Exception $e) {
        throw new Exception('Error getting data: ' . $e->getMessage());
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $recordsFiltered,
        'data' => $rows
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // Return error in DataTables format
    echo json_encode([
        'draw' => isset($request['draw']) ? (int) $request['draw'] : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
