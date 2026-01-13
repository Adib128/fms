<?php
// Prevent any output before JSON
ob_start();

require '../config.php';

// Clear any output that might have been generated
ob_clean();

header('Content-Type: application/json');

try {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    $startDate = $data['start_date'] ?? null;
    $endDate = $data['end_date'] ?? null;
    $busId = $data['bus_id'] ?? null;
    $sourceKilometrage = $data['source_kilometrage'] ?? 'index';
    
    if (!$startDate || !$endDate) {
        throw new Exception('Date range is required');
    }
    
    // Get kilometrage data from kilometrage table for the selected period (always fetch for both metrics)
    // For chart display: group by bus_id and date
    $kilometrageData = [];
    // For total calculation: group by bus_id only (sum all dates)
    $kilometrageTotalByBus = [];
    
    $kmQuery = "
        SELECT id_bus, date_kilometrage, SUM(kilometrage) as total_km
        FROM kilometrage
        WHERE date_kilometrage BETWEEN :start_date AND :end_date
    ";
    $kmParams = [
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ];
    
    if ($busId && $busId !== '') {
        $kmQuery .= " AND id_bus = :bus_id";
        $kmParams[':bus_id'] = $busId;
    }
    
    $kmQuery .= " GROUP BY id_bus, date_kilometrage ORDER BY id_bus, date_kilometrage";
    
    $kmStmt = $db->prepare($kmQuery);
    $kmStmt->execute($kmParams);
    $kmResults = $kmStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by bus_id and date (format: dd/mm/yyyy) for chart display
    foreach ($kmResults as $kmRow) {
        $busIdKm = $kmRow['id_bus'];
        $dateKm = date('d/m/Y', strtotime($kmRow['date_kilometrage']));
        if (!isset($kilometrageData[$busIdKm])) {
            $kilometrageData[$busIdKm] = [];
        }
        if (!isset($kilometrageData[$busIdKm][$dateKm])) {
            $kilometrageData[$busIdKm][$dateKm] = 0;
        }
        $kilometrageData[$busIdKm][$dateKm] += floatval($kmRow['total_km']);
        
        // Also accumulate total by bus (for calculation)
        if (!isset($kilometrageTotalByBus[$busIdKm])) {
            $kilometrageTotalByBus[$busIdKm] = 0;
        }
        $kilometrageTotalByBus[$busIdKm] += floatval($kmRow['total_km']);
    }
    
    // Build the base query
    $baseQuery = "
        SELECT 
            b.id_bus,
            b.matricule_interne,
            b.conso_huile,
            mo.type,
            mo.oil_operation,
            mo.oil_type_id,
            mo.liquide_type_id,
            mo.quantity,
            mo.filter_operation,
            mo.filter_type_id,
            c.name as compartiment_name,
            ot.name as oil_type_name,
            ot.usageOil,
            l.name as liquide_type_name,
            ft.name as filter_type_name,
            ft.usageFilter,
            f.date as fiche_date,
            f.numd_doc,
            f.id_fiche,
            mr.index_km
        FROM maintenance_operations mo
        INNER JOIN maintenance_records mr ON mo.record_id = mr.id
        INNER JOIN fiche_entretien f ON mr.fiche_id = f.id_fiche
        INNER JOIN bus b ON mr.id_bus = b.id_bus
        LEFT JOIN compartiments c ON mo.compartiment_id = c.id
        LEFT JOIN oil_types ot ON mo.oil_type_id = ot.id
        LEFT JOIN liquides l ON mo.liquide_type_id = l.id
        LEFT JOIN filter_types ft ON mo.filter_type_id = ft.id
        WHERE f.date BETWEEN :start_date AND :end_date
    ";
    
    $params = [
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ];
    
    // Add vehicle filter if specified
    if ($busId && $busId !== '') {
        $baseQuery .= " AND b.id_bus = :bus_id";
        $params[':bus_id'] = $busId;
    }
    
    $baseQuery .= " ORDER BY b.matricule_interne, f.date, mo.type";
    
    // Execute the query
    $stmt = $db->prepare($baseQuery);
    $stmt->execute($params);
    $operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group data by vehicle
    $vehiclesData = [];
    
    foreach ($operations as $operation) {
        $busId = $operation['id_bus'];
        $matricule = $operation['matricule_interne'];
        
        // Initialize vehicle data if not exists
        if (!isset($vehiclesData[$busId])) {
            $vehiclesData[$busId] = [
                'id_bus' => $busId,
                'matricule_interne' => $matricule,
                'conso_huile' => $operation['conso_huile'] ?? null,
                'total_oil' => 0,
                'total_liquide' => 0,
                'total_filters' => 0,
                'oil_operations' => 0,
                'liquide_operations' => 0,
                'filter_operations' => 0,
                'operations' => []
            ];
        }
        
        // Add operation details
        $operationDetail = [
            'date' => date('d/m/Y', strtotime($operation['fiche_date'])),
            'fiche_num' => $operation['numd_doc'],
            'fiche_id' => $operation['id_fiche'] ?? null,
            'type' => $operation['type'],
            'compartiment_name' => $operation['compartiment_name'] ?: 'Non spécifié',
            'oil_operation' => $operation['oil_operation'] ?: '',
            'oil_type_name' => $operation['oil_type_name'] ?: '',
            'usage_oil' => $operation['usageOil'] ?: '',
            'liquide_type_name' => $operation['liquide_type_name'] ?: '',
            'quantity' => $operation['quantity'] ?: 0,
            'filter_operation' => $operation['filter_operation'] ?: '',
            'filter_type_name' => $operation['filter_type_name'] ?: '',
            'usage_filter' => $operation['usageFilter'] ?: '',
            'index_km' => $operation['index_km'] ?? null
        ];
        
        $vehiclesData[$busId]['operations'][] = $operationDetail;
        
        // Update totals based on operation type
        if ($operation['type'] === 'Huile') {
            $vehiclesData[$busId]['total_oil'] += floatval($operation['quantity'] ?: 0);
            $vehiclesData[$busId]['oil_operations']++;
        } elseif ($operation['type'] === 'Liquide') {
            $vehiclesData[$busId]['total_liquide'] += floatval($operation['quantity'] ?: 0);
            $vehiclesData[$busId]['liquide_operations']++;
        } elseif ($operation['type'] === 'Filter') {
            $vehiclesData[$busId]['total_filters']++;
            $vehiclesData[$busId]['filter_operations']++;
        }
    }
    
    // Convert to indexed array and sort by vehicle matricule
    $result = array_values($vehiclesData);
    usort($result, function($a, $b) {
        return strcmp($a['matricule_interne'], $b['matricule_interne']);
    });
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'kilometrage_data' => $kilometrageData,
        'kilometrage_total_by_bus' => $kilometrageTotalByBus,
        'source_kilometrage' => $sourceKilometrage,
        'summary' => [
            'total_vehicles' => count($result),
            'total_oil' => array_sum(array_column($result, 'total_oil')),
            'total_liquide' => array_sum(array_column($result, 'total_liquide')),
            'total_filters' => array_sum(array_column($result, 'total_filters')),
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]
    ]);
    
} catch (Exception $e) {
    // Ensure no output before JSON
    ob_clean();
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
