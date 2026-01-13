<?php
require_once __DIR__ . '/app/security.php';
require_once __DIR__ . '/config.php';
require 'header.php';

// Handle AJAX request for chart data
if (isset($_GET['selected_year']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $selectedYear = $_GET['selected_year'];
    
    // Get kilometrage by bus type for selected year
    $kilometrageByGenreStmt = $db->query("
        SELECT b.type AS genre, SUM(c.index_km) as total_km 
        FROM carburant c 
        JOIN bus b ON c.id_bus = b.id_bus 
        WHERE YEAR(c.date) = '$selectedYear' 
        GROUP BY b.type 
        ORDER BY total_km DESC
    ");
    $kilometrageByGenre = $kilometrageByGenreStmt ? $kilometrageByGenreStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Get consumption by bus type for selected year
    $consumptionByGenreStmt = $db->query("
        SELECT b.type AS genre, SUM(c.qte_go) as total_qte 
        FROM carburant c 
        JOIN bus b ON c.id_bus = b.id_bus 
        WHERE YEAR(c.date) = '$selectedYear' 
        GROUP BY b.type 
        ORDER BY total_qte DESC
    ");
    $consumptionByGenre = $consumptionByGenreStmt ? $consumptionByGenreStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Get monthly consumption data for selected year
    $monthlyConsumptionStmt = $db->query("
        SELECT 
            MONTH(date) as month, 
            MONTHNAME(date) as month_name,
            SUM(qte_go) as total_qte,
            COUNT(DISTINCT id_bus) as active_buses
        FROM carburant 
        WHERE YEAR(date) = '$selectedYear' 
        GROUP BY MONTH(date), MONTHNAME(date) 
        ORDER BY MONTH(date)
    ");
    $monthlyConsumption = $monthlyConsumptionStmt ? $monthlyConsumptionStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Get monthly kilometrage data for selected year
    // Use the kilometrage table which contains actual distance traveled, not odometer readings
    $monthlyKilometrageStmt = $db->query("
        SELECT 
            MONTH(k.date_kilometrage) as month, 
            MONTHNAME(k.date_kilometrage) as month_name,
            SUM(k.kilometrage) as total_km
        FROM kilometrage k 
        WHERE YEAR(k.date_kilometrage) = '$selectedYear' 
        GROUP BY MONTH(k.date_kilometrage), MONTHNAME(k.date_kilometrage) 
        ORDER BY MONTH(k.date_kilometrage)
    ");
    $monthlyKilometrage = $monthlyKilometrageStmt ? $monthlyKilometrageStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Get oil types quantities for selected year, separated by operation type
    $oilByTypeStmt = $db->query("
        SELECT 
            ot.name as oil_type, 
            ot.usageOil,
            mo.oil_operation,
            SUM(mo.quantity) as total_quantity 
        FROM maintenance_operations mo 
        JOIN maintenance_records mr ON mo.record_id = mr.id
        JOIN oil_types ot ON mo.oil_type_id = ot.id 
        WHERE YEAR(mr.date) = '$selectedYear' AND mo.type = 'Huile' AND mo.quantity > 0 AND mo.oil_operation IN ('Vidange', 'Apoint')
        GROUP BY ot.id, ot.name, ot.usageOil, mo.oil_operation
        ORDER BY ot.name, mo.oil_operation
    ");
    $oilByTypeRaw = $oilByTypeStmt ? $oilByTypeStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Process data for grouped bar chart
    $oilTypes = [];
    $vidangeData = [];
    $apointData = [];
    
    // Debug: Log raw data
    error_log("=== OIL DATA DEBUG ===");
    error_log("Raw oil data: " . print_r($oilByTypeRaw, true));
    
    foreach ($oilByTypeRaw as $row) {
        $oilTypeKey = $row['oil_type'] . ' (' . $row['usageOil'] . ')';
        
        if (!in_array($oilTypeKey, $oilTypes)) {
            $oilTypes[] = $oilTypeKey;
            $vidangeData[] = 0;
            $apointData[] = 0;
        }
        
        $index = array_search($oilTypeKey, $oilTypes);
        if ($row['oil_operation'] === 'Vidange') {
            $vidangeData[$index] = floatval($row['total_quantity']);
            error_log("Vidange - {$oilTypeKey}: {$vidangeData[$index]}");
        } elseif ($row['oil_operation'] === 'Apoint') {
            $apointData[$index] = floatval($row['total_quantity']);
            error_log("Apoint - {$oilTypeKey}: {$apointData[$index]}");
        }
    }
    
    $oilByType = [
        'labels' => $oilTypes,
        'vidange' => $vidangeData,
        'apoint' => $apointData
    ];
    
    error_log("Final oil structure: " . print_r($oilByType, true));
    error_log("=== END OIL DATA DEBUG ===");

    // Get liquide types quantities for selected year
    $liquideByTypeStmt = $db->query("
        SELECT l.name as liquide_type, SUM(mo.quantity) as total_quantity 
        FROM maintenance_operations mo 
        JOIN maintenance_records mr ON mo.record_id = mr.id
        JOIN liquides l ON mo.liquide_type_id = l.id 
        WHERE YEAR(mr.date) = '$selectedYear' AND mo.type = 'Liquide' AND mo.quantity > 0
        GROUP BY l.id, l.name 
        ORDER BY total_quantity DESC
    ");
    $liquideByType = $liquideByTypeStmt ? $liquideByTypeStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Get filter operations statistics for selected year
    $filterStatsStmt = $db->query("
        SELECT 
            ft.name as filter_type,
            ft.usageFilter,
            mo.filter_operation as filter_action,
            COUNT(*) as count
        FROM maintenance_operations mo 
        JOIN maintenance_records mr ON mo.record_id = mr.id
        JOIN filter_types ft ON mo.filter_type_id = ft.id 
        WHERE YEAR(mr.date) = '$selectedYear' AND mo.type = 'Filter' AND mo.filter_operation IS NOT NULL
        GROUP BY ft.id, ft.name, ft.usageFilter, mo.filter_operation 
        ORDER BY ft.name, mo.filter_operation
    ");
    $filterStats = $filterStatsStmt ? $filterStatsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Get kilometrage and consumption by marque
    $kilometrageByMarqueStmt = $db->query("
        SELECT b.marque AS marque, SUM(c.index_km) as total_km, SUM(c.qte_go) as total_qte 
        FROM carburant c 
        JOIN bus b ON c.id_bus = b.id_bus 
        WHERE YEAR(c.date) = '$selectedYear' 
        GROUP BY b.marque 
        ORDER BY total_km DESC
    ");
    $kilometrageByMarque = $kilometrageByMarqueStmt ? $kilometrageByMarqueStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    // Get consommation by marque
    $consumptionByMarqueStmt = $db->query("
        SELECT b.marque AS marque, SUM(c.qte_go) as total_qte 
        FROM carburant c 
        JOIN bus b ON c.id_bus = b.id_bus 
        WHERE YEAR(c.date) = '$selectedYear' 
        GROUP BY b.marque 
        ORDER BY total_qte DESC
    ");
    $consumptionByMarque = $consumptionByMarqueStmt ? $consumptionByMarqueStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Get consumption by fuel type (GO, GSS, ESP)
    $consumptionByFuelTypeStmt = $db->query("
        SELECT 
            type as fuel_type, 
            SUM(qte_go) as total_qte,
            COUNT(*) as count
        FROM carburant 
        WHERE YEAR(date) = '$selectedYear' 
        GROUP BY type 
        ORDER BY total_qte DESC
    ");
    $consumptionByFuelType = $consumptionByFuelTypeStmt ? $consumptionByFuelTypeStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Get status stats for Demandes
    $demandeStatusStmt = $db->query("SELECT etat, COUNT(*) as count FROM demande WHERE YEAR(date) = '$selectedYear' GROUP BY etat");
    $demandeStatus = $demandeStatusStmt ? $demandeStatusStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Get status stats for Ordres de Travail
    $ordreStatusStmt = $db->query("SELECT etat, COUNT(*) as count FROM ordre WHERE YEAR(date) = '$selectedYear' GROUP BY etat");
    $ordreStatus = $ordreStatusStmt ? $ordreStatusStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    header('Content-Type: application/json');
    echo json_encode([
        'kilometrageByGenre' => $kilometrageByGenre,
        'consumptionByGenre' => $consumptionByGenre,
        'monthlyConsumption' => $monthlyConsumption,
        'monthlyKilometrage' => $monthlyKilometrage,
        'oilByType' => $oilByType,
        'liquideByType' => $liquideByType,
        'filterStats' => $filterStats,
        'consumptionByFuelType' => $consumptionByFuelType,
        'kilometrageByMarque' => $kilometrageByMarque,
        'consumptionByMarque' => $consumptionByMarque,
        'demandeStatus' => $demandeStatus,
        'ordreStatus' => $ordreStatus
    ]);
    exit;
}


// Get global statistics for dashboard
$currentYear = date('Y');
$currentMonth = date('m');

// Get selected year from GET or default to current year
$selectedYear = $_GET['selected_year'] ?? $currentYear;

// Total kilometrage for selected year
$totalKilometrageStmt = $db->query("
    SELECT SUM(k.kilometrage) as total_km 
    FROM kilometrage k 
    WHERE YEAR(k.date_kilometrage) = '$selectedYear'
");
$totalKilometrage = $totalKilometrageStmt ? (float)($totalKilometrageStmt->fetchColumn() ?: 0) : 0;

// Total consumption for selected year
$totalConsumptionStmt = $db->query("
    SELECT SUM(c.qte_go) as total_qte 
    FROM carburant c 
    WHERE YEAR(c.date) = '$selectedYear'
");
$totalConsumption = $totalConsumptionStmt ? (float)($totalConsumptionStmt->fetchColumn() ?: 0) : 0;

// Kilometrage by bus type for selected year
$kilometrageByGenreStmt = $db->query("
    SELECT b.type AS genre, SUM(c.index_km) as total_km 
    FROM carburant c 
    JOIN bus b ON c.id_bus = b.id_bus 
    WHERE YEAR(c.date) = '$selectedYear' 
    GROUP BY b.type 
    ORDER BY total_km DESC
");
$kilometrageByGenre = $kilometrageByGenreStmt ? $kilometrageByGenreStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Consumption by bus type for selected year
$consumptionByGenreStmt = $db->query("
    SELECT b.type AS genre, SUM(c.qte_go) as total_qte 
    FROM carburant c 
    JOIN bus b ON c.id_bus = b.id_bus 
    WHERE YEAR(c.date) = '$selectedYear' 
    GROUP BY b.type 
    ORDER BY total_qte DESC
");
$consumptionByGenre = $consumptionByGenreStmt ? $consumptionByGenreStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Debug: Log the consumption data
error_log("Selected Year: $selectedYear");
error_log("Consumption by Agency Query Result: " . print_r($consumptionByGenre, true));

// Get available years for filter (2019 to current year)
$availableYears = [];
for ($year = 2019; $year <= $currentYear; $year++) {
    $availableYears[] = $year;
}

// Monthly consumption data for the selected year
$monthlyConsumptionStmt = $db->query("
    SELECT 
        MONTH(date) as month, 
        MONTHNAME(date) as month_name,
        SUM(qte_go) as total_qte,
        COUNT(DISTINCT id_bus) as active_buses
    FROM carburant 
    WHERE YEAR(date) = '$selectedYear' 
    GROUP BY MONTH(date), MONTHNAME(date) 
    ORDER BY MONTH(date)
");
$monthlyConsumption = $monthlyConsumptionStmt ? $monthlyConsumptionStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Monthly kilometrage data for the selected year
// Use the kilometrage table which contains actual distance traveled, not odometer readings
$monthlyKilometrageStmt = $db->query("
    SELECT 
        MONTH(k.date_kilometrage) as month, 
        MONTHNAME(k.date_kilometrage) as month_name,
        SUM(k.kilometrage) as total_km
    FROM kilometrage k 
    WHERE YEAR(k.date_kilometrage) = '$selectedYear' 
    GROUP BY MONTH(k.date_kilometrage), MONTHNAME(k.date_kilometrage) 
    ORDER BY MONTH(k.date_kilometrage)
");
$monthlyKilometrage = $monthlyKilometrageStmt ? $monthlyKilometrageStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Get oil types quantities for selected year, separated by operation type
$oilByTypeStmt = $db->query("
    SELECT 
        ot.name as oil_type, 
        ot.usageOil,
        mo.oil_operation,
        SUM(mo.quantity) as total_quantity 
    FROM maintenance_operations mo 
    JOIN maintenance_records mr ON mo.record_id = mr.id
    JOIN oil_types ot ON mo.oil_type_id = ot.id 
    WHERE YEAR(mr.date) = '$selectedYear' AND mo.type = 'Huile' AND mo.quantity > 0 AND mo.oil_operation IN ('Vidange', 'Apoint')
    GROUP BY ot.id, ot.name, ot.usageOil, mo.oil_operation
    ORDER BY ot.name, mo.oil_operation
");
$oilByTypeRaw = $oilByTypeStmt ? $oilByTypeStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Process data for grouped bar chart
$oilTypes = [];
$vidangeData = [];
$apointData = [];

// Debug: Log raw data
error_log("=== OIL DATA DEBUG (NORMAL LOAD) ===");
error_log("Raw oil data: " . print_r($oilByTypeRaw, true));

foreach ($oilByTypeRaw as $row) {
    $oilTypeKey = $row['oil_type'] . ' (' . $row['usageOil'] . ')';
    
    if (!in_array($oilTypeKey, $oilTypes)) {
        $oilTypes[] = $oilTypeKey;
        $vidangeData[] = 0;
        $apointData[] = 0;
    }
    
    $index = array_search($oilTypeKey, $oilTypes);
    if ($row['oil_operation'] === 'Vidange') {
        $vidangeData[$index] = floatval($row['total_quantity']);
        error_log("Vidange - {$oilTypeKey}: {$vidangeData[$index]}");
    } elseif ($row['oil_operation'] === 'Apoint') {
        $apointData[$index] = floatval($row['total_quantity']);
        error_log("Apoint - {$oilTypeKey}: {$apointData[$index]}");
    }
}

$oilByType = [
    'labels' => $oilTypes,
    'vidange' => $vidangeData,
    'apoint' => $apointData
];

error_log("Final oil structure: " . print_r($oilByType, true));
error_log("=== END OIL DATA DEBUG (NORMAL LOAD) ===");

// Get liquide types quantities for selected year
$liquideByTypeStmt = $db->query("
    SELECT l.name as liquide_type, SUM(mo.quantity) as total_quantity 
    FROM maintenance_operations mo 
    JOIN maintenance_records mr ON mo.record_id = mr.id
    JOIN liquides l ON mo.liquide_type_id = l.id 
    WHERE YEAR(mr.date) = '$selectedYear' AND mo.type = 'Liquide' AND mo.quantity > 0
    GROUP BY l.id, l.name 
    ORDER BY total_quantity DESC
");
$liquideByType = $liquideByTypeStmt ? $liquideByTypeStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Get filter operations statistics for selected year
$filterStatsStmt = $db->query("
    SELECT 
        ft.name as filter_type,
        ft.usageFilter,
        mo.filter_operation as filter_action,
        COUNT(*) as count
    FROM maintenance_operations mo 
    JOIN maintenance_records mr ON mo.record_id = mr.id
    JOIN filter_types ft ON mo.filter_type_id = ft.id 
    WHERE YEAR(mr.date) = '$selectedYear' AND mo.type = 'Filter' AND mo.filter_operation IS NOT NULL
    GROUP BY ft.id, ft.name, ft.usageFilter, mo.filter_operation 
    ORDER BY ft.name, mo.filter_operation
");
$filterStats = $filterStatsStmt ? $filterStatsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Get kilometrage and consumption by marque for selected year
$kilometrageByMarqueStmt = $db->query("
    SELECT b.marque AS marque, SUM(c.index_km) as total_km, SUM(c.qte_go) as total_qte 
    FROM carburant c 
    JOIN bus b ON c.id_bus = b.id_bus 
    WHERE YEAR(c.date) = '$selectedYear' 
    GROUP BY b.marque 
    ORDER BY total_km DESC
");
$kilometrageByMarque = $kilometrageByMarqueStmt ? $kilometrageByMarqueStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Get consumption by marque for selected year
$consumptionByMarqueStmt = $db->query("
    SELECT b.marque AS marque, SUM(c.qte_go) as total_qte 
    FROM carburant c 
    JOIN bus b ON c.id_bus = b.id_bus 
    WHERE YEAR(c.date) = '$selectedYear' 
    GROUP BY b.marque 
    ORDER BY total_qte DESC
");
$consumptionByMarque = $consumptionByMarqueStmt ? $consumptionByMarqueStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Get consumption by fuel type (GO, GSS, ESP) for selected year
$consumptionByFuelTypeStmt = $db->query("
    SELECT 
        type as fuel_type, 
        SUM(qte_go) as total_qte,
        COUNT(*) as count
    FROM carburant 
    WHERE YEAR(date) = '$selectedYear' 
    GROUP BY type 
    ORDER BY total_qte DESC
");
$consumptionByFuelType = $consumptionByFuelTypeStmt ? $consumptionByFuelTypeStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Additional KPIs for selected year
$activeBusesStmt = $db->query("SELECT COUNT(DISTINCT id_bus) as count FROM carburant WHERE YEAR(date) = '$selectedYear'");
$activeBuses = $activeBusesStmt ? (int)($activeBusesStmt->fetchColumn() ?: 0) : 0;

$avgConsumptionPerBus = $activeBuses > 0 ? $totalConsumption / $activeBuses : 0;

$lastMonthConsumptionStmt = $db->query("
    SELECT SUM(qte_go) as total_qte 
    FROM carburant 
    WHERE YEAR(date) = '$currentYear' AND MONTH(date) = '$currentMonth' - 1
");
$lastMonthConsumption = $lastMonthConsumptionStmt ? (float)($lastMonthConsumptionStmt->fetchColumn() ?: 0) : 0;

$currentMonthConsumptionStmt = $db->query("
    SELECT SUM(qte_go) as total_qte 
    FROM carburant 
    WHERE YEAR(date) = '$currentYear' AND MONTH(date) = '$currentMonth'
");
$currentMonthConsumption = $currentMonthConsumptionStmt ? (float)($currentMonthConsumptionStmt->fetchColumn() ?: 0) : 0;

$monthlyTrend = $lastMonthConsumption > 0 ? (($currentMonthConsumption - $lastMonthConsumption) / $lastMonthConsumption) * 100 : 0;

// Total oil consumption for selected year
$totalOilConsumptionStmt = $db->query("
    SELECT SUM(mo.quantity) as total_oil
    FROM maintenance_operations mo 
    JOIN maintenance_records mr ON mo.record_id = mr.id
    WHERE YEAR(mr.date) = '$selectedYear' AND mo.type = 'Huile' AND mo.quantity > 0
");
$totalOilConsumption = $totalOilConsumptionStmt ? (float)($totalOilConsumptionStmt->fetchColumn() ?: 0) : 0;

// Get status stats for Demandes (Initial Load)
$demandeStatusStmt = $db->query("SELECT etat, COUNT(*) as count FROM demande WHERE YEAR(date) = '$selectedYear' GROUP BY etat");
$demandeStatus = $demandeStatusStmt ? $demandeStatusStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Get status stats for Ordres de Travail (Initial Load)
$ordreStatusStmt = $db->query("SELECT etat, COUNT(*) as count FROM ordre WHERE YEAR(date) = '$selectedYear' GROUP BY etat");
$ordreStatus = $ordreStatusStmt ? $ordreStatusStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<style>
    .stat-card {
        @apply bg-white rounded-2xl shadow-lg p-6 border border-slate-100 hover:shadow-xl transition-shadow duration-300;
    }
    .stat-value {
        @apply text-3xl font-bold text-slate-900;
    }
    .stat-label {
        @apply text-sm font-medium text-slate-500 uppercase tracking-wide;
    }
    .trend-up {
        @apply text-emerald-600 bg-emerald-50;
    }
    .trend-down {
        @apply text-red-600 bg-red-50;
    }
    .chart-container {
        @apply bg-white rounded-2xl shadow-lg p-4 sm:p-6 border border-slate-100;
    }
    
    /* Mobile chart optimizations */
    @media (max-width: 640px) {
        .chart-container {
            padding: 1rem !important;
            border-radius: 1rem;
        }
        
        .chart-container h3 {
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .chart-container > div[style*="height"] {
            height: 300px !important;
        }
    }
    .filter-btn {
        @apply px-4 py-2 text-sm font-medium rounded-lg border transition-colors;
    }
    .filter-btn.active {
        @apply bg-brand-600 text-white border-brand-600;
    }
    .filter-btn:not(.active) {
        @apply bg-white text-slate-600 border-slate-200 hover:bg-slate-50;
    }
</style>

<div id="page-wrapper">
    <div class="mx-auto flex max-w-7xl flex-col gap-8">
        <!-- Header -->
        <div class="flex flex-col gap-2">
            <h1 class="page-title">Tableau de bord maitrise de l'energie</h1>
        </div>

        <!-- Year Filter Section -->
        <div class="flex justify-end mb-4 sm:mb-0">
            <select name="selected_year" id="selectedYear" class="w-full sm:w-40 px-3 py-2.5 sm:py-2 text-base sm:text-sm font-medium rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 min-h-[44px]">
                <?php foreach ($availableYears as $year): ?>
                    <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 2. Global Statistics & Charts - Moved to Top -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <!-- Total Kilometrage Card -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl sm:rounded-2xl shadow-xl p-4 sm:p-6 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-blue-100 text-xs sm:text-sm font-medium mb-1">Kilométrage Total</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold truncate"><?= number_format($totalKilometrage, 0, ',', ' '); ?></p>
                        <p class="text-blue-100 text-[10px] sm:text-xs mt-1 sm:mt-2">kilomètres - Année <?= $selectedYear ?></p>
                    </div>
                    <div class="bg-white/20 rounded-lg sm:rounded-xl p-2 sm:p-3 flex-shrink-0">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M18 8c0 3.613-3.869 7.429-5.393 8.795a1 1 0 0 1-1.214 0C9.87 15.429 6 11.613 6 8a6 6 0 0 1 12 0"/><circle cx="12" cy="8" r="2"/><path d="M8.714 14h-3.71a1 1 0 0 0-.948.683l-2.004 6A1 1 0 0 0 3 22h18a1 1 0 0 0 .948-1.316l-2-6a1 1 0 0 0-.949-.684h-3.712"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Consumption Card -->
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl sm:rounded-2xl shadow-xl p-4 sm:p-6 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-emerald-100 text-xs sm:text-sm font-medium mb-1">Consommation Totale</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold truncate"><?= number_format($totalConsumption, 0, ',', ' '); ?></p>
                        <p class="text-emerald-100 text-[10px] sm:text-xs mt-1 sm:mt-2">litres - Année <?= $selectedYear ?></p>
                    </div>
                    <div class="bg-white/20 rounded-lg sm:rounded-xl p-2 sm:p-3 flex-shrink-0">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 4 0v-6.998a2 2 0 0 0-.59-1.42L18 5"/><path d="M14 21V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16"/><path d="M2 21h13"/><path d="M3 9h11"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Oil Consumption Card -->
            <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl sm:rounded-2xl shadow-xl p-4 sm:p-6 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-amber-100 text-xs sm:text-sm font-medium mb-1">Huile Consommée</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold truncate"><?= number_format($totalOilConsumption, 0, ',', ' '); ?></p>
                        <p class="text-amber-100 text-[10px] sm:text-xs mt-1 sm:mt-2">litres - Année <?= $selectedYear ?></p>
                    </div>
                    <div class="bg-white/20 rounded-lg sm:rounded-xl p-2 sm:p-3 flex-shrink-0">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fuel Type KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
            <?php
            // Get consumption values by fuel type
            $goConsumption = 0;
            $gssConsumption = 0;
            $espConsumption = 0;
            foreach ($consumptionByFuelType as $fuel) {
                if ($fuel['fuel_type'] === 'GO') $goConsumption = (float)$fuel['total_qte'];
                elseif ($fuel['fuel_type'] === 'GSS') $gssConsumption = (float)$fuel['total_qte'];
                elseif ($fuel['fuel_type'] === 'ESP') $espConsumption = (float)$fuel['total_qte'];
            }
            ?>
            <!-- Gasoil (GO) Card -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl sm:rounded-2xl shadow-xl p-4 sm:p-6 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-blue-100 text-xs sm:text-sm font-medium mb-1">Gasoil (GO)</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold truncate"><?= number_format($goConsumption, 0, ',', ' '); ?></p>
                        <p class="text-blue-100 text-[10px] sm:text-xs mt-1 sm:mt-2">litres - Année <?= $selectedYear ?></p>
                    </div>
                    <div class="bg-white/20 rounded-lg sm:rounded-xl p-2 sm:p-3 relative flex-shrink-0">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 4 0v-6.998a2 2 0 0 0-.59-1.42L18 5"/><path d="M14 21V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16"/><path d="M2 21h13"/><path d="M3 9h11"/>
                        </svg>
                        <span class="absolute -top-1 -right-1 sm:-top-2 sm:-right-2 bg-white text-blue-600 text-[9px] sm:text-[10px] font-bold rounded-full px-1 sm:px-1.5 py-0.5 flex items-center justify-center">GO</span>
                    </div>
                </div>
            </div>

            <!-- Gasoil sans soufre (GSS) Card -->
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl sm:rounded-2xl shadow-xl p-4 sm:p-6 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-emerald-100 text-xs sm:text-sm font-medium mb-1">Gasoil sans soufre (GSS)</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold truncate"><?= number_format($gssConsumption, 0, ',', ' '); ?></p>
                        <p class="text-emerald-100 text-[10px] sm:text-xs mt-1 sm:mt-2">litres - Année <?= $selectedYear ?></p>
                    </div>
                    <div class="bg-white/20 rounded-lg sm:rounded-xl p-2 sm:p-3 relative flex-shrink-0">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 4 0v-6.998a2 2 0 0 0-.59-1.42L18 5"/><path d="M14 21V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16"/><path d="M2 21h13"/><path d="M3 9h11"/>
                        </svg>
                        <span class="absolute -top-1 -right-1 sm:-top-2 sm:-right-2 bg-white text-emerald-600 text-[9px] sm:text-[10px] font-bold rounded-full px-1 sm:px-1.5 py-0.5 flex items-center justify-center">GSS</span>
                    </div>
                </div>
            </div>

            <!-- Essence sans plomb (ESP) Card -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl sm:rounded-2xl shadow-xl p-4 sm:p-6 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-orange-100 text-xs sm:text-sm font-medium mb-1">Essence sans plomb (ESP)</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold truncate"><?= number_format($espConsumption, 0, ',', ' '); ?></p>
                        <p class="text-orange-100 text-[10px] sm:text-xs mt-1 sm:mt-2">litres - Année <?= $selectedYear ?></p>
                    </div>
                    <div class="bg-white/20 rounded-lg sm:rounded-xl p-2 sm:p-3 relative flex-shrink-0">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 4 0v-6.998a2 2 0 0 0-.59-1.42L18 5"/><path d="M14 21V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16"/><path d="M2 21h13"/><path d="M3 9h11"/>
                        </svg>
                        <span class="absolute -top-1 -right-1 sm:-top-2 sm:-right-2 bg-white text-orange-600 text-[9px] sm:text-[10px] font-bold rounded-full px-1 sm:px-1.5 py-0.5 flex items-center justify-center">ESP</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kilometrage and Consumption by Agency Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
            <!-- Kilometrage by Agency -->
            <div class="chart-container">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-slate-900">Kilométrage par Genre</h3>
                    <div class="text-sm text-slate-500">
                        Année <span class="chart-year"><?= $selectedYear ?></span>
                    </div>
                </div>
                <div style="height: 300px;" class="sm:h-[400px] md:h-[450px]">
                    <canvas id="kilometrageByGenreChart"></canvas>
                </div>
            </div>

            <!-- Consumption by Agency -->
            <div class="chart-container">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0 mb-4 sm:mb-6">
                    <h3 class="text-base sm:text-lg font-semibold text-slate-900">Consommation par Genre</h3>
                    <div class="text-xs sm:text-sm text-slate-500">
                        Année <span class="chart-year"><?= $selectedYear ?></span>
                    </div>
                </div>
                <div style="height: 300px;" class="sm:h-[400px] md:h-[430px]">
                    <canvas id="consumptionByGenreChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Kilométrage et Consommation par Marque -->
        <div class="grid grid-cols-1 gap-6">
            <div class="chart-container">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0 mb-4 sm:mb-6">
                    <h3 class="text-base sm:text-lg font-semibold text-slate-900">Kilométrage et Consommation par Marque</h3>
                    <div class="text-xs sm:text-sm text-slate-500">Année <span class="chart-year"><?= $selectedYear ?></span></div>
                </div>
                <div style="height: 400px;" class="sm:h-[500px] md:h-[550px]">
                    <canvas id="kilometrageByMarqueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. Fuel Consumption Over Time -->
        <div class="chart-container">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0 mb-4 sm:mb-6">
                <h3 class="text-base sm:text-lg font-semibold text-slate-900">Consommation de Carburant par Mois - <span class="chart-year"><?= $selectedYear ?></span></h3>
                <div class="text-xs sm:text-sm text-slate-500">
                    Données mensuelles
                </div>
            </div>
            <div style="height: 300px;" class="sm:h-[350px] md:h-[400px]">
                <canvas id="consumptionOverTimeChart"></canvas>
            </div>
        </div>

        <!-- 4. Kilometrage Over Time -->
        <div class="chart-container">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0 mb-4 sm:mb-6">
                <h3 class="text-base sm:text-lg font-semibold text-slate-900">Kilométrage par Mois - <span class="chart-year"><?= $selectedYear ?></span></h3>
                <div class="text-xs sm:text-sm text-slate-500">
                    Données mensuelles
                </div>
            </div>
            <div style="height: 300px;" class="sm:h-[350px] md:h-[400px]">
                <canvas id="kilometrageOverTimeChart"></canvas>
            </div>
        </div>

        <!-- Oil Statistics by Type - Full Width -->
        <div class="grid grid-cols-1 gap-6">
            <!-- Oil Statistics by Type -->
            <div class="card">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Statistiques des Huiles par Type</h2>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Quantités d'huile utilisées - Année <?= $selectedYear ?></p>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-amber-50 via-yellow-50 to-white px-4 py-2 text-sm font-semibold text-amber-700 shadow-sm">
                        <svg class="h-5 w-5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2c3 3.8 6 7.6 6 11a6 6 0 0 1-12 0c0-3.4 3-7.2 6-11Z" />
                        </svg>
                        <span><?= array_sum($oilByType['vidange']) + array_sum($oilByType['apoint']) ?> L</span>
                    </div>
                </div>
                <div style="height: 400px;">
                    <canvas id="oilByTypeChart"></canvas>
                </div>
            </div>

            <!-- Liquide Statistics by Type - Full Width -->
            <div class="card">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Statistiques des Liquides par Type</h2>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Quantités de liquides utilisées - Année <?= $selectedYear ?></p>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-purple-50 via-pink-50 to-white px-4 py-2 text-sm font-semibold text-purple-700 shadow-sm">
                        <svg class="h-5 w-5 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2c3 3.8 6 7.6 6 11a6 6 0 0 1-12 0c0-3.4 3-7.2 6-11Z" />
                        </svg>
                        <span><?= array_sum(array_column($liquideByType, 'total_quantity')) ?> L</span>
                    </div>
                </div>
                <div style="height: 400px;">
                    <canvas id="liquideByTypeChart"></canvas>
                </div>
            </div>

        <!-- Filter Operations Statistics -->
        <div class="card">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Statistiques des Filtres</h2>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Opérations de filtres par type et action - Année <?= $selectedYear ?></p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-blue-50 via-indigo-50 to-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm">
                    <svg class="h-5 w-5 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v2.586a1 1 0 0 1-.293.707l-6.414 6.414a1 1 0 0 0-.293.707V17l-4 4v-6.586a1 1 0 0 0-.293-.707L3.293 7.293A1 1 0 0 1 3 6.586V4z" />
                    </svg>
                    <span><?= array_sum(array_column($filterStats, 'count')) ?> opérations</span>
                </div>
            </div>
            <div style="height: 400px;">
                <canvas id="filterStatsChart"></canvas>
            </div>
        </div>



        </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js default configuration
    Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
    
    // Mobile-responsive font sizes
    const isMobile = window.innerWidth < 640;
    if (isMobile) {
        Chart.defaults.font.size = 11;
        Chart.defaults.plugins.legend.labels.font.size = 11;
        Chart.defaults.plugins.tooltip.titleFont.size = 12;
        Chart.defaults.plugins.tooltip.bodyFont.size = 11;
    } else {
        Chart.defaults.font.size = 12;
    }
    
    // Update chart defaults on resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const isMobileNow = window.innerWidth < 640;
            Chart.defaults.font.size = isMobileNow ? 11 : 12;
            Chart.defaults.plugins.legend.labels.font.size = isMobileNow ? 11 : 12;
            Chart.defaults.plugins.tooltip.titleFont.size = isMobileNow ? 12 : 13;
            Chart.defaults.plugins.tooltip.bodyFont.size = isMobileNow ? 11 : 12;
        }, 250);
    });
    
    // Kilometrage by Agency Chart
    const kilometrageCtx = document.getElementById('kilometrageByGenreChart').getContext('2d');
    const kilometrageChart = new Chart(kilometrageCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($kilometrageByGenre, 'genre')); ?>,
            datasets: [{
                label: 'Kilométrage',
                data: <?= json_encode(array_column($kilometrageByGenre, 'total_km')); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: isMobile ? 8 : 12,
                    cornerRadius: 8,
                    titleFont: { size: isMobile ? 11 : 13 },
                    bodyFont: { size: isMobile ? 10 : 12 },
                    callbacks: {
                        label: function(context) {
                            return 'Kilométrage: ' + context.parsed.y.toLocaleString('fr-FR') + ' km';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: isMobile ? 10 : 12 },
                        callback: function(value) {
                            return value.toLocaleString('fr-FR') + ' km';
                        }
                    }
                },
                x: {
                    ticks: {
                        font: { size: isMobile ? 10 : 12 }
                    }
                }
            }
        }
    });

    // Consumption by Agency Chart
    const consumptionCtx = document.getElementById('consumptionByGenreChart').getContext('2d');
    const consumptionData = <?= json_encode($consumptionByGenre); ?>;
    console.log('Consumption data:', consumptionData); // Debug log
    
    const consumptionLabels = consumptionData.length > 0 ? consumptionData.map(d => d.genre) : ['Aucune donnée'];
    const consumptionValues = consumptionData.length > 0 ? consumptionData.map(d => parseFloat(d.total_qte) || 0) : [1];
    console.log('Consumption values:', consumptionValues); // Debug log
    console.log('Consumption labels:', consumptionLabels); // Debug log
    
    const consumptionChart = new Chart(consumptionCtx, {
        type: 'doughnut',
        data: {
            labels: consumptionLabels,
            datasets: [{
                data: consumptionValues,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(251, 146, 60, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(236, 72, 153, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%', // reduce the ring thickness
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            console.log('Tooltip context:', context); // Debug log
                            console.log('Parsed value:', context.parsed); // Debug log
                            
                            if (context.label === 'Aucune donnée') {
                                return 'Aucune donnée de consommation disponible pour cette année';
                            }
                            
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            console.log('Total:', total); // Debug log
                            
                            if (total === 0 || isNaN(total)) {
                                return context.label + ': ' + context.parsed.toLocaleString('fr-FR') + ' L (0.0%)';
                            }
                            
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            console.log('Percentage:', percentage); // Debug log
                            
                            return context.label + ': ' + context.parsed.toLocaleString('fr-FR') + ' L (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Consumption Over Time Chart
    const consumptionTimeCtx = document.getElementById('consumptionOverTimeChart').getContext('2d');
    const monthlyData = <?= json_encode($monthlyConsumption); ?>;
    const selectedYear = <?= $selectedYear ?>;
    
    const consumptionTimeChart = new Chart(consumptionTimeCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month_name),
            datasets: [{
                label: 'Consommation (L)',
                data: monthlyData.map(d => d.total_qte),
                borderColor: 'rgba(16, 185, 129, 1)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return 'Consommation: ' + context.parsed.y.toLocaleString('fr-FR') + ' L';
                        },
                        title: function(context) {
                            return context[0].label + ' ' + selectedYear;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('fr-FR') + ' L';
                        }
                    }
                }
            }
        }
    });

    // Kilometrage Over Time Chart
    const kilometrageTimeCtx = document.getElementById('kilometrageOverTimeChart').getContext('2d');
    const monthlyKilometrageData = <?= json_encode($monthlyKilometrage); ?>;
    
    const kilometrageTimeChart = new Chart(kilometrageTimeCtx, {
        type: 'line',
        data: {
            labels: monthlyKilometrageData.map(d => d.month_name),
            datasets: [{
                label: 'Kilométrage (km)',
                data: monthlyKilometrageData.map(d => d.total_km),
                borderColor: 'rgba(59, 130, 246, 1)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return 'Kilométrage: ' + context.parsed.y.toLocaleString('fr-FR') + ' km';
                        },
                        title: function(context) {
                            return context[0].label + ' ' + selectedYear;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('fr-FR') + ' km';
                        }
                    }
                }
            }
        }
    });

    // Kilométrage et Consommation par Marque - Vertical Bar Chart with Dual Axes
    const kilometrageByMarqueCtx = document.getElementById('kilometrageByMarqueChart').getContext('2d');
    const kilometrageByMarqueData = <?= json_encode($kilometrageByMarque); ?>;
    
    const kilometrageByMarqueChart = new Chart(kilometrageByMarqueCtx, {
        type: 'bar',
        data: {
            labels: kilometrageByMarqueData.map(d => d.marque ?? 'Non spécifié'),
            datasets: [
                {
                    label: 'Kilométrage (km)',
                    data: kilometrageByMarqueData.map(d => parseFloat(d.total_km) || 0),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    barPercentage: 0.5,
                    categoryPercentage: 0.7,
                    yAxisID: 'y'
                },
                {
                    label: 'Consommation (L)',
                    data: kilometrageByMarqueData.map(d => parseFloat(d.total_qte) || 0),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    barPercentage: 0.5,
                    categoryPercentage: 0.7,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    display: true,
                    position: 'top',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.label.includes('Kilométrage')) {
                                return 'Kilométrage: ' + context.parsed.y.toLocaleString('fr-FR') + ' km';
                            } else {
                                return 'Consommation: ' + context.parsed.y.toLocaleString('fr-FR') + ' L';
                            }
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        font: {
                            size: 11,
                            weight: '500'
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: { 
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Kilométrage (km)',
                        color: 'rgba(59, 130, 246, 1)',
                        font: { weight: '600' }
                    },
                    ticks: { 
                        color: 'rgba(59, 130, 246, 1)',
                        callback: function(value) {
                            return value.toLocaleString('fr-FR');
                        }
                    },
                    grid: {
                        color: 'rgba(59, 130, 246, 0.1)'
                    }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Consommation (L)',
                        color: 'rgba(16, 185, 129, 1)',
                        font: { weight: '600' }
                    },
                    ticks: { 
                        color: 'rgba(16, 185, 129, 1)',
                        callback: function(value) {
                            return value.toLocaleString('fr-FR');
                        }
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
    window.kilometrageByMarqueChart = kilometrageByMarqueChart;

    // Oil by Type Chart (Vertical Bar Chart)
    const oilCtx = document.getElementById('oilByTypeChart').getContext('2d');
    const oilData = <?= json_encode($oilByType) ?>;
    
    // Debug: Log the oil data structure
    console.log('Oil Data Structure:', oilData);
    console.log('Labels:', oilData.labels);
    console.log('Vidange Data:', oilData.vidange);
    console.log('Apoint Data:', oilData.apoint);
    
    const oilChart = new Chart(oilCtx, {
        type: 'bar',
        data: {
            labels: oilData.labels,
            datasets: [
                {
                    label: 'Vidange',
                    data: oilData.vidange,
                    backgroundColor: 'rgba(245, 158, 11, 0.8)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                },
                {
                    label: 'Appoint',
                    data: oilData.apoint,
                    backgroundColor: 'rgba(251, 191, 36, 0.8)',
                    borderColor: 'rgba(251, 191, 36, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }
            ]
        },
        options: {
            indexAxis: 'x', // This makes it vertical bars
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' L';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantité (L)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('fr-FR') + ' L';
                        }
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });

    // Liquide by Type Chart (Horizontal Bar Chart)
    const liquideCtx = document.getElementById('liquideByTypeChart').getContext('2d');
    const liquideData = <?= json_encode($liquideByType); ?>;
    
    const liquideChart = new Chart(liquideCtx, {
        type: 'bar',
        data: {
            labels: liquideData.map(d => d.liquide_type || 'Non spécifié'),
            datasets: [{
                label: 'Quantité (L)',
                data: liquideData.map(d => parseFloat(d.total_quantity) || 0),
                backgroundColor: 'rgba(168, 85, 247, 0.8)',
                borderColor: 'rgba(168, 85, 247, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            indexAxis: 'y', // Horizontal bars
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return 'Quantité: ' + context.parsed.x.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' L';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantité (L)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('fr-FR') + ' L';
                        }
                    }
                },
                y: {
                    ticks: {
                        font: {
                            size: 11,
                            weight: '500'
                        }
                    }
                }
            }
        }
    });
    
    window.liquideChart = liquideChart;

    // Filter Statistics Chart (Horizontal Bar Chart with Moteur/Boite specification)
    const filterCtx = document.getElementById('filterStatsChart').getContext('2d');
    const filterData = <?= json_encode($filterStats); ?>;
    
    // Process filter data to group by filter type and separate actions
    const filterTypes = [...new Set(filterData.map(d => d.filter_type))];
    const changementData = filterTypes.map(type => {
        const item = filterData.find(d => d.filter_type === type && d.filter_action === 'Changement');
        return item ? item.count : 0;
    });
    const nettoyageData = filterTypes.map(type => {
        const item = filterData.find(d => d.filter_type === type && d.filter_action === 'Nettoyage');
        return item ? item.count : 0;
    });

    // Get usageFilter from the data
    const filterCategories = filterTypes.map(type => {
        const item = filterData.find(d => d.filter_type === type);
        return item ? item.usageFilter : 'Autre';
    });

    const filterChart = new Chart(filterCtx, {
        type: 'bar',
        data: {
            labels: filterTypes.map((type, index) => type + ' (' + filterCategories[index] + ')'),
            datasets: [
                {
                    label: 'Changement',
                    data: changementData,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Nettoyage',
                    data: nettoyageData,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const category = filterCategories[context.dataIndex];
                            return context.dataset.label + ': ' + context.parsed.y;
                        },
                        afterLabel: function(context) {
                            const category = filterCategories[context.dataIndex];
                            return 'Type: ' + category;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    stacked: false,
                    title: {
                        display: true,
                        text: "Nombre d'opérations"
                    }
                },
                x: {
                    stacked: false
                }
            }
        }
    });

    // Year filter functionality with dynamic chart updates
    const yearSelect = document.getElementById('selectedYear');
    if (yearSelect) {
        yearSelect.addEventListener('change', function() {
            const selectedYear = this.value;
            
            // Update all charts with new year data
            updateChartsForYear(selectedYear);
        });
    }

    // Function to update charts for selected year
    function updateChartsForYear(year) {
        // Fetch new data for the selected year
        fetch(`dashboard.php?selected_year=${year}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update Kilométrage par Agence chart
            if (window.kilometrageChart && data.kilometrageByGenre) {
                window.kilometrageChart.data.labels = data.kilometrageByGenre.map(d => d.genre);
                window.kilometrageChart.data.datasets[0].data = data.kilometrageByGenre.map(d => d.total_km);
                window.kilometrageChart.update();
            }

            // Update Consommation par Agence chart
            if (window.consumptionChart && data.consumptionByGenre) {
                window.consumptionChart.data.labels = data.consumptionByGenre.map(d => d.genre);
                window.consumptionChart.data.datasets[0].data = data.consumptionByGenre.map(d => d.total_qte);
                window.consumptionChart.update();
            }

            // Update Consommation de Carburant dans le Temps chart
            if (window.consumptionTimeChart && data.monthlyConsumption) {
                window.consumptionTimeChart.data.labels = data.monthlyConsumption.map(d => d.month_name);
                window.consumptionTimeChart.data.datasets[0].data = data.monthlyConsumption.map(d => d.total_qte);
                window.consumptionTimeChart.update();
            }

            // Update Kilometrage Over Time chart
            if (window.kilometrageTimeChart && data.monthlyKilometrage) {
                window.kilometrageTimeChart.data.labels = data.monthlyKilometrage.map(d => d.month_name);
                window.kilometrageTimeChart.data.datasets[0].data = data.monthlyKilometrage.map(d => d.total_km);
                window.kilometrageTimeChart.update();
            }

            // Update Kilométrage par Marque vertical bar chart (with consumption)
            if (window.kilometrageByMarqueChart && data.kilometrageByMarque) {
                window.kilometrageByMarqueChart.data.labels = data.kilometrageByMarque.map(d => d.marque || 'Non spécifié');
                window.kilometrageByMarqueChart.data.datasets[0].data = data.kilometrageByMarque.map(d => parseFloat(d.total_km) || 0);
                window.kilometrageByMarqueChart.data.datasets[1].data = data.kilometrageByMarque.map(d => parseFloat(d.total_qte) || 0);
                window.kilometrageByMarqueChart.update();
            }

            // Update Oil by Type chart
            if (window.oilChart && data.oilByType) {
                window.oilChart.data.labels = data.oilByType.labels;
                window.oilChart.data.datasets[0].data = data.oilByType.vidange;
                window.oilChart.data.datasets[1].data = data.oilByType.apoint;
                window.oilChart.update();
            }

            // Update Liquide by Type chart
            if (window.liquideChart && data.liquideByType) {
                window.liquideChart.data.labels = data.liquideByType.map(d => d.liquide_type || 'Non spécifié');
                window.liquideChart.data.datasets[0].data = data.liquideByType.map(d => parseFloat(d.total_quantity) || 0);
                window.liquideChart.update();
            }

            // Update Filter Statistics chart
            if (window.filterChart && data.filterStats) {
                const filterData = data.filterStats;
                const filterTypes = [...new Set(filterData.map(d => d.filter_type))];
                const changementData = filterTypes.map(type => {
                    const item = filterData.find(d => d.filter_type === type && d.filter_action === 'Changement');
                    return item ? item.count : 0;
                });
                const nettoyageData = filterTypes.map(type => {
                    const item = filterData.find(d => d.filter_type === type && d.filter_action === 'Nettoyage');
                    return item ? item.count : 0;
                });
                
                // Get usageFilter from the data
                const filterCategories = filterTypes.map(type => {
                    const item = filterData.find(d => d.filter_type === type);
                    return item ? item.usageFilter : 'Autre';
                });
                
                window.filterChart.data.labels = filterTypes.map((type, index) => type + ' (' + filterCategories[index] + ')');
                window.filterChart.data.datasets[0].data = changementData;
                window.filterChart.data.datasets[1].data = nettoyageData;
                window.filterChart.update();
            }



            // Update year display in chart titles
            const yearElements = document.querySelectorAll('.chart-year');
            yearElements.forEach(el => {
                el.textContent = year;
            });
        })
        .catch(error => {
            console.error('Error fetching chart data:', error);
            // Fallback: reload the page
            window.location.href = `dashboard.php?selected_year=${year}`;
        });
    }

    // Store chart instances globally for access
    window.kilometrageChart = kilometrageChart;
    window.consumptionChart = consumptionChart;
    window.consumptionTimeChart = consumptionTimeChart;
    window.kilometrageTimeChart = kilometrageTimeChart;
    window.oilChart = oilChart;
    window.filterChart = filterChart;
    window.kilometrageByMarqueChart = kilometrageByMarqueChart;




    // Filter buttons functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const period = this.dataset.period;
            console.log('Selected period:', period);
            
            // Here you could implement different data filtering based on the selected period
            // For now, just update the button text to reflect the selected year
            if (period === 'year') {
                this.textContent = 'Année ' + selectedYear;
            }
        });
    });
});
</script>

</body>
</html>
