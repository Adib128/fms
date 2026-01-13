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
                        <h1 class="page-title">Statistique carburant</h1>
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
                        <form method="POST" action="<?= url('statistique-carburant') ?>" role="form" id="search_form" class="search-form">
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
                    
                    // Helper functions
                    function calculDiff($tab) {
                        if (count($tab) == 0) return 0;
                        sort($tab);
                        return $tab[count($tab) - 1] - $tab[0];
                    }
                    
                    // Get all vehicles with their data
                    $vehicles_data = [];
                    $forte_consommation = [];
                    
                    $query_bus = $db->query("SELECT * FROM bus ORDER BY matricule_interne ASC");
                    $liste_bus = $query_bus->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($liste_bus as $bus) {
                        $id_bus = $bus["id_bus"];
                        $matricule = $bus["matricule_interne"];
                        $std_conso = floatval($bus["conso"] ?? 0);
                        
                        // Get agence
                        $id_station = $bus["id_station"];
                        $agence = "";
                        if ($id_station) {
                            $query_station = $db->query("SELECT lib FROM station WHERE id_station='$id_station' LIMIT 1");
                            $station_row = $query_station->fetch(PDO::FETCH_ASSOC);
                            if ($station_row) {
                                $agence = $station_row["lib"];
                            }
                        }
                        
                        // Get carburant data for period
                        $tab_carburant_index = [];
                        $qte_totale = 0;
                        
                        $query_carburant = $db->query("
                            SELECT index_km, qte_go 
                            FROM carburant 
                            WHERE id_bus='$id_bus' 
                            AND date >= '$date_debut' 
                            AND date <= '$date_fin' 
                            ORDER BY date ASC
                        ");
                        
                        foreach ($query_carburant as $carb) {
                            if ($carb["index_km"] > 0) {
                                $tab_carburant_index[] = floatval($carb["index_km"]);
                            }
                            if ($carb["qte_go"] > 0) {
                                $qte_totale += floatval($carb["qte_go"]);
                            }
                        }
                        
                        // Calculate kilometrage index
                        $km_index = calculDiff($tab_carburant_index);
                        
                        // Calculate kilometrage exploitation
                        $query_km_exp = $db->query("
                            SELECT SUM(kilometrage) as total_km 
                            FROM kilometrage 
                            WHERE id_bus='$id_bus' 
                            AND date_kilometrage >= '$date_debut' 
                            AND date_kilometrage <= '$date_fin'
                        ");
                        $km_exp_row = $query_km_exp->fetch(PDO::FETCH_ASSOC);
                        $km_exploitation = floatval($km_exp_row["total_km"] ?? 0);
                        
                        // Calculate moyenne consommation by index
                        $moy_index = 0;
                        if ($km_index > 0 && $qte_totale > 0) {
                            $moy_index = ($qte_totale / $km_index) * 100;
                            $moy_index = round($moy_index, 2);
                        }
                        
                        // Calculate moyenne consommation by exploitation
                        $moy_exploitation = 0;
                        if ($km_exploitation > 0 && $qte_totale > 0) {
                            $moy_exploitation = ($qte_totale / $km_exploitation) * 100;
                            $moy_exploitation = round($moy_exploitation, 2);
                        }
                        
                        // Only add vehicles with data
                        if ($km_index > 0 || $km_exploitation > 0 || $qte_totale > 0) {
                            $vehicle_data = [
                                'vehicule' => $matricule,
                                'agence' => $agence,
                                'std_conso' => $std_conso,
                                'km_exploitation' => $km_exploitation,
                                'km_index' => $km_index,
                                'moy_index' => $moy_index,
                                'moy_exploitation' => $moy_exploitation
                            ];
                            
                            $vehicles_data[] = $vehicle_data;
                            
                            // Check for forte consommation (moyenne >= std + 4)
                            if ($std_conso > 0 && ($moy_index >= ($std_conso + 4) || $moy_exploitation >= ($std_conso + 4))) {
                                $forte_consommation[] = $vehicle_data;
                            }
                        }
                    }
                    
                    // Sort forte consommation by difference (highest first)
                    usort($forte_consommation, function($a, $b) {
                        $diff_a = max($a['moy_index'], $a['moy_exploitation']) - $a['std_conso'];
                        $diff_b = max($b['moy_index'], $b['moy_exploitation']) - $b['std_conso'];
                        return $diff_b <=> $diff_a;
                    });
                    ?>
                    
                    <!-- Table: Véhicules à forte consommation -->
                    <?php if (count($forte_consommation) > 0): ?>
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
                                        <h2 class="text-lg font-bold">⚠️ Véhicules à forte consommation carburant</h2>
                                        <p class="text-sm text-red-100">Ces véhicules nécessitent une attention particulière</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold"><?php echo count($forte_consommation); ?></div>
                                    <div class="text-xs text-red-100">véhicule(s)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="panel-body">
                            <div class="overflow-x-auto">
                                <table class="table table-striped table-bordered" id="tab_forte" style="width:100%">
                                    <thead class="bg-red-100">
                                        <tr>
                                            <th class="text-red-900 font-semibold">Véhicule</th>
                                            <th class="text-red-900 font-semibold">Agence</th>
                                            <th class="text-red-900 font-semibold text-right">Std Consommation</th>
                                            <th class="text-red-900 font-semibold text-right">Kilométrage Exploitation</th>
                                            <th class="text-red-900 font-semibold text-right">Kilométrage Index</th>
                                            <th class="text-red-900 font-semibold text-right">Moyenne (L/100km) par Exploitation</th>
                                            <th class="text-red-900 font-semibold text-right">Moyenne (L/100km) par Index</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($forte_consommation as $vehicle): 
                                            // Color code based on consumption level
                                            $moy_max = max($vehicle['moy_exploitation'], $vehicle['moy_index']);
                                            $diff = $moy_max - $vehicle['std_conso'];
                                            $rowClass = '';
                                            $badgeClass = '';
                                            if ($diff >= 8) {
                                                $rowClass = 'bg-red-50 hover:bg-red-100';
                                                $badgeClass = 'bg-red-600 text-white';
                                            } elseif ($diff >= 6) {
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
                                                        <?php echo htmlspecialchars($vehicle['vehicule']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($vehicle['agence']); ?></td>
                                                <td class="text-right"><?php echo number_format($vehicle['std_conso'], 2); ?></td>
                                                <td class="text-right"><?php echo $vehicle['km_exploitation'] > 0 ? number_format($vehicle['km_exploitation'], 0, ',', ' ') . ' km' : '-'; ?></td>
                                                <td class="text-right"><?php echo $vehicle['km_index'] > 0 ? number_format($vehicle['km_index'], 0, ',', ' ') . ' km' : '-'; ?></td>
                                                <td class="text-right">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold <?php echo $badgeClass; ?>">
                                                        <?php echo $vehicle['moy_exploitation'] > 0 ? number_format($vehicle['moy_exploitation'], 2, ',', ' ') : '-'; ?>
                                                        <?php if ($diff >= 8): ?>
                                                            <svg class="ml-1 h-3 w-3" viewBox="0 0 24 24" fill="currentColor">
                                                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                                            </svg>
                                                        <?php endif; ?>
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold <?php echo $badgeClass; ?>">
                                                        <?php echo $vehicle['moy_index'] > 0 ? number_format($vehicle['moy_index'], 2, ',', ' ') : '-'; ?>
                                                        <?php if ($diff >= 8): ?>
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
                    
                    <!-- Table: Liste des véhicules -->
                    <div class="panel">
                        <div class="panel-heading">
                            <span>Liste des véhicules</span>
                            <span class="badge">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 11H3m6 0v6m0-6l-6 6"/>
                                    <path d="M15 13h6m-6 0v-6m0 6l6-6"/>
                                </svg>
                            </span>
                        </div>
                        <div class="panel-body">
                            <?php if (count($vehicles_data) > 0): ?>
                                <div class="overflow-x-auto">
                                    <table class="table table-striped table-bordered" id="tab" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Véhicule</th>
                                                <th>Agence</th>
                                                <th>Std Consommation</th>
                                                <th>Kilométrage Exploitation</th>
                                                <th>Kilométrage Index</th>
                                                <th>Moyenne (L/100km) par Exploitation</th>
                                                <th>Moyenne (L/100km) par Index</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($vehicles_data as $vehicle): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($vehicle['vehicule']); ?></td>
                                                    <td><?php echo htmlspecialchars($vehicle['agence']); ?></td>
                                                    <td class="text-right"><?php echo number_format($vehicle['std_conso'], 2); ?></td>
                                                    <td class="text-right"><?php echo $vehicle['km_exploitation'] > 0 ? number_format($vehicle['km_exploitation'], 0, ',', ' ') . ' km' : '-'; ?></td>
                                                    <td class="text-right"><?php echo $vehicle['km_index'] > 0 ? number_format($vehicle['km_index'], 0, ',', ' ') . ' km' : '-'; ?></td>
                                                    <td class="text-right"><?php echo $vehicle['moy_exploitation'] > 0 ? number_format($vehicle['moy_exploitation'], 2, ',', ' ') : '-'; ?></td>
                                                    <td class="text-right"><?php echo $vehicle['moy_index'] > 0 ? number_format($vehicle['moy_index'], 2, ',', ' ') : '-'; ?></td>
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
                    
                    <?php
                }
                ?>
            <br>
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

        // Search form validation
        $("#search_form").on('submit', function (e) {
            var date_debut = $("#date_debut").val();
            var date_fin = $("#date_fin").val();
            if (date_debut == "" || date_fin == "") {
                alert("Veuillez vérifier les critères de votre recherche");
                e.preventDefault();
                return false;
            }
        });

        // Initialize DataTable for forte consommation if exists
        if ($('#tab_forte').length) {
            $('#tab_forte').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/French.json'
                },
                pageLength: 25,
                responsive: true,
                order: [[6, 'desc']], // Order by Moyenne Index DESC
                columnDefs: [
                    { type: 'num', targets: [2, 3, 4, 5, 6] } // Set numeric type for numeric columns
                ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'print',
                        text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Imprimer',
                        className: 'btn btn-indigo btn-sm',
                        title: 'Statistique carburant - Forte consommation',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg> Excel',
                        className: 'btn btn-emerald btn-sm',
                        title: 'Statistique carburant - Forte consommation',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg> PDF',
                        className: 'btn btn-red btn-sm',
                        title: 'Statistique carburant - Forte consommation',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }
                    }
                ]
            });
        }

        // Initialize DataTable if table exists
        if ($('#tab').length) {
            $('#tab').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/French.json'
                },
                pageLength: 25,
                responsive: true,
                order: [[0, 'asc']],
                columnDefs: [
                    { type: 'num', targets: [2, 3, 4, 5, 6] } // Set numeric type for numeric columns
                ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'print',
                        text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Imprimer',
                        className: 'btn btn-indigo btn-sm',
                        title: 'Statistique carburant',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg> Excel',
                        className: 'btn btn-emerald btn-sm',
                        title: 'Statistique carburant',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg> PDF',
                        className: 'btn btn-red btn-sm',
                        title: 'Statistique carburant',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }
                    }
                ]
            });
        }
    });
</script>
</body>
</html>

