<?php
// Include database connection
require 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get POST parameters
$date_debut = $_POST['date_debut'] ?? '';
$date_fin = $_POST['date_fin'] ?? '';
$bus = $_POST['bus'] ?? '';

// DataTables parameters
$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search = $_POST['search']['value'] ?? '';
$order_column = $_POST['order'][0]['column'] ?? 0;
$order_dir = $_POST['order'][0]['dir'] ?? 'asc';

// Column mapping
$columns = ['date_kilometrage', 'station_lib', 'matricule_interne', 'kilometrage'];
$order_column_name = $columns[$order_column] ?? 'date_kilometrage';

// Build base SQL query
$base_sql = "SELECT k.id_kilometrage, k.date_kilometrage, k.kilometrage, b.matricule_interne, b.id_bus, COALESCE(s.lib, '-') AS station_lib, b.id_station 
             FROM kilometrage k 
             INNER JOIN bus b ON k.id_bus = b.id_bus 
             LEFT JOIN station s ON b.id_station = s.id_station 
             WHERE k.date_kilometrage >= '$date_debut' AND k.date_kilometrage <= '$date_fin'";

// Add bus filter if not "all"
if ($bus != "all") {
    $base_sql .= " AND k.id_bus='$bus'";
}

// Add search filter
if (!empty($search)) {
    $base_sql .= " AND (
        k.date_kilometrage LIKE '%$search%' OR 
        b.matricule_interne LIKE '%$search%' OR 
        s.lib LIKE '%$search%' OR 
        k.kilometrage LIKE '%$search%'
    )";
}

// Get total records count (before filtering)
$total_sql = "SELECT COUNT(*) as total FROM kilometrage k 
              INNER JOIN bus b ON k.id_bus = b.id_bus 
              LEFT JOIN station s ON b.id_station = s.id_station 
              WHERE k.date_kilometrage >= '$date_debut' AND k.date_kilometrage <= '$date_fin'";

if ($bus != "all") {
    $total_sql .= " AND k.id_bus='$bus'";
}

$total_result = $db->query($total_sql);
$total_records = $total_result->fetch()['total'];

// Get filtered records count
$filtered_sql = "SELECT COUNT(*) as total FROM ($base_sql) as count_query";
$filtered_result = $db->query($filtered_sql);
$filtered_records = $filtered_result->fetch()['total'];

// Add ordering and pagination
$sql = $base_sql . " ORDER BY $order_column_name $order_dir LIMIT $start, $length";

// Execute main query
$result = $db->query($sql);

// Prepare data for DataTables
$data = [];
$total_km = 0;

foreach ($result as $row) {
    $data[] = [
        'date_kilometrage' => date('d/m/Y', strtotime($row['date_kilometrage'])),
        'station_lib' => $row['station_lib'],
        'matricule_interne' => $row['matricule_interne'],
        'kilometrage' => (int)$row['kilometrage']
    ];
    $total_km += $row['kilometrage'];
}

// Calculate total for all records (not just current page)
$total_all_sql = $base_sql;
$total_all_result = $db->query($total_all_sql);
$total_km_all = 0;
foreach ($total_all_result as $row) {
    $total_km_all += $row['kilometrage'];
}

// Prepare response
$response = [
    'draw' => intval($draw),
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_records,
    'data' => $data,
    'totalKm' => $total_km_all
];

echo json_encode($response);
?>
