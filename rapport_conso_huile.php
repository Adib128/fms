<?php
require 'header.php' ?>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="container">
            <div class="mx-auto flex flex-col gap-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="page-title">Rapport de consommation des lubrifiants</h1>
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
                        <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>" role="form" id="search_form" class="search-form">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                                
                                <!-- Source Kilometrage Field -->
                                <div class="space-y-2">
                                    <label for="source_kilometrage" class="block text-sm font-medium text-gray-700">Source kilométrage</label>
                                    <select name="source_kilometrage" id="source_kilometrage" 
                                        class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" required>
                                        <option value="index" <?php echo (isset($_POST['source_kilometrage']) && $_POST['source_kilometrage'] == 'index') ? 'selected' : 'selected'; ?>>Kilométrage Index</option>
                                        <option value="exploitation" <?php echo (isset($_POST['source_kilometrage']) && $_POST['source_kilometrage'] == 'exploitation') ? 'selected' : ''; ?>>Kilométrage d'exploitation</option>
                                    </select>
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
                    $source_kilometrage = isset($_POST["source_kilometrage"]) ? $_POST["source_kilometrage"] : 'index';
                    
                    if ($source_kilometrage == 'index') {
                        // Query using index_km from maintenance_records
                        $sql = "
                            SELECT 
                                b.id_bus,
                                b.matricule_interne,
                                COALESCE(s.lib, '-') AS agence,
                                s.id_station,
                                SUM(CASE WHEN mo.type = 'Huile' 
                                    AND LOWER(TRIM(c.name)) = 'moteur' 
                                    AND LOWER(TRIM(mo.oil_operation)) = 'apoint' 
                                    AND mo.quantity > 0 
                                    THEN mo.quantity ELSE 0 END) as total_huile,
                                MIN(CASE WHEN mr.index_km IS NOT NULL AND mr.index_km > 0 THEN mr.index_km ELSE NULL END) as min_index_km,
                                MAX(CASE WHEN mr.index_km IS NOT NULL AND mr.index_km > 0 THEN mr.index_km ELSE NULL END) as max_index_km
                            FROM bus b
                            LEFT JOIN station s ON b.id_station = s.id_station
                            LEFT JOIN maintenance_records mr ON b.id_bus = mr.id_bus
                            LEFT JOIN fiche_entretien f ON mr.fiche_id = f.id_fiche
                            LEFT JOIN maintenance_operations mo ON mr.id = mo.record_id
                            LEFT JOIN compartiments c ON mo.compartiment_id = c.id
                            WHERE f.date BETWEEN :date_debut AND :date_fin
                            GROUP BY b.id_bus, b.matricule_interne, s.lib, s.id_station
                            HAVING total_huile > 0 OR min_index_km IS NOT NULL
                            ORDER BY b.matricule_interne ASC
                        ";
                        
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            ':date_debut' => $date_debut,
                            ':date_fin' => $date_fin
                        ]);
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Calculate average consumption for each vehicle using index_km
                        $table_data = [];
                        $total_huile_global = 0;
                        $total_km_global = 0;
                        
                        foreach ($results as $row) {
                            $total_huile = floatval($row['total_huile'] ?? 0);
                            $min_km = $row['min_index_km'] ? floatval($row['min_index_km']) : null;
                            $max_km = $row['max_index_km'] ? floatval($row['max_index_km']) : null;
                            
                            $moyenne_consommation = 0;
                            if ($min_km !== null && $max_km !== null && $max_km > $min_km && $total_huile > 0) {
                                $km_traveled = $max_km - $min_km;
                                $moyenne_consommation = ($total_huile / $km_traveled) * 1000;
                                
                                $total_huile_global += $total_huile;
                                $total_km_global += $km_traveled;
                            }
                            
                            $table_data[] = [
                                'vehicule' => $row['matricule_interne'],
                                'agence' => $row['agence'],
                                'total_huile' => $total_huile,
                                'moyenne_consommation' => $moyenne_consommation,
                                'km_traveled' => ($min_km !== null && $max_km !== null && $max_km > $min_km) ? ($max_km - $min_km) : 0
                            ];
                        }
                    } else {
                        // Query using kilometrage from kilometrage table (exploitation)
                        // First get oil consumption per bus
                        $oilSql = "
                            SELECT 
                                b.id_bus,
                                b.matricule_interne,
                                COALESCE(s.lib, '-') AS agence,
                                s.id_station,
                                SUM(CASE WHEN mo.type = 'Huile' 
                                    AND LOWER(TRIM(c.name)) = 'moteur' 
                                    AND LOWER(TRIM(mo.oil_operation)) = 'apoint' 
                                    AND mo.quantity > 0 
                                    THEN mo.quantity ELSE 0 END) as total_huile
                            FROM bus b
                            LEFT JOIN station s ON b.id_station = s.id_station
                            LEFT JOIN maintenance_records mr ON b.id_bus = mr.id_bus
                            LEFT JOIN fiche_entretien f ON mr.fiche_id = f.id_fiche
                            LEFT JOIN maintenance_operations mo ON mr.id = mo.record_id
                            LEFT JOIN compartiments c ON mo.compartiment_id = c.id
                            WHERE f.date BETWEEN :date_debut AND :date_fin
                            GROUP BY b.id_bus, b.matricule_interne, s.lib, s.id_station
                            HAVING total_huile > 0
                        ";
                        
                        $oilStmt = $db->prepare($oilSql);
                        $oilStmt->execute([
                            ':date_debut' => $date_debut,
                            ':date_fin' => $date_fin
                        ]);
                        $oilResults = $oilStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Get kilometrage sum per bus for the selected period
                        $kmSql = "
                            SELECT 
                                id_bus,
                                SUM(kilometrage) as total_km_exploitation
                            FROM kilometrage
                            WHERE date_kilometrage BETWEEN :date_debut_km AND :date_fin_km
                            GROUP BY id_bus
                        ";
                        
                        $kmStmt = $db->prepare($kmSql);
                        $kmStmt->execute([
                            ':date_debut_km' => $date_debut,
                            ':date_fin_km' => $date_fin
                        ]);
                        $kmResults = $kmStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Create a map of bus_id => total_km
                        $kmMap = [];
                        foreach ($kmResults as $kmRow) {
                            $kmMap[$kmRow['id_bus']] = floatval($kmRow['total_km_exploitation'] ?? 0);
                        }
                        
                        // Calculate average consumption for each vehicle using kilometrage from kilometrage table
                        $table_data = [];
                        $total_huile_global = 0;
                        $total_km_global = 0;
                        
                        foreach ($oilResults as $row) {
                            $id_bus = $row['id_bus'];
                            $total_huile = floatval($row['total_huile'] ?? 0);
                            $km_traveled = isset($kmMap[$id_bus]) ? $kmMap[$id_bus] : 0;
                            
                            $moyenne_consommation = 0;
                            if ($km_traveled > 0 && $total_huile > 0) {
                                $moyenne_consommation = ($total_huile / $km_traveled) * 1000;
                                
                                $total_huile_global += $total_huile;
                                $total_km_global += $km_traveled;
                            }
                            
                            $table_data[] = [
                                'vehicule' => $row['matricule_interne'],
                                'agence' => $row['agence'],
                                'total_huile' => $total_huile,
                                'moyenne_consommation' => $moyenne_consommation,
                                'km_traveled' => $km_traveled
                            ];
                        }
                    }
                    
                    // Calculate global average
                    $moyenne_globale = ($total_km_global > 0) ? ($total_huile_global / $total_km_global) * 1000 : 0;
                    
                    // Filter vehicles with high consumption (> 4 L/1000 km) and sort by moyenne DESC
                    $high_consumption_data = array_filter($table_data, function($data) {
                        return $data['moyenne_consommation'] > 4;
                    });
                    
                    // Sort by moyenne_consommation DESC
                    usort($high_consumption_data, function($a, $b) {
                        return $b['moyenne_consommation'] <=> $a['moyenne_consommation'];
                    });
                    ?>

                    <!-- High Consumption Vehicles Panel -->
                    <?php if (count($high_consumption_data) > 0): 
                        // Calculate statistics for high consumption vehicles
                        $avg_high_consumption = array_sum(array_column($high_consumption_data, 'moyenne_consommation')) / count($high_consumption_data);
                        $max_consumption = max(array_column($high_consumption_data, 'moyenne_consommation'));
                    ?>
                    <div class="panel border-2 border-red-300 shadow-lg" style="background: linear-gradient(to bottom, #fef2f2 0%, #ffffff 100%);">
                        <!-- Alert Banner -->
                        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-4 rounded-t-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                        <line x1="12" y1="9" x2="12" y2="13"></line>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                    <div>
                                        <h2 class="text-lg font-bold">⚠️ Véhicules à forte consommation</h2>
                                        <p class="text-sm text-red-100">Ces véhicules nécessitent une attention particulière</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold"><?php echo count($high_consumption_data); ?></div>
                                    <div class="text-xs text-red-100">véhicule(s)</div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="panel-body">
                            <div class="overflow-x-auto">
                                <table class="table table-striped table-bordered" id="highConsumptionTable" style="width:100%">
                                    <thead class="bg-red-100">
                                        <tr>
                                            <th class="text-red-900 font-semibold">Véhicule</th>
                                            <th class="text-red-900 font-semibold">Agence</th>
                                            <th class="text-red-900 font-semibold text-right">Consommation totale (L)</th>
                                            <th class="text-red-900 font-semibold text-right">Total Kilométrage</th>
                                            <th class="text-red-900 font-semibold text-right">Moyenne consommation (L/1000 km)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($high_consumption_data as $data): 
                                            // Color code based on consumption level
                                            $consumption = $data['moyenne_consommation'];
                                            $rowClass = '';
                                            $badgeClass = '';
                                            if ($consumption >= 8) {
                                                $rowClass = 'bg-red-50 hover:bg-red-100';
                                                $badgeClass = 'bg-red-600 text-white';
                                            } elseif ($consumption >= 6) {
                                                $rowClass = 'bg-orange-50 hover:bg-orange-100';
                                                $badgeClass = 'bg-orange-500 text-white';
                                            } else {
                                                $rowClass = 'bg-yellow-50 hover:bg-yellow-100';
                                                $badgeClass = 'bg-yellow-500 text-white';
                                            }
                                        ?>
                                            <tr class="<?php echo $rowClass; ?> transition-colors">
                                                <td class="font-medium">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="h-4 w-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M5 18H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3.19M9 18h6M9 18v-4M9 14h.01M15 18v-4m0 4h.01M19 10a2 2 0 0 0-2-2h-1M19 10a2 2 0 0 1 2 2m-2-2v6a2 2 0 0 0 2 2m-2-2H7m5-10V4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v2m5 0h2"/>
                                                        </svg>
                                                        <?php echo htmlspecialchars($data['vehicule']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($data['agence']); ?></td>
                                                <td class="text-right font-medium"><?php echo number_format($data['total_huile'], 2, ',', ' '); ?></td>
                                                <td class="text-right"><?php echo $data['km_traveled'] > 0 ? number_format($data['km_traveled'], 0, ',', ' ') . ' km' : '-'; ?></td>
                                                <td class="text-right">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold <?php echo $badgeClass; ?>">
                                                        <?php echo $data['moyenne_consommation'] > 0 ? number_format($data['moyenne_consommation'], 2, ',', ' ') : '-'; ?>
                                                        <?php if ($consumption >= 8): ?>
                                                            <svg class="ml-1 h-3 w-3" viewBox="0 0 24 24" fill="currentColor">
                                                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                                            </svg>
                                                        <?php endif; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Results Panel -->
                    <div class="panel">
                        <div class="panel-heading">
                            <span>Moyenne des consommations des véhicules</span>
                            <span class="badge">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 11H3m6 0v6m0-6l-6 6"/>
                                    <path d="M15 13h6m-6 0v-6m0 6l6-6"/>
                                </svg>
                                
                            </span>
                        </div>
                        <div class="panel-body">
                            <?php if (count($table_data) > 0): ?>
                                
                                <div class="overflow-x-auto">
                                    <table class="table table-striped table-bordered" id="consoHuileTable" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Véhicule</th>
                                                <th>Agence</th>
                                                <th>Consommation totale (L)</th>
                                                <th>Total Kilométrage</th>
                                                <th>Moyenne consommation (L/1000 km)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($table_data as $data): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($data['vehicule']); ?></td>
                                                    <td><?php echo htmlspecialchars($data['agence']); ?></td>
                                                    <td class="text-right"><?php echo number_format($data['total_huile'], 2, ',', ' '); ?></td>
                                                    <td class="text-right"><?php echo $data['km_traveled'] > 0 ? number_format($data['km_traveled'], 0, ',', ' ') . ' km' : '-'; ?></td>
                                                    <td class="text-right"><?php echo $data['moyenne_consommation'] > 0 ? number_format($data['moyenne_consommation'], 2, ',', ' ') : '-'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M9 11H3m6 0v6m0-6l-6 6"/>
                                        <path d="M15 13h6m-6 0v-6m0 6l6-6"/>
                                    </svg>
                                    <p class="text-gray-500">Aucune donnée disponible pour la période sélectionnée</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
    $(document).ready(function() {
        // Reset form functionality
        $('#reset_search').on('click', function() {
            $('#search_form')[0].reset();
            window.location.href = window.location.pathname;
        });

        // Initialize DataTable for high consumption table if exists
        if ($('#highConsumptionTable').length) {
            $('#highConsumptionTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/French.json'
                },
                pageLength: 25,
                responsive: true,
                order: [[4, 'desc']], // Order by Moyenne consommation DESC (column index 4)
                columnDefs: [
                    { type: 'num', targets: [2, 3, 4] } // Set numeric type for consumption and kilometrage columns
                ],
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
                ]
            });
        }

        // Initialize DataTable if table exists
        if ($('#consoHuileTable').length) {
            $('#consoHuileTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/French.json'
                },
                pageLength: 25,
                responsive: true,
                order: [[0, 'asc']],
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
                ]
            });
        }
    });
</script>
</body>
</html>

