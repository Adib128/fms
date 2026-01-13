<?php
// Initialize config and database BEFORE checking for AJAX
require_once __DIR__ . '/app/security.php';
require_once __DIR__ . '/config.php';

// Get vehicle ID from URL
$id_bus = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id_bus) {
    header('Location: ' . url('liste-vehicule'));
    exit;
}

// Handle AJAX request for filtered chart data BEFORE including header.php
if (isset($_GET['selected_year']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    try {
        $selectedYear = $_GET['selected_year'];
        
        // Kilometrage evolution by month for selected year
        $km_evolution_stmt = $db->query("
            SELECT DATE_FORMAT(STR_TO_DATE(date_kilometrage, '%Y-%m-%d'), '%Y-%m') as month, SUM(kilometrage) as total 
            FROM kilometrage 
            WHERE id_bus = '$id_bus' AND YEAR(STR_TO_DATE(date_kilometrage, '%Y-%m-%d')) = '$selectedYear'
            GROUP BY DATE_FORMAT(STR_TO_DATE(date_kilometrage, '%Y-%m-%d'), '%Y-%m') 
            ORDER BY month ASC
        ");
        $km_evolution = $km_evolution_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $km_dates = [];
        $km_values = [];
        foreach ($km_evolution as $km) {
            if ($km['month']) {
                $km_dates[] = date('M Y', strtotime($km['month'] . '-01'));
                $km_values[] = (float)$km['total'];
            }
        }
        
        // Monthly consumption for selected year
        $monthly_conso_stmt = $db->query("
            SELECT DATE_FORMAT(date, '%Y-%m') as month, SUM(qte_go) as total 
            FROM carburant 
            WHERE id_bus = '$id_bus' AND YEAR(date) = '$selectedYear'
            GROUP BY DATE_FORMAT(date, '%Y-%m') 
            ORDER BY month ASC
        ");
        $monthly_conso = $monthly_conso_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $conso_months = [];
        $conso_values = [];
        foreach ($monthly_conso as $mc) {
            if ($mc['month']) {
                $conso_months[] = date('M Y', strtotime($mc['month'] . '-01'));
                $conso_values[] = (float)$mc['total'];
            }
        }
        
        // Vidange Index for selected year
        $vidange_index_stmt = $db->query("
            SELECT date_vidange, indexe 
            FROM vidange 
            WHERE id_bus = '$id_bus' AND YEAR(STR_TO_DATE(date_vidange, '%Y-%m-%d')) = '$selectedYear'
            ORDER BY date_vidange ASC
        ");
        $vidange_index_data = $vidange_index_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $vidange_index_dates = [];
        $vidange_index_values = [];
        foreach ($vidange_index_data as $v) {
            if ($v['date_vidange']) {
                $vidange_index_dates[] = date('d/m/Y', strtotime($v['date_vidange']));
                $vidange_index_values[] = (float)$v['indexe'];
            }
        }
        
        // Vidange Kilometrage for selected year
        $vidange_km_stmt = $db->query("
            SELECT date_vidange, kilometrage 
            FROM vidange 
            WHERE id_bus = '$id_bus' AND YEAR(STR_TO_DATE(date_vidange, '%Y-%m-%d')) = '$selectedYear'
            ORDER BY date_vidange ASC
        ");
        $vidange_km_data = $vidange_km_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $vidange_dates = [];
        $vidange_km_values = [];
        foreach ($vidange_km_data as $v) {
            if ($v['date_vidange']) {
                $vidange_dates[] = date('d/m/Y', strtotime($v['date_vidange']));
                $vidange_km_values[] = (float)$v['kilometrage'];
            }
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'km_dates' => $km_dates,
            'km_values' => $km_values,
            'conso_months' => $conso_months,
            'conso_values' => $conso_values,
            'vidange_index_dates' => $vidange_index_dates,
            'vidange_index_values' => $vidange_index_values,
            'vidange_dates' => $vidange_dates,
            'vidange_km_values' => $vidange_km_values
        ]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Now include header for normal page rendering
require 'header.php';

// Fetch vehicle information
$query_bus = $db->query("
    SELECT b.*, s.lib as station_name, 
           om.name as huile_moteur_name, om.designation as huile_moteur_desc,
           ob.name as huile_boite_name, ob.designation as huile_boite_desc,
           op.name as huile_pont_name, op.designation as huile_pont_desc
    FROM bus b 
    LEFT JOIN station s ON b.id_station = s.id_station 
    LEFT JOIN oil_types om ON b.huile_moteur = om.id
    LEFT JOIN oil_types ob ON b.huile_boite_vitesse = ob.id
    LEFT JOIN oil_types op ON b.huile_pont = op.id
    WHERE b.id_bus = '$id_bus'
");
$vehicle = $query_bus->fetch(PDO::FETCH_ASSOC);

if (!$vehicle) {
    header('Location: ' . url('liste-vehicule'));
    exit;
}

// Fetch statistics
// Total Kilometrage
$query_km = $db->query("SELECT SUM(kilometrage) as total_km FROM kilometrage WHERE id_bus = '$id_bus'");
$total_km_data = $query_km->fetch(PDO::FETCH_ASSOC);
$total_km = $total_km_data['total_km'] ?? 0;

// Total Vidanges
$query_vidange_count = $db->query("SELECT COUNT(*) as total_vidanges FROM vidange WHERE id_bus = '$id_bus'");
$vidange_count_data = $query_vidange_count->fetch(PDO::FETCH_ASSOC);
$total_vidanges = $vidange_count_data['total_vidanges'] ?? 0;

// Total Consommation
$query_conso = $db->query("SELECT SUM(qte_go) as total_conso FROM carburant WHERE id_bus = '$id_bus'");
$conso_data = $query_conso->fetch(PDO::FETCH_ASSOC);
$total_consommation = $conso_data['total_conso'] ?? 0;

// Average Consumption
$query_km_for_avg = $db->query("SELECT index_km FROM carburant WHERE id_bus = '$id_bus' ORDER BY date ASC");
$km_indexes = $query_km_for_avg->fetchAll(PDO::FETCH_COLUMN);
$km_diff = 0;
if (count($km_indexes) > 1) {
    $km_diff = end($km_indexes) - reset($km_indexes);
}
$avg_consumption = ($km_diff > 0 && $total_consommation > 0) ? round(($total_consommation / $km_diff) * 100, 2) : 0;

// Fetch data for charts - filtered by current year by default
$current_year = date('Y');

// Kilometrage evolution by month for current year
$query_km_evolution = $db->query("
    SELECT DATE_FORMAT(STR_TO_DATE(date_kilometrage, '%Y-%m-%d'), '%Y-%m') as month, SUM(kilometrage) as total 
    FROM kilometrage 
    WHERE id_bus = '$id_bus' AND YEAR(STR_TO_DATE(date_kilometrage, '%Y-%m-%d')) = '$current_year'
    GROUP BY DATE_FORMAT(STR_TO_DATE(date_kilometrage, '%Y-%m-%d'), '%Y-%m') 
    ORDER BY month ASC
");
$km_evolution = $query_km_evolution->fetchAll(PDO::FETCH_ASSOC);

$km_dates = [];
$km_values = [];
foreach ($km_evolution as $km) {
    if ($km['month']) {
        $km_dates[] = date('M Y', strtotime($km['month'] . '-01'));
        $km_values[] = (float)$km['total'];
    }
}

// Monthly consumption for current year
$query_monthly_conso = $db->query("
    SELECT DATE_FORMAT(date, '%Y-%m') as month, SUM(qte_go) as total 
    FROM carburant 
    WHERE id_bus = '$id_bus' AND YEAR(date) = '$current_year'
    GROUP BY DATE_FORMAT(date, '%Y-%m') 
    ORDER BY month ASC
");
$monthly_conso = $query_monthly_conso->fetchAll(PDO::FETCH_ASSOC);

$conso_months = [];
$conso_values = [];
foreach ($monthly_conso as $mc) {
    if ($mc['month']) {
        $conso_months[] = date('M Y', strtotime($mc['month'] . '-01'));
        $conso_values[] = (float)$mc['total'];
    }
}

// Vidange timeline - Kilometrage (all years)
$query_vidange_timeline = $db->query("SELECT date_vidange, kilometrage FROM vidange WHERE id_bus = '$id_bus' ORDER BY date_vidange ASC");
$vidange_timeline = $query_vidange_timeline->fetchAll(PDO::FETCH_ASSOC);

$vidange_dates = [];
$vidange_km_values = [];
foreach ($vidange_timeline as $v) {
    if ($v['date_vidange']) {
        $vidange_dates[] = date('d/m/Y', strtotime($v['date_vidange']));
        $vidange_km_values[] = (float)$v['kilometrage'];
    }
}

// Vidange timeline - Index (all years)
$query_vidange_index = $db->query("SELECT date_vidange, indexe FROM vidange WHERE id_bus = '$id_bus' ORDER BY date_vidange ASC");
$vidange_index_timeline = $query_vidange_index->fetchAll(PDO::FETCH_ASSOC);

$vidange_index_dates = [];
$vidange_index_values = [];
foreach ($vidange_index_timeline as $v) {
    if ($v['date_vidange']) {
        $vidange_index_dates[] = date('d/m/Y', strtotime($v['date_vidange']));
        $vidange_index_values[] = (float)$v['indexe'];
    }
}
?>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Détails du Véhicule</h1>
                        <p class="mt-2 text-sm text-gray-600">Informations complètes et statistiques pour <?php echo htmlspecialchars($vehicle['matricule_interne']); ?></p>
                    </div>
                    <a href="<?= url('liste-vehicule') ?>" class="btn btn-secondary">
                        <svg class="h-5 w-5 inline-block mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Retour à la liste
                    </a>
                </div>
            </div>

            <!-- Vehicle Information Card -->
            <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Informations Complètes du Véhicule</h2>
                
                <!-- Basic Information Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-700 mb-4 flex items-center">
                        <svg class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Informations Générales
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-500 mb-1">Matricule Interne</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($vehicle['matricule_interne']); ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-500 mb-1">Matricule Externe</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($vehicle['matricule']); ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-500 mb-1">Type/Gendre</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($vehicle['type']); ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-500 mb-1">Marque</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($vehicle['marque']); ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-500 mb-1">Type Carburant</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($vehicle['carburant_type']); ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-500 mb-1">État</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($vehicle['etat']); ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-500 mb-1">Contenance Réservoir</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $vehicle['contenance_reservoir'] ? htmlspecialchars($vehicle['contenance_reservoir']) . ' L' : '-'; ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-500 mb-1">Date de mise en circulation</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $vehicle['date_mise_circulation'] ? date('d/m/Y', strtotime($vehicle['date_mise_circulation'])) : '-'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Technical Specifications Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-700 mb-4 flex items-center">
                        <svg class="h-5 w-5 mr-2 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        Caractéristiques Techniques
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-500 mb-1">Fréquence Vidange Moteur</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo number_format($vehicle['freq_vidange_moteur'], 0, ',', ' '); ?> <span class="text-xs font-normal">km</span></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-500 mb-1">Standard Consommation</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo $vehicle['conso']; ?> <span class="text-xs font-normal">L/100km</span></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-500 mb-1">Fréquence Vidange Boite</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo number_format($vehicle['freq_vidange_boite'], 0, ',', ' '); ?> <span class="text-xs font-normal">km</span></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-500 mb-1">Fréquence Vidange Pont</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo number_format($vehicle['freq_vidange_pont'], 0, ',', ' '); ?> <span class="text-xs font-normal">km</span></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-500 mb-1">Standard Consommation Huile</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo $vehicle['conso_huile']; ?> <span class="text-xs font-normal">L/100km</span></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-500 mb-1">Huile Moteur</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($vehicle['huile_moteur_name'] ?? 'N/A'); ?> <span class="text-xs font-normal">(<?php echo htmlspecialchars($vehicle['huile_moteur_desc'] ?? ''); ?>)</span></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-500 mb-1">Huile Boite Vitesse</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($vehicle['huile_boite_name'] ?? 'N/A'); ?> <span class="text-xs font-normal">(<?php echo htmlspecialchars($vehicle['huile_boite_desc'] ?? ''); ?>)</span></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-500 mb-1">Huile Pont</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($vehicle['huile_pont_name'] ?? 'N/A'); ?> <span class="text-xs font-normal">(<?php echo htmlspecialchars($vehicle['huile_pont_desc'] ?? ''); ?>)</span></p>
                        </div>
                    </div>
                </div>

                <!-- Assignment & Status Section -->
                <div>
                    <h3 class="text-lg font-medium text-gray-700 mb-4 flex items-center">
                        <svg class="h-5 w-5 mr-2 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Affectation & Statut
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-500 mb-1">Agence d'Affectation</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($vehicle['station_name'] ?? 'Non assignée'); ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-600 mb-1">Kilométrage (Exp)</p>
                            <p class="text-sm font-semibold text-gray-900">
                                <?php
                                $id_bus = $vehicle['id_bus'];
                                $select = $db->query("SELECT * FROM total_kilometrage WHERE id_bus='$id_bus'");
                                foreach ($select as $item) {
                                    $kilometrage = $item["kilometrage"];
                                }
                                echo isset($kilometrage) ? number_format($kilometrage, 0, ',', ' ') . ' km' : '0 km';
                                ?>
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-gray-600 mb-1">Kilométrage (Index)</p>
                            <p class="text-sm font-semibold text-gray-900">
                                <?php
                                $select = $db->query("SELECT MAX(index_km) AS mx FROM carburant WHERE id_bus='$id_bus'");
                                foreach ($select as $item) {
                                    $max_carburant = $item["mx"];
                                }
                                echo isset($max_carburant) ? number_format($max_carburant, 0, ',', ' ') . ' km' : '0 km';
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Total Kilométrage</p>
                            <p class="text-3xl font-bold mt-2"><?php echo number_format($total_km, 0, ',', ' '); ?> <span class="text-lg font-normal">km</span></p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-emerald-100 text-sm font-medium">Total Vidanges</p>
                            <p class="text-3xl font-bold mt-2"><?php echo $total_vidanges; ?></p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Total Consommation</p>
                            <p class="text-3xl font-bold mt-2"><?php echo number_format($total_consommation, 0, ',', ' '); ?> <span class="text-lg font-normal">L</span></p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 4 0v-6.998a2 2 0 0 0-.59-1.42L18 5"/>
                                <path d="M14 21V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16"/>
                                <path d="M2 21h13"/>
                                <path d="M3 9h11"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-2xl p-6 text-white shadow-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-amber-100 text-sm font-medium">Moyenne Consommation</p>
                            <p class="text-3xl font-bold mt-2"><?php echo $avg_consumption; ?> <span class="text-lg font-normal">L/100km</span></p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15.6 2.7a10 10 0 1 0 5.7 5.7"/>
                                <circle cx="12" cy="12" r="2"/>
                                <path d="M13.4 10.6 19 5"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Year Filter for Charts -->
            <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-800">Graphiques Statistiques</h3>
                    <div class="flex items-center gap-3">
                        <label for="yearFilter" class="text-sm font-medium text-gray-700">Filtrer par année:</label>
                        <select id="yearFilter" class="form-control" style="width: 150px;">
                            <?php
                            // Show years from 2019 to current year
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= 2019; $year--) {
                                $selected = ($year == $current_year) ? 'selected' : '';
                                echo "<option value='$year' $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Kilometrage Evolution Chart -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6">Évolution du Kilométrage</h3>
                    <div class="relative" style="height: 300px;">
                        <canvas id="kilometrageChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Consumption Chart -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6">Consommation par Mois</h3>
                    <div class="relative" style="height: 300px;">
                        <canvas id="consommationChart"></canvas>
                    </div>
                </div>
            </div>



            <!-- Vidange Kilometrage Chart -->
            <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-6">Historique des Vidanges (Kilométrage)</h3>
                <div class="relative" style="height: 300px;">
                    <canvas id="vidangeKmChart"></canvas>
                </div>
            </div>

            <!-- Data Tables Section -->
            <div class="space-y-8">
                <!-- Consommation Table -->
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Historique Consommation</h3>
                    </div>
                    <div class="panel-body">
                        <table id="consommationTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Quantité (L)</th>
                                    <th>Index KM</th>
                                    <th>Type</th>
                                    <th>Chauffeur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_conso_list = $db->query("
                                    SELECT c.*, ch.nom_prenom 
                                    FROM carburant c 
                                    LEFT JOIN chauffeur ch ON c.id_chauffeur = ch.id_chauffeur 
                                    WHERE c.id_bus = '$id_bus' 
                                    ORDER BY c.date DESC
                                ");
                                while ($c_row = $query_conso_list->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y', strtotime($c_row['date'])) . "</td>";
                                    echo "<td>" . $c_row['qte_go'] . " L</td>";
                                    echo "<td>" . number_format($c_row['index_km'], 0, ',', ' ') . " km</td>";
                                    echo "<td>" . htmlspecialchars($c_row['type']) . "</td>";
                                    echo "<td>" . htmlspecialchars($c_row['nom_prenom'] ?? 'N/A') . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vidange Table -->
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Historique Vidange</h3>
                    </div>
                    <div class="panel-body">
                        <table id="vidangeTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Kilométrage</th>
                                    <th>Index</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_vidange_list = $db->query("
                                    SELECT v.* 
                                    FROM vidange v 
                                    WHERE v.id_bus = '$id_bus' 
                                    ORDER BY v.date_vidange DESC
                                ");
                                while ($v_row = $query_vidange_list->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y', strtotime($v_row['date_vidange'])) . "</td>";
                                    echo "<td>" . number_format($v_row['kilometrage'], 0, ',', ' ') . " km</td>";
                                    echo "<td>" . number_format($v_row['indexe'], 0, ',', ' ') . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kilometrage Table -->
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Historique Kilométrage</h3>
                    </div>
                    <div class="panel-body">
                        <table id="kilometrageTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Kilométrage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_km_list = $db->query("
                                    SELECT k.* 
                                    FROM kilometrage k 
                                    WHERE k.id_bus = '$id_bus' 
                                    ORDER BY k.date_kilometrage DESC
                                ");
                                while ($km_row = $query_km_list->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y', strtotime($km_row['date_kilometrage'])) . "</td>";
                                    echo "<td>" . number_format($km_row['kilometrage'], 0, ',', ' ') . " km</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Immobilisation Table -->
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Historique Immobilisations</h3>
                    </div>
                    <div class="panel-body">
                        <table id="immobilisationTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Date Début</th>
                                    <th>Date Fin</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_immob = $db->query("
                                    SELECT * FROM immobilisation 
                                    WHERE id_vehicule = '$id_bus' 
                                    ORDER BY start_date DESC
                                ");
                                while ($i_row = $query_immob->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y', strtotime($i_row['start_date'])) . "</td>";
                                    echo "<td>" . ($i_row['end_date'] ? date('d/m/Y', strtotime($i_row['end_date'])) : '<span class="badge bg-amber-100 text-amber-800">En cours</span>') . "</td>";
                                    echo "<td>" . htmlspecialchars($i_row['commentaire'] ?? '-') . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Demande Table -->
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Historique Demandes</h3>
                    </div>
                    <div class="panel-body">
                        <table id="demandeTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_demande = $db->query("
                                    SELECT * FROM demande 
                                    WHERE id_vehicule = '$id_bus' 
                                    ORDER BY date DESC
                                ");
                                while ($d_row = $query_demande->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($d_row['numero']) . "</td>";
                                    echo "<td>" . date('d/m/Y', strtotime($d_row['date'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($d_row['description']) . "</td>";
                                    echo "<td>" . htmlspecialchars($d_row['etat']) . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Intervention Table -->
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Historique Interventions</h3>
                    </div>
                    <div class="panel-body">
                        <table id="interventionTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Intervention</th>
                                    <th>N° Ordre</th>
                                    <th>État Ordre</th>
                                    <th>Date Réalisation</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_int = $db->query("
                                    SELECT oi.*, i.libelle as intervention_nom, o.numero as ordre_numero, o.etat as ordre_etat
                                    FROM ordre_intervention oi
                                    JOIN intervention i ON oi.id_intervention = i.id
                                    JOIN ordre o ON oi.id_ordre = o.id
                                    JOIN demande d ON o.id_demande = d.id
                                    WHERE d.id_vehicule = '$id_bus'
                                    ORDER BY oi.realised_at DESC
                                ");
                                while ($int_row = $query_int->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($int_row['intervention_nom']) . "</td>";
                                    echo "<td>" . htmlspecialchars($int_row['ordre_numero']) . "</td>";
                                    echo "<td>" . htmlspecialchars($int_row['ordre_etat']) . "</td>";
                                    echo "<td>" . ($int_row['realised_at'] ? date('d/m/Y H:i', strtotime($int_row['realised_at'])) : '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($int_row['status']) . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Articles Table -->
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Historique Articles utilisés</h3>
                    </div>
                    <div class="panel-body">
                        <table id="articleTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Désignation</th>
                                    <th>Quantité</th>
                                    <th>Intervention</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_art = $db->query("
                                    SELECT oia.*, a.code, a.designiation, i.libelle as intervention_nom, oi.realised_at
                                    FROM ordre_intervention_article oia
                                    JOIN article a ON oia.id_article = a.id
                                    JOIN ordre_intervention oi ON oia.id_ordre_intervention = oi.id
                                    JOIN intervention i ON oi.id_intervention = i.id
                                    JOIN ordre o ON oi.id_ordre = o.id
                                    JOIN demande d ON o.id_demande = d.id
                                    WHERE d.id_vehicule = '$id_bus'
                                    ORDER BY oi.realised_at DESC
                                ");
                                while ($art_row = $query_art->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($art_row['code']) . "</td>";
                                    echo "<td>" . htmlspecialchars($art_row['designiation']) . "</td>";
                                    echo "<td>" . $art_row['quantite'] . "</td>";
                                    echo "<td>" . htmlspecialchars($art_row['intervention_nom']) . "</td>";
                                    echo "<td>" . ($art_row['realised_at'] ? date('d/m/Y', strtotime($art_row['realised_at'])) : '-') . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- jQuery (required for DataTables) -->
<script src="/js/jquery.js"></script>

<!-- DataTables Scripts -->
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.print.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTables with export buttons
    const tableConfig = {
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'print',
                text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Imprimer',
                className: 'btn btn-indigo btn-sm'
            },
            {
                extend: 'excelHtml5',
                text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg> Excel',
                className: 'btn btn-emerald btn-sm'
            },
            {
                extend: 'pdfHtml5',
                text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg> PDF',
                className: 'btn btn-red btn-sm'
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/French.json'
        }
    };

    $('#kilometrageTable').DataTable(tableConfig);
    $('#vidangeTable').DataTable(tableConfig);
    $('#consommationTable').DataTable(tableConfig);
    $('#immobilisationTable').DataTable(tableConfig);
    $('#demandeTable').DataTable(tableConfig);
    $('#interventionTable').DataTable(tableConfig);
    $('#articleTable').DataTable(tableConfig);

    // Initialize Charts
    console.log('Initializing charts...');
    console.log('KM Data:', <?php echo json_encode($km_dates); ?>, <?php echo json_encode($km_values); ?>);
    console.log('Conso Data:', <?php echo json_encode($conso_months); ?>, <?php echo json_encode($conso_values); ?>);
    console.log('Vidange KM Data:', <?php echo json_encode($vidange_dates); ?>, <?php echo json_encode($vidange_km_values); ?>);
    console.log('Vidange Index Data:', <?php echo json_encode($vidange_index_dates); ?>, <?php echo json_encode($vidange_index_values); ?>);
    
    // Declare chart variables at the top so they're accessible to the year filter handler
    let kmChart, consoChart, vidangeIndexChart, vidangeKmChart;
    
    // Kilometrage Evolution Chart
    const kmCtx = document.getElementById('kilometrageChart');
    if (kmCtx) {
        kmChart = new Chart(kmCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($km_dates); ?>,
                datasets: [{
                    label: 'Kilométrage',
                    data: <?php echo json_encode($km_values); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kilométrage (km)'
                        }
                    }
                }
            }
        });
        console.log('KM Chart created:', kmChart);
    } else {
        console.error('kilometrageChart canvas not found');
    }

    // Monthly Consumption Chart
    const consoCtx = document.getElementById('consommationChart');
    if (consoCtx) {
        consoChart = new Chart(consoCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($conso_months); ?>,
                datasets: [{
                    label: 'Consommation (L)',
                    data: <?php echo json_encode($conso_values); ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Litres'
                        }
                    }
                }
            }
        });
        console.log('Conso Chart created:', consoChart);
    } else {
        console.error('consommationChart canvas not found');
    }



    // Vidange Kilometrage Chart
    const vidangeKmCtx = document.getElementById('vidangeKmChart');
    if (vidangeKmCtx) {
        vidangeKmChart = new Chart(vidangeKmCtx.getContext('2d'), {
            type: 'scatter',
            data: {
                labels: <?php echo json_encode($vidange_dates); ?>,
                datasets: [{
                    label: 'Kilométrage à la vidange',
                    data: <?php echo json_encode(array_map(function($date, $value) {
                        return ['x' => $date, 'y' => $value];
                    }, $vidange_dates, $vidange_km_values)); ?>,
                    backgroundColor: 'rgba(245, 158, 11, 0.8)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 2,
                    pointRadius: 8,
                    pointHoverRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        type: 'category',
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kilométrage (km)'
                        }
                    }
                }
            }
        });
        console.log('Vidange KM Chart created:', vidangeKmChart);
    } else {
        console.error('vidangeKmChart canvas not found');
    }
    
    // Year Filter Handler
    $('#yearFilter').on('change', function() {
        const selectedYear = $(this).val();
        const vehicleId = <?php echo $id_bus; ?>;
        
        // Fetch filtered data via AJAX
        $.ajax({
            url: window.location.pathname,
            method: 'GET',
            data: {
                id: vehicleId,
                selected_year: selectedYear
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataType: 'json',
            success: function(data) {
                console.log('Filtered data received:', data);
                
                // Check if request was successful
                if (data.success === false) {
                    console.error('Server error:', data.error);
                    alert('Erreur: ' + data.error);
                    return;
                }
                
                // Update Kilometrage Chart
                if (kmChart) {
                    kmChart.data.labels = data.km_dates;
                    kmChart.data.datasets[0].data = data.km_values;
                    kmChart.update();
                }
                
                // Update Consumption Chart
                if (consoChart) {
                    consoChart.data.labels = data.conso_months;
                    consoChart.data.datasets[0].data = data.conso_values;
                    consoChart.update();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error Details:');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                console.error('Status Code:', xhr.status);
                
                let errorMsg = 'Erreur lors du chargement des données filtrées';
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMsg += ': ' + response.error;
                        }
                    } catch(e) {
                        errorMsg += ' (Status: ' + xhr.status + ')';
                    }
                }
                alert(errorMsg);
            }
        });
    });
});
</script>

</body>
</html>
