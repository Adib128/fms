<?php
require 'header.php' ?>

<style>
    tfoot {
        display: table-header-group;
    }
    tfoot input {
        width: 100%;
        padding: 3px;
        box-sizing: border-box;
    }
</style>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="container">
            <div class="mx-auto flex flex-col gap-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="page-title">Rapport de consommation par marque</h1>
                    </div>
                </div>

                <!-- Search Panel -->
                <div class="panel">
                    <div class="panel-heading">
                        <span>Critères de recherche</span>
                        <span class="badge">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            Filtres
                        </span>
                    </div>
                    <div class="panel-body">
                        <form method="POST" action="<?= url('rapport-consommation-marque') ?>" role="form" id="search_form" class="search-form">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Date Debut Field -->
                                <div class="space-y-2">
                                    <label for="date_debut" class="block text-sm font-medium text-gray-700">Date début</label>
                                    <input type="date" name="date_debut" id="date_debut" 
                                        class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                        placeholder="Date début" required
                                        value="<?php echo isset($_POST['date_debut']) ? $_POST['date_debut'] : ''; ?>">
                                </div>
                                
                                <!-- Date Fin Field -->
                                <div class="space-y-2">
                                    <label for="date_fin" class="block text-sm font-medium text-gray-700">Date fin</label>
                                    <input type="date" name="date_fin" id="date_fin" 
                                        class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                        placeholder="Date fin" required
                                        value="<?php echo isset($_POST['date_fin']) ? $_POST['date_fin'] : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Search Actions -->
                            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 mt-6">
                                <button type="button" id="reset_search" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                    <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                        <path d="M3 3v5h5"/>
                                    </svg>
                                    Réinitialiser
                                </button>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                    <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <path d="m21 21-4.35-4.35"></path>
                                    </svg>
                                    Chercher
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                if ($_SERVER["REQUEST_METHOD"] == 'POST') {

                    $date_debut = $_POST["date_debut"];
                    $date_fin = $_POST["date_fin"];
                    
                    // Process data by marque
                    function calculDiff($tab) {
                        $diff = 0; 
                        $ln = count($tab) - 1;
                        $diff = $tab[$ln] - $tab[0];
                        return $diff;
                    }

                    $query_bus = $db->query("SELECT DISTINCT marque FROM bus WHERE marque IS NOT NULL AND marque != ''");
                    $liste_bus = $query_bus->fetchAll(PDO::FETCH_ASSOC);

                    $tab_marque = array();
                    foreach ($liste_bus as $item) {
                        $tab_marque[] = $item["marque"];
                    }
                    
                    $tab_all = array();
                    $qte_totale = 0; 
                    $tab_carburant = array();
                    $total_km_exp = 0;
                    
                    foreach ($tab_marque as $item) {
                        $query_carburant = $db->query(
                            "SELECT * FROM carburant INNER JOIN bus ON carburant.id_bus=bus.id_bus WHERE bus.marque = '$item' AND date >= '$date_debut' AND date <= '$date_fin' ORDER BY id_carburant ASC"
                        );
                        $liste_carburant = $query_carburant->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($liste_carburant as $key => $value) {
                            $tab_carburant[] = $value["index_km"];
                            $qte_totale = $qte_totale + $value["qte_go"];
                        }
                       
                        $tab_diff = $tab_carburant;
                        sort($tab_diff);

                        if (count($tab_diff) != 0) {
                            $total_km = calculDiff($tab_diff);
                        } else {
                            $total_km = 0;
                        }
                        
                        if ($qte_totale != 0 && $total_km != 0) {
                            $moy = ($qte_totale / $total_km) * 100;
                            $moy = round($moy, 2);
                        } else {
                            $moy = 0;
                        }
                        
                        $query_km = $db->query("SELECT SUM(kilometrage) as total_km FROM kilometrage INNER JOIN bus ON bus.id_bus=kilometrage.id_bus WHERE bus.marque='$item' AND date_kilometrage >= '$date_debut' AND date_kilometrage <= '$date_fin'");
                        $km_result = $query_km->fetch(PDO::FETCH_ASSOC);
                        $total_km_exp = $km_result['total_km'] ? $km_result['total_km'] : 0;
                        
                        if ($qte_totale != 0 && $total_km_exp != 0) {
                            $moy_exp = ($qte_totale / $total_km_exp) * 100;
                            $moy_exp = round($moy_exp, 2);
                        } else {
                            $moy_exp = 0;
                        }

                        $tab_all[$item]["moy"] = $moy;
                        $tab_all[$item]["km_total"] = $total_km;
                        $tab_all[$item]["qte_totale"] = $qte_totale;
                        $tab_all[$item]["marque"] = $item;
                        $tab_all[$item]["total_km_exp"] = $total_km_exp;
                        $tab_all[$item]["moy_exp"] = $moy_exp;
                        
                        $qte_totale = 0; $total_km = 0; $moy = 0; $moy_exp = 0; 
                        $tab_carburant = $tab_diff = array();
                    }
                    
                    // Prepare chart data
                    $marques_chart = array();
                    $kilometrage_chart = array();
                    $consommation_chart = array();
                    
                    foreach ($tab_all as $key => $value) {
                        if (!empty($value["marque"])) {
                            $marques_chart[] = $value["marque"];
                            $kilometrage_chart[] = $value["total_km_exp"];
                            $consommation_chart[] = $value["qte_totale"];
                        }
                    }
                    ?>
                    
                    <!-- Kilométrage par Marque -->
                    <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Kilométrage par Marque</h3>
                                <p class="text-xs text-gray-400">Distance parcourue par marque de véhicule</p>
                            </div>
                            <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-blue-50 via-indigo-50 to-white px-3 py-1.5 text-sm font-semibold text-blue-700 shadow-sm">
                                <svg class="h-4 w-4 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8c0 3.613-3.869 7.429-5.393 8.795a1 1 0 0 1-1.214 0C9.87 15.429 6 11.613 6 8a6 6 0 0 1 12 0"/><circle cx="12" cy="8" r="2"/></svg>
                                <span><?= number_format(array_sum($kilometrage_chart), 0, ',', ' ') ?> km</span>
                            </div>
                        </div>
                        <div style="height: 350px;" class="relative">
                            <canvas id="kilometrageByMarqueChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Consommation par Marque -->
                    <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Consommation par Marque</h3>
                                <p class="text-xs text-gray-400">Carburant consommé par marque de véhicule</p>
                            </div>
                            <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-50 via-green-50 to-white px-3 py-1.5 text-sm font-semibold text-emerald-700 shadow-sm">
                                <svg class="h-4 w-4 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 4 0v-6.998a2 2 0 0 0-.59-1.42L18 5"/><path d="M14 21V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16"/><path d="M2 21h13"/><path d="M3 9h11"/></svg>
                                <span><?= formatQuantity(array_sum($consommation_chart)) ?> L</span>
                            </div>
                        </div>
                        <div style="height: 350px;" class="relative">
                            <canvas id="consommationByMarqueChart"></canvas>
                        </div>
                    </div>

                    <?php
                    // Get selected marque filter - default to first marque
                    $selectedMarque = isset($_POST['maintenance_marque']) ? $_POST['maintenance_marque'] : (count($tab_marque) > 0 ? $tab_marque[0] : 'all');
                    
                    // Build marque condition for queries
                    $marqueCondition = "";
                    $marqueParams = ['date_debut' => $date_debut, 'date_fin' => $date_fin];
                    if ($selectedMarque !== 'all' && !empty($selectedMarque)) {
                        $marqueCondition = " AND b.marque = :marque";
                        $marqueParams['marque'] = $selectedMarque;
                    }
                    
                    // Oil Statistics
                    $oilStatsData = [];
                    $totalOilStats = 0;
                    try {
                        $oilStatsQuery = $db->prepare("
                            SELECT 
                                ot.name as oil_type,
                                mo.oil_operation,
                                SUM(mo.quantity) as total_quantity
                            FROM maintenance_operations mo
                            JOIN maintenance_records mr ON mo.record_id = mr.id
                            JOIN bus b ON mr.id_bus = b.id_bus
                            JOIN oil_types ot ON mo.oil_type_id = ot.id
                            WHERE mr.date >= :date_debut AND mr.date <= :date_fin
                            AND mo.type = 'Huile' AND mo.quantity > 0
                            AND mo.oil_operation IN ('Vidange', 'Apoint')
                            $marqueCondition
                            GROUP BY ot.id, ot.name, mo.oil_operation
                            ORDER BY ot.name
                        ");
                        $oilStatsQuery->execute($marqueParams);
                        $oilStatsRaw = $oilStatsQuery->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($oilStatsRaw as $row) {
                            $oilType = $row['oil_type'];
                            if (!isset($oilStatsData[$oilType])) {
                                $oilStatsData[$oilType] = ['Vidange' => 0, 'Apoint' => 0];
                            }
                            $oilStatsData[$oilType][$row['oil_operation']] = floatval($row['total_quantity']);
                            $totalOilStats += floatval($row['total_quantity']);
                        }
                    } catch (Exception $e) {}
                    
                    // Liquide Statistics
                    $liquideStats = [];
                    try {
                        $liquideStatsQuery = $db->prepare("
                            SELECT 
                                l.name as liquide_type,
                                SUM(mo.quantity) as total_quantity
                            FROM maintenance_operations mo
                            JOIN maintenance_records mr ON mo.record_id = mr.id
                            JOIN bus b ON mr.id_bus = b.id_bus
                            JOIN liquides l ON mo.liquide_type_id = l.id
                            WHERE mr.date >= :date_debut AND mr.date <= :date_fin
                            AND mo.type = 'Liquide' AND mo.quantity > 0
                            $marqueCondition
                            GROUP BY l.id, l.name
                            ORDER BY l.name
                        ");
                        $liquideStatsQuery->execute($marqueParams);
                        $liquideStats = $liquideStatsQuery->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {}
                    
                    // Filter Statistics
                    $filterStats = [];
                    try {
                        $filterStatsQuery = $db->prepare("
                            SELECT 
                                ft.name as filter_type,
                                mo.filter_operation,
                                COUNT(*) as count
                            FROM maintenance_operations mo
                            JOIN maintenance_records mr ON mo.record_id = mr.id
                            JOIN bus b ON mr.id_bus = b.id_bus
                            JOIN filter_types ft ON mo.filter_type_id = ft.id
                            WHERE mr.date >= :date_debut AND mr.date <= :date_fin
                            AND mo.type = 'Filter' AND mo.filter_operation IS NOT NULL
                            $marqueCondition
                            GROUP BY ft.id, ft.name, mo.filter_operation
                            ORDER BY ft.name
                        ");
                        $filterStatsQuery->execute($marqueParams);
                        $filterStats = $filterStatsQuery->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {}
                    ?>
                    
                    <!-- Marque Filter for Maintenance Stats -->
                    <div class="bg-white rounded-2xl shadow-xl p-6 mb-6 mt-8">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Statistiques de Maintenance Préventive</h3>
                                <p class="text-xs text-gray-400">Huiles, Liquides et Filtres</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <label for="maintenance_marque" class="text-sm font-medium text-gray-700">Filtrer par Marque:</label>
                                <select id="maintenance_marque" name="maintenance_marque" 
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                    <option value="all" <?= $selectedMarque === 'all' ? 'selected' : '' ?>>Toutes les marques</option>
                                    <?php foreach ($tab_marque as $marque): ?>
                                    <option value="<?= htmlspecialchars($marque) ?>" <?= $selectedMarque === $marque ? 'selected' : '' ?>><?= htmlspecialchars($marque) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                    document.getElementById('maintenance_marque').addEventListener('change', function() {
                        var form = document.getElementById('search_form');
                        var hiddenInput = form.querySelector('input[name="maintenance_marque"]');
                        if (!hiddenInput) {
                            hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'maintenance_marque';
                            form.appendChild(hiddenInput);
                        }
                        hiddenInput.value = this.value;
                        form.submit();
                    });
                    </script>
                    
                    <!-- Maintenance Statistics Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Oil Statistics by Type -->
                        <div class="bg-white rounded-2xl shadow-xl p-6">
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Statistiques des Huiles par Type</h3>
                                    <p class="text-xs text-gray-400">Vidange vs Appoint<?= $selectedMarque !== 'all' ? ' - ' . htmlspecialchars($selectedMarque) : '' ?></p>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-amber-50 via-yellow-50 to-white px-3 py-1.5 text-sm font-semibold text-amber-700 shadow-sm">
                                    <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>
                                    <span><?= formatQuantity($totalOilStats) ?> L</span>
                                </div>
                            </div>
                            <div style="height: 300px;" class="relative">
                                <canvas id="oilStatsChart"></canvas>
                                <?php if (empty($oilStatsData)): ?>
                                <div class="absolute inset-0 flex items-center justify-center text-gray-400">Aucune donnée disponible</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Liquide Statistics by Type -->
                        <div class="bg-white rounded-2xl shadow-xl p-6">
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Consommation de Liquide par Type</h3>
                                    <p class="text-xs text-gray-400">Quantités utilisées<?= $selectedMarque !== 'all' ? ' - ' . htmlspecialchars($selectedMarque) : '' ?></p>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-purple-50 via-pink-50 to-white px-3 py-1.5 text-sm font-semibold text-purple-700 shadow-sm">
                                    <svg class="h-4 w-4 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>
                                    <span><?= formatQuantity(array_sum(array_column($liquideStats, 'total_quantity'))) ?> L</span>
                                </div>
                            </div>
                            <div style="height: 300px;" class="relative">
                                <canvas id="liquideStatsChart"></canvas>
                                <?php if (empty($liquideStats)): ?>
                                <div class="absolute inset-0 flex items-center justify-center text-gray-400">Aucune donnée disponible</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Statistics -->
                    <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Statistiques des Filtres</h3>
                                <p class="text-xs text-gray-400">Changement vs Nettoyage<?= $selectedMarque !== 'all' ? ' - ' . htmlspecialchars($selectedMarque) : '' ?></p>
                            </div>
                        </div>
                        <div style="height: 350px;" class="relative">
                            <canvas id="filterStatsChart"></canvas>
                            <?php if (empty($filterStats)): ?>
                            <div class="absolute inset-0 flex items-center justify-center text-gray-400">Aucune donnée disponible</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="panel">
                        <div class="panel-heading">
                            <span>Détail par Marque de Véhicule</span>
                            <span class="badge">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v18h18" />
                                    <path d="m7 16 3-3 2 2 5-5" />
                                </svg>
                                Données
                            </span>
                        </div>
                        <div class="panel-body overflow-x-auto">
                            <style>
                                /* Table styles matching liste-vehicle */
                                #tab td, #tab th {
                                    padding: 0.5rem 0.75rem !important;
                                    vertical-align: middle;
                                }
                                #tab {
                                    width: 100% !important;
                                }
                                #tab td {
                                    white-space: normal;
                                    word-wrap: break-word;
                                }
                                .dataTables_wrapper {
                                    opacity: 0;
                                    transition: opacity 0.3s ease-in-out;
                                }
                                .dataTables_wrapper.loaded {
                                    opacity: 1;
                                }
                                .dataTables_length, .dataTables_filter {
                                    margin-bottom: 0.5rem;
                                }
                                .dataTables_paginate {
                                    margin-top: 0.5rem;
                                }
                                #tab tfoot input {
                                    width: 100%;
                                    padding: 0.25rem 0.5rem;
                                    font-size: 0.875rem;
                                    border: 1px solid #e5e7eb;
                                    border-radius: 0.375rem;
                                }
                            </style>
                            <table class="table compact-table" id="tab">
                                <tfoot>
                                    <tr>
                                        <th>Marque</th>
                                        <th>Qté (L)</th>
                                        <th>Tot. Km</th>
                                        <th>Moy CO %</th>
                                        <th>Km (Exp)</th>
                                        <th>Moy CO (Exp)</th>
                                    </tr>
                                </tfoot>
                                <thead>
                                    <tr>
                                        <th>Marque</th>
                                        <th>Qté (L)</th>
                                        <th>Tot. Km</th>
                                        <th>Moy CO %</th>
                                        <th>Km (Exp)</th>
                                        <th>Moy CO (Exp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $qte_totale_sum = 0; $km_total_sum = 0; $total_km_exp_sum = 0;
                                    foreach ($tab_all as $key => $value) {
                                        ?>
                                        <tr>
                                            <td><?php echo $value["marque"]; ?></td>
                                            <td><?php echo formatQuantity($value["qte_totale"]); $qte_totale_sum += $value["qte_totale"]; ?></td>
                                            <td><?php echo number_format($value["km_total"], 0, ',', ' '); $km_total_sum += $value["km_total"]; ?></td>
                                            <td><?php echo $value["moy"]; ?></td>
                                            <td><?php echo number_format($value["total_km_exp"], 0, ',', ' '); $total_km_exp_sum += $value["total_km_exp"]; ?></td>
                                            <td><?php echo $value["moy_exp"]; ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const marques = <?= json_encode($marques_chart) ?>;
                            const kilometrageData = <?= json_encode($kilometrage_chart) ?>;
                            const consommationData = <?= json_encode($consommation_chart) ?>;
                            const barColors = [
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(168, 85, 247, 0.8)',
                                'rgba(251, 146, 60, 0.8)',
                                'rgba(239, 68, 68, 0.8)',
                                'rgba(236, 72, 153, 0.8)',
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(99, 102, 241, 0.8)'
                            ];
                            
                            // Kilométrage par Marque Chart
                            if (marques.length > 0) {
                                new Chart(document.getElementById('kilometrageByMarqueChart').getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: marques,
                                        datasets: [{
                                            label: 'Kilométrage (km)',
                                            data: kilometrageData,
                                            backgroundColor: marques.map((_, i) => barColors[i % barColors.length]),
                                            borderWidth: 2,
                                            borderRadius: 6
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                            y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('fr-FR') + ' km' } },
                                            x: { ticks: { maxRotation: 45 } }
                                        }
                                    }
                                });
                            }
                            
                            // Consommation par Marque Chart
                            if (marques.length > 0) {
                                new Chart(document.getElementById('consommationByMarqueChart').getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: marques,
                                        datasets: [{
                                            label: 'Consommation (L)',
                                            data: consommationData,
                                            backgroundColor: marques.map((_, i) => barColors[i % barColors.length]),
                                            borderWidth: 2,
                                            borderRadius: 6
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                            y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('fr-FR') + ' L' } },
                                            x: { ticks: { maxRotation: 45 } }
                                        }
                                    }
                                });
                            }
                            
                            // Oil Statistics Chart
                            const oilStatsData = <?= json_encode($oilStatsData) ?>;
                            const oilLabels = Object.keys(oilStatsData);
                            const vidangeData = oilLabels.map(type => oilStatsData[type].Vidange || 0);
                            const apointData = oilLabels.map(type => oilStatsData[type].Apoint || 0);
                            
                            if (oilLabels.length > 0) {
                                new Chart(document.getElementById('oilStatsChart').getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: oilLabels,
                                        datasets: [
                                            { label: 'Vidange', data: vidangeData, backgroundColor: 'rgba(245, 158, 11, 0.8)', borderColor: 'rgba(245, 158, 11, 1)', borderWidth: 2, borderRadius: 6 },
                                            { label: 'Appoint', data: apointData, backgroundColor: 'rgba(251, 191, 36, 0.8)', borderColor: 'rgba(251, 191, 36, 1)', borderWidth: 2, borderRadius: 6 }
                                        ]
                                    },
                                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { callback: v => v + ' L' } }, x: { ticks: { maxRotation: 45 } } } }
                                });
                            }
                            
                            // Liquide Statistics Chart
                            const liquideStats = <?= json_encode($liquideStats) ?>;
                            if (liquideStats.length > 0) {
                                new Chart(document.getElementById('liquideStatsChart').getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: liquideStats.map(d => d.liquide_type || 'Non spécifié'),
                                        datasets: [{ label: 'Quantité (L)', data: liquideStats.map(d => parseFloat(d.total_quantity) || 0), backgroundColor: 'rgba(168, 85, 247, 0.8)', borderColor: 'rgba(168, 85, 247, 1)', borderWidth: 2, borderRadius: 6 }]
                                    },
                                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { callback: v => v + ' L' } } } }
                                });
                            }
                            
                            // Filter Statistics Chart
                            const filterStats = <?= json_encode($filterStats) ?>;
                            const filterTypes = [...new Set(filterStats.map(d => d.filter_type))];
                            const changementData = filterTypes.map(type => { const item = filterStats.find(d => d.filter_type === type && d.filter_operation === 'Changement'); return item ? parseInt(item.count) : 0; });
                            const nettoyageData = filterTypes.map(type => { const item = filterStats.find(d => d.filter_type === type && d.filter_operation === 'Nettoyage'); return item ? parseInt(item.count) : 0; });
                            
                            if (filterTypes.length > 0) {
                                new Chart(document.getElementById('filterStatsChart').getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: filterTypes,
                                        datasets: [
                                            { label: 'Changement', data: changementData, backgroundColor: 'rgba(59, 130, 246, 0.8)', borderColor: 'rgba(59, 130, 246, 1)', borderWidth: 2, borderRadius: 4 },
                                            { label: 'Nettoyage', data: nettoyageData, backgroundColor: 'rgba(16, 185, 129, 0.8)', borderColor: 'rgba(16, 185, 129, 1)', borderWidth: 2, borderRadius: 4 }
                                        ]
                                    },
                                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, title: { display: true, text: "Nombre d'opérations" } } } }
                                });
                            }
                        });
                    </script>

                    <?php
                }
                ?>
            </form>
            <br>
        </div>
    </div>
