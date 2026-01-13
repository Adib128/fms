<?php
require '../config.php';

header('Content-Type: application/json');

// DataTables parameters
$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search = $_POST['search']['value'] ?? '';
$orderColumn = $_POST['order'][0]['column'] ?? 0;
$orderDir = $_POST['order'][0]['dir'] ?? 'desc';

// Column mapping
$columns = [
    0 => 'f.id_fiche',
    1 => 'f.numd_doc', 
    2 => 'f.date',
    3 => 's.lib'
];

// Default ordering by ID DESC if no ordering specified
if (!isset($_POST['order'])) {
    $orderColumn = 0; // ID column
    $orderDir = 'desc';
}

try {
    // Build base query
    $baseQuery = "FROM fiche_entretien f LEFT JOIN station s ON s.id_station = f.id_station";
    $whereClause = "WHERE 1=1 AND (f.numd_doc IS NULL OR f.numd_doc NOT LIKE 'OP-%')";
    
    // Add search
    if (!empty($search)) {
        $whereClause .= " AND (f.numd_doc LIKE :search1 OR f.date LIKE :search2 OR s.lib LIKE :search3)";
        $searchParam = '%' . $search . '%';
    }
    
    // Get total records (excluding OP- fiches)
    $totalQuery = "SELECT COUNT(*) as total $baseQuery WHERE (f.numd_doc IS NULL OR f.numd_doc NOT LIKE 'OP-%')";
    $stmt = $db->prepare($totalQuery);
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    
    // Get filtered records
    $filteredQuery = "SELECT COUNT(*) as total $baseQuery $whereClause";
    $stmt = $db->prepare($filteredQuery);
    
    // Bind search parameters for filtered query
    if (!empty($search)) {
        $stmt->bindValue(':search1', $searchParam);
        $stmt->bindValue(':search2', $searchParam);
        $stmt->bindValue(':search3', $searchParam);
    }
    
    $stmt->execute();
    $totalFiltered = $stmt->fetchColumn();
    
    // Get paginated data
    $orderBy = $columns[$orderColumn] . ' ' . $orderDir;
    $dataQuery = "SELECT f.id_fiche, f.numd_doc, f.date, s.lib AS station 
                  $baseQuery $whereClause 
                  ORDER BY $orderBy 
                  LIMIT :start, :length";
    
    $stmt = $db->prepare($dataQuery);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    
    // Bind search parameters for data query
    if (!empty($search)) {
        $stmt->bindValue(':search1', $searchParam);
        $stmt->bindValue(':search2', $searchParam);
        $stmt->bindValue(':search3', $searchParam);
    }
    
    $stmt->execute();
    $fiches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for DataTables
    $data = [];
    foreach ($fiches as $fiche) {
        $actions = '
            <div class="flex gap-1 justify-center">
                <a href="/details-fiche-entretien?id=' . (int)$fiche['id_fiche'] . '" class="btn-info py-1 px-2" title="Détails de la fiche">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </a>
                <a href="/modifier-document?id=' . (int)$fiche['id_fiche'] . '" class="btn-success py-1 px-2" title="Modifier le document">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z" />
                    </svg>
                </a>
                <a href="/supprimer-fiche-entretien?id=' . (int)$fiche['id_fiche'] . '" 
                   onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette fiche d\'entretien ?\')"
                   class="btn-danger py-1 px-2" 
                   title="Supprimer la fiche">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </a>
            </div>';
        
        $data[] = [
            (int)$fiche['id_fiche'],
            htmlspecialchars($fiche['numd_doc'] ?? '—', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((new DateTime($fiche['date']))->format('d/m/Y'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($fiche['station'] ?? '—', ENT_QUOTES, 'UTF-8'),
            $actions
        ];
    }
    
    echo json_encode([
        'draw' => (int)$draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalFiltered,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'draw' => (int)$draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
}
?>