</div>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/i18n/defaults-*.min.js"></script>
<script>
    $(document).ready(function () {
        // Reset form functionality
        $('#reset_search').on('click', function() {
            $('#search_form')[0].reset();
            // Clear any form submission
            window.location.href = window.location.pathname;
        });

        // Search form validation
        $("#search_form").on('submit', function (e) {
            var date_debut = $("#date_debut").val();
            var date_fin = $("#date_fin").val();
            if (date_debut == "" || date_fin == "") {
                alert("Vueillez verifier les critére de votre recherche");
                e.preventDefault();
                return false;
            }
        });

        var titreRapport = "Rapport consommation par marque";
        
        const table = $('#tab').DataTable({
            bSort: true,
            order: [[1, 'desc']],
            deferRender: true,
            autoWidth: false,
            scrollX: false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'print',
                    title: titreRapport,
                    text: 'Imprimer',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                },
                {
                    extend: 'excelHtml5',
                    title: titreRapport,
                    text: 'Exporter Excel',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                },
                {
                    extend: 'pdfHtml5',
                    title: titreRapport,
                    text: 'Exporter PDF',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                }
            ],
            language: {
                sProcessing: 'Traitement en cours...',
                sSearch: 'Rechercher&nbsp;:',
                sLengthMenu: 'Afficher _MENU_ éléments',
                sInfo: "Affichage de l'élément _START_ à _END_ sur _TOTAL_ éléments",
                sInfoEmpty: "Affichage de l'élément 0 à 0 sur 0 élément",
                sInfoFiltered: '(filtré de _MAX_ éléments au total)',
                sLoadingRecords: 'Chargement en cours...',
                sZeroRecords: 'Aucun élément à afficher',
                sEmptyTable: 'Aucune donnée disponible dans le tableau',
                oPaginate: {
                    sFirst: 'Premier',
                    sPrevious: 'Précédent',
                    sNext: 'Suivant',
                    sLast: 'Dernier'
                }
            },
            initComplete: function (settings, json) {
                setTimeout(function() {
                    try {
                        // Setup footer filters
                        $('#tab tfoot th').each(function (i) {
                            const $this = $(this);
                            if ($this.length) {
                                $this.html('<input type="text" placeholder="Filtrer" data-index="' + i + '" />');
                            }
                        });
                        
                        // Show table with smooth transition
                        $('.dataTables_wrapper').addClass('loaded');
                    } catch (error) {
                        console.error('DataTables initialization error:', error);
                        $('.dataTables_wrapper').addClass('loaded');
                    }
                }, 100);
            }
        });
        
        // Setup footer search functionality
        $(document).on('keyup', '#tab tfoot input', function () {
            try {
                const columnIndex = $(this).data('index');
                if (columnIndex !== undefined && table) {
                    table.column(columnIndex).search(this.value).draw();
                }
            } catch (error) {
                console.error('Footer search error:', error);
            }
        });
    });
</script>
</body>
</html>