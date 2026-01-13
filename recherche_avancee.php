<?php
require 'header.php' ?>
<style>
    input[type="date"]:before {
        content: attr(placeholder) !important;
        color: #aaa;
        margin-right: 0.5em;
    }
    input[type="date"]:focus:before,
    input[type="date"]:valid:before {
        content: "";
    }
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
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Historique des ravitaillements par véhicule</h1>
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
                <form method="POST" action="<?= url('rapport-consommation') ?>" role="form" id="search_form" class="search-form">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Date début Field -->
                        <div class="space-y-2">
                            <label for="date_debut" class="block text-sm font-medium text-gray-700">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" 
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                value="<?php echo isset($_POST['date_debut']) ? $_POST['date_debut'] : ''; ?>">
                        </div>
                        
                        <!-- Date fin Field -->
                        <div class="space-y-2">
                            <label for="date_fin" class="block text-sm font-medium text-gray-700">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" 
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                value="<?php echo isset($_POST['date_fin']) ? $_POST['date_fin'] : ''; ?>">
                        </div>
                        
                                                
                        <!-- Véhicule Field -->
                        <div class="space-y-2">
                            <label for="bus" class="block text-sm font-medium text-gray-700">Véhicule</label>
                            <select
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 tom-select"
                                name="bus"
                                id="bus"
                                data-placeholder="Choisir le véhicule"
                                data-search-placeholder="Rechercher un véhicule"
                                data-icon="bus"
                            >
                                <option value=""></option>
                                <?php
                                $liste_station = $db->query("SELECT * FROM bus ORDER BY id_bus ASC");
                                foreach ($liste_station as $row) {
                                    echo '<option value="' . $row["id_bus"] . '" ' . ((isset($_POST['bus']) && $_POST['bus'] == $row["id_bus"]) ? 'selected' : '') . '>';
                                    echo htmlspecialchars($row["matricule_interne"] . " : " . $row["type"], ENT_QUOTES, 'UTF-8');
                                    echo '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Chauffeur Field -->
                        <div class="space-y-2">
                            <label for="chauffeur" class="block text-sm font-medium text-gray-700">Chauffeur</label>
                            <select
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 tom-select"
                                name="chauffeur"
                                id="chauffeur"
                                data-placeholder="Choisir le chauffeur"
                                data-search-placeholder="Rechercher un chauffeur"
                                data-icon="chauffeur"
                            >
                                <option value=""></option>
                                <?php
                                $liste_station = $db->query("SELECT * FROM chauffeur ORDER BY id_chauffeur ASC");
                                foreach ($liste_station as $row) {
                                    echo '<option value="' . $row["id_chauffeur"] . '" ' . ((isset($_POST['chauffeur']) && $_POST['chauffeur'] == $row["id_chauffeur"]) ? 'selected' : '') . '>';
                                    echo htmlspecialchars($row["matricule"] . " : " . $row["nom_prenom"], ENT_QUOTES, 'UTF-8');
                                    echo '</option>';
                                }
                                ?>
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
                                                <button type="button" id="search" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
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
                // Calculate statistics for chart - move before table
                if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                    $bus = $_POST["bus"];
                    $chauffeur = $_POST["chauffeur"];

                    $date_debut = strtotime($_POST["date_debut"]);
                    $date_debut = date('Y-m-d', $date_debut);

                    $date_fin = strtotime($_POST["date_fin"]);
                    $date_fin = date('Y-m-d', $date_fin);

                    $where = " WHERE 1 = 1 ";

                    if ($bus != "") {
                        $where = $where . " AND id_bus = " . $bus;
                    }

                    if ($chauffeur != "") {
                        $where = $where . " AND id_chauffeur = " . $chauffeur . " ";
                    }

                    if ($date_debut != "1970-01-01") {
                        $where = $where . " AND date >= " . "'" . $date_debut . "' ";
                    }

                    if ($date_fin != "1970-01-01") {
                        $where = $where . " AND date <= " . "'" . $date_fin . "' ";
                    }

                    $liste_carburant = $db->query(
                            "SELECT * FROM  carburant " . $where . " ORDER BY id_carburant ASC"
                    );
                    $e_carburant = $liste_carburant->fetch(PDO::FETCH_NUM);

                    $tab_all = array();
                    $tab_indexes = array();
                    if ($e_carburant == true) {
                        $liste_carburant = $db->query(
                                "SELECT * FROM  carburant " . $where . " ORDER BY date ASC"
                        );
                        $i = 0;
                        foreach ($liste_carburant as $item) {
                            $id_doc_carburant = $item["id_doc_carburant"];
                            $query = $db->query(
                                    "SELECT * FROM  doc_carburant WHERE id_doc_carburant='$id_doc_carburant'"
                            );
                            foreach ($query as $it) {
                                $num_doc = $it["num_doc_carburant"];
                                $date = $it["date"];
                            }

                            $date = $item["date"];
                            $date = date_create($date);
                            $date = date_format($date, "d/m/Y");
                            $tab_all[$i]["id_bus"] = $item["id_bus"];
                            $tab_all[$i]["id_chauffeur"] = $item["id_chauffeur"];
                            $tab_all[$i]["num"] = $item["ref"];
                            $tab_all[$i]["date"] = $date;
                            $tab_all[$i]["qte_go"] = $item["qte_go"];
                            $tab_all[$i]["index_km"] = $item["index_km"];
                            $tab_all[$i]["type"] = $item["type"];
                            $tab_all[$i]["id_carburant"] = $item["id_carburant"];
                            $tab_indexes[] = $item["index_km"];
                            $i++;
                        }
                    }
                    
                    // Display statistics and chart if data exists
                    if ($e_carburant == true && isset($tab_all) && count($tab_all) > 0) {
                        $chart_data = array();
                        $standard_consommation = array();
                        $moyenne_consommation = array();
                        $dates_chart = array();
                        
                        $i = 0;
                        $last_index_km = 0;
                        $cumul_qte = 0;
                        $cumul_km = 0;
                        
                        foreach ($tab_all as $sc) {
                            $dates_chart[] = $sc["date"];
                            
                            // Get driver info for tooltip
                            $driver_info = "";
                            if ($sc["id_chauffeur"] != "") {
                                $id_chauffeur = $sc["id_chauffeur"];
                                $driver_query = $db->query("SELECT * FROM chauffeur WHERE id_chauffeur='$id_chauffeur'");
                                foreach ($driver_query as $driver) {
                                    $driver_info = $driver["matricule"] . " - " . $driver["nom_prenom"];
                                }
                            } else {
                                $driver_info = "Non assigné";
                            }
                            $chauffeurs_info[] = $driver_info;
                            
                            // Get standard consumption from bus
                            $id_bus = $sc["id_bus"];
                            $item = $db->query("SELECT * FROM bus WHERE id_bus='$id_bus'");
                            $conso_standard = 0;
                            foreach ($item as $cr) {
                                $conso_standard = $cr["conso"];
                            }
                            $standard_consommation[] = $conso_standard;
                            
                            // Calculate average consumption
                            if ($i > 0 && $last_index_km > 0) {
                                $km_diff = $sc["index_km"] - $last_index_km;
                                if ($km_diff > 0) {
                                    $moy = ($sc["qte_go"] / $km_diff) * 100;
                                    $moyenne_consommation[] = round($moy, 2);
                                } else {
                                    $moyenne_consommation[] = null; // Use null for invalid data
                                }
                            } else {
                                $moyenne_consommation[] = null; // Use null for first entry
                            }
                            
                            $last_index_km = $sc["index_km"];
                            $i++;
                        }
                        
                        // Filter out null values and corresponding data points
                        $filtered_data = array();
                        for ($j = 0; $j < count($moyenne_consommation); $j++) {
                            if ($moyenne_consommation[$j] !== null) {
                                $filtered_data['dates'][] = $dates_chart[$j];
                                $filtered_data['moyennes'][] = $moyenne_consommation[$j];
                                $filtered_data['standards'][] = $standard_consommation[$j];
                                $filtered_data['chauffeurs'][] = $chauffeurs_info[$j];
                            }
                        }
                        
                        // Replace original arrays with filtered data
                        $dates_chart = $filtered_data['dates'] ?? array();
                        $moyenne_consommation = $filtered_data['moyennes'] ?? array();
                        $standard_consommation = $filtered_data['standards'] ?? array();
                        $chauffeurs_info = $filtered_data['chauffeurs'] ?? array();
                        
                        // Calculate totals
                        $total_qte = 0;
                        $total_km = 0;
                        $avg_conso = 0;
                        $entries_count = count($tab_all);
                        
                        foreach ($tab_all as $sc) {
                            $total_qte += $sc["qte_go"];
                        }
                        
                        if (count($tab_indexes) > 0) {
                            function calculDiff($tab) {
                                $diff = 0;
                                $ln = count($tab) - 1;
                                $diff = $tab[$ln] - $tab[0];
                                return $diff;
                            }
                            $total_km = calculDiff($tab_indexes);
                        }
                        
                        if ($total_km > 0) {
                            $avg_conso = ($total_qte / $total_km) * 100;
                            $avg_conso = round($avg_conso, 2);
                        }
                        
                        // Get standard consumption from first bus and vehicle info
                        $standard_conso = 0;
                        $vehicle_info = "";
                        if (isset($tab_all[0]["id_bus"])) {
                            $item = $db->query("SELECT * FROM bus WHERE id_bus='" . $tab_all[0]["id_bus"] . "'");
                            foreach ($item as $cr) {
                                $standard_conso = $cr["conso"];
                                $vehicle_info = $cr["matricule_interne"];
                            }
                        }
                        ?>

                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-blue-100 text-sm font-medium">Total Carburant</p>
                                        <p class="text-3xl font-bold mt-2"><?php echo $total_qte; ?> <span class="text-lg font-normal">L</span></p>
                                    </div>
                                    <div class="bg-white/20 rounded-full p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-fuel-icon lucide-fuel"><path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 4 0v-6.998a2 2 0 0 0-.59-1.42L18 5"/><path d="M14 21V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16"/><path d="M2 21h13"/><path d="M3 9h11"/></svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-emerald-100 text-sm font-medium">Distance Totale</p>
                                        <p class="text-3xl font-bold mt-2"><?php echo number_format($total_km, 0, ',', ' '); ?> <span class="text-lg font-normal">km</span></p>
                                    </div>
                                    <div class="bg-white/20 rounded-full p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pinned-icon lucide-map-pinned"><path d="M18 8c0 3.613-3.869 7.429-5.393 8.795a1 1 0 0 1-1.214 0C9.87 15.429 6 11.613 6 8a6 6 0 0 1 12 0"/><circle cx="12" cy="8" r="2"/><path d="M8.714 14h-3.71a1 1 0 0 0-.948.683l-2.004 6A1 1 0 0 0 3 22h18a1 1 0 0 0 .948-1.316l-2-6a1 1 0 0 0-.949-.684h-3.712"/></svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-purple-100 text-sm font-medium">Moyenne Consommation</p>
                                        <p class="text-3xl font-bold mt-2"><?php echo $avg_conso; ?> <span class="text-lg font-normal">L/100km</span></p>
                                    </div>
                                    <div class="bg-white/20 rounded-full p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-gauge-icon lucide-circle-gauge"><path d="M15.6 2.7a10 10 0 1 0 5.7 5.7"/><circle cx="12" cy="12" r="2"/><path d="M13.4 10.6 19 5"/></svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-2xl p-6 text-white shadow-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-amber-100 text-sm font-medium">Nombre des ravitaillements</p>
                                        <p class="text-3xl font-bold mt-2"><?php echo $entries_count; ?></p>
                                    </div>
                                    <div class="bg-white/20 rounded-full p-3">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="16" y1="13" x2="8" y2="13"></line>
                                            <line x1="16" y1="17" x2="8" y2="17"></line>
                                            <polyline points="10 9 9 9 8 9"></polyline>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart Container -->
                        <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                            <h3 class="text-xl font-semibold text-gray-800 mb-6">Évolution de la Consommation Moyenne du véhicule <?php echo $vehicle_info; ?></h3>
                            <div class="relative" style="height: 400px;">
                                <canvas id="consumptionChart"></canvas>
                            </div>
                        </div>

                        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const ctx = document.getElementById('consumptionChart').getContext('2d');
                                
                                // Prepare chart datasets
                                const datasets = [
                                    {
                                        label: 'Consommation Moyenne',
                                        data: <?php echo json_encode($moyenne_consommation); ?>,
                                        borderColor: 'rgb(16, 185, 129)',
                                        backgroundColor: 'transparent',
                                        borderWidth: 3,
                                        tension: 0.4,
                                        pointRadius: 6,
                                        pointBackgroundColor: 'rgb(16, 185, 129)',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        pointHoverRadius: 8,
                                        fill: false
                                    },
                                    {
                                        label: 'Consommation Standard du Véhicule',
                                        data: <?php echo json_encode($standard_consommation); ?>,
                                        borderColor: 'rgb(239, 68, 68)',
                                        backgroundColor: 'transparent',
                                        borderWidth: 2,
                                        borderDash: [10, 5],
                                        tension: 0,
                                        pointRadius: 0,
                                        pointHoverRadius: 0,
                                        fill: false
                                    }
                                ];
                                
                                const chart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: <?php echo json_encode($dates_chart); ?>,
                                        datasets: datasets
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                display: true,
                                                position: 'top',
                                                labels: {
                                                    usePointStyle: true,
                                                    padding: 20,
                                                    font: {
                                                        size: 12,
                                                        family: 'system-ui'
                                                    }
                                                }
                                            },
                                            tooltip: {
                                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                                padding: 12,
                                                cornerRadius: 8,
                                                titleFont: {
                                                    size: 14
                                                },
                                                bodyFont: {
                                                    size: 13
                                                },
                                                callbacks: {
                                                    title: function(context) {
                                                        return 'Date: ' + context[0].label;
                                                    },
                                                    label: function(context) {
                                                        if (context.dataset.label === 'Consommation Moyenne') {
                                                            const driverInfo = <?php echo json_encode($chauffeurs_info); ?>[context.dataIndex];
                                                            return [
                                                                'Chauffeur: ' + driverInfo,
                                                                'Consommation: ' + context.parsed.y + ' L/100km'
                                                            ];
                                                        } else {
                                                            return 'Consommation Standard: ' + context.parsed.y + ' L/100km';
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: 'Consommation Moyenne',
                                                    font: {
                                                        size: 12,
                                                        weight: '500'
                                                    }
                                                },
                                                grid: {
                                                    color: 'rgba(0, 0, 0, 0.05)',
                                                    drawBorder: false
                                                },
                                                ticks: {
                                                    font: {
                                                        size: 11
                                                    }
                                                }
                                            },
                                            x: {
                                                title: {
                                                    display: true,
                                                    text: 'Dates de Ravitaillement',
                                                    font: {
                                                        size: 12,
                                                        weight: '500'
                                                    }
                                                },
                                                grid: {
                                                    display: false
                                                },
                                                ticks: {
                                                    font: {
                                                        size: 11
                                                    },
                                                    maxRotation: 45,
                                                    minRotation: 45
                                                }
                                            }
                                        },
                                        interaction: {
                                            intersect: false,
                                            mode: 'index'
                                        }
                                    }
                                });
                            });
                        </script>
                    <?php } ?>
                <?php } ?>

                <?php
                if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                    ?> 
                    <table class="table table-striped table-bordered" style="width:100%" id="tab">
                        <tfoot>
                            <tr>
                                <th>Date </th>
                                <th>Type</th>
                                <th>Véhicule</th>
                                <th>Chauffeur</th>
                                <th>Qte Carb (L)</th>
                                <th>Index Km</th>
                                <th>Moy Co</th>
                                <th>Km Tech (Estimé)</th>
                            </tr>
                        </tfoot>
                        <thead>
                            <tr>
                                <th>Date </th>
                                <th class="search_select">Type</th>
                                <th>Véhicule</th>
                                <th>Chauffeur</th>
                                <th>Qte Carb (L)</th>
                                <th>Index Km</th>
                                <th>Moy Co</th>
                                <th>Km Tech (Estimé)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $bus = $_POST["bus"];
                            $chauffeur = $_POST["chauffeur"];

                            $date_debut = strtotime($_POST["date_debut"]);
                            $date_debut = date('Y-m-d', $date_debut);

                            $date_fin = strtotime($_POST["date_fin"]);
                            $date_fin = date('Y-m-d', $date_fin);

                            $where = " WHERE 1 = 1 ";

                            if ($bus != "") {
                                $where = $where . " AND id_bus = " . $bus;
                            }

                            if ($chauffeur != "") {
                                $where = $where . " AND id_chauffeur = " . $chauffeur . " ";
                            }

                            if ($date_debut != "1970-01-01") {
                                $where = $where . " AND date >= " . "'" . $date_debut . "' ";
                            }

                            if ($date_fin != "1970-01-01") {
                                $where = $where . " AND date <= " . "'" . $date_fin . "' ";
                            }

                            $liste_carburant = $db->query(
                                    "SELECT * FROM  carburant " . $where . " ORDER BY id_carburant ASC"
                            );
                            $e_carburant = $liste_carburant->fetch(PDO::FETCH_NUM);
                

                            $tab_all = array();
                            if ($e_carburant == false) {
                                ?>
                                <tr>
                                    <td colspan="8">
                            <center>
                                <strong>Aucune résultat pour ce recherche</strong>
                            </center>
                            </td>
                            </tr>
                            <?php
                        }
                        $last_index = 0;
                        $tab_carburant = array();
                        $tab_bon = array();
                        if ($e_carburant == true) {
                            $liste_carburant = $db->query(
                                    "SELECT * FROM  carburant " . $where . " ORDER BY date ASC"
                            );
                            $i = 0;
                            foreach ($liste_carburant as $item) {
                                $id_doc_carburant = $item["id_doc_carburant"];
                                $query = $db->query(
                                        "SELECT * FROM  doc_carburant WHERE id_doc_carburant='$id_doc_carburant'"
                                );
                                foreach ($query as $it) {
                                    $num_doc = $it["num_doc_carburant"];
                                    $date = $it["date"];
                                }
                                $tab_carburant[] = $item["index_km"];

                                $date = $item["date"];
                                $date = date_create($date);
                                $date = date_format($date, "d/m/Y");
                                $tab_all[$i]["id_bus"] = $item["id_bus"];
                                $tab_all[$i]["id_chauffeur"] = $item["id_chauffeur"];
                                $tab_all[$i]["num"] = $item["ref"];
                                $tab_all[$i]["date"] = $date;
                                $tab_all[$i]["qte_go"] = $item["qte_go"];
                                $tab_all[$i]["index_km"] = $item["index_km"];
                                $tab_all[$i]["type"] = $item["type"];
                                $tab_all[$i]["id_carburant"] = $item["id_carburant"];
                                $i++;
                            }

                            $qte_total = 0;
                            $km_total = 0;
                            $last_index = 0;
                            $last_qte = 0;
                            $tab_indexes = array();
                            $i = 0 ; 
                            $first_qte = 0 ; 
                            foreach ($tab_all as $sc) {
                                if($i == 0){
                                    $first_qte = $sc["qte_go"];
                                }
                                ?>
                                <tr class="Entries">
                                    <td>
                                        <a href="modifier_carburant.php?id=<?php echo $sc["id_carburant"] ?>" class="text-blue-500 hover:text-blue-600 cursor-pointer" target="_blank"><?php echo $sc["date"]  ?> </a>
                                    </td>
                                    <td><?php echo $sc["type"] ?></td>
                                
                                    <td>
                                        <?php
                                        $id_bus = $sc["id_bus"];
                                        $item = $db->query("SELECT * FROM bus WHERE id_bus='$id_bus'");
                                        foreach ($item as $cr) {
                                            $matricule_interne = $cr["matricule_interne"];
                                            $conso = $cr["conso"];
                                        }
                                        echo $matricule_interne;
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if($sc["id_chauffeur"] != "") {
                                            $id_chauffeur = $sc["id_chauffeur"];
                                            $item = $db->query("SELECT * FROM chauffeur WHERE id_chauffeur='$id_chauffeur'");
                                            foreach ($item as $cr) {
                                                $matricule_interne = $cr["nom_prenom"];
                                            }
                                            echo $matricule_interne;
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        $qte = $sc["qte_go"];
                                        $qte_total = $qte_total + $qte;
                                        echo $sc["qte_go"];
                                        ?>
                                    </td>
                                    <td><?php echo $sc["index_km"] ?></td>
                                    <?php $tab_indexes[] = $sc["index_km"] ?>
                                   <td>
                                    <?php
                                   
                                    if($i > 0){
                                    if ($last_index != 0) {
                                        $km = $sc["index_km"] - $last_index;
                                        if ($km > 0) {
                                            $moy = $qte / $km;
                                            $moy = $moy * 100;
                                            $moy = round($moy, 2);
                                            echo $moy;
                                        } else {
                                            echo '-';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                } else {
                                    // First entry - show dash instead of 0
                                    echo '-';
                                }
                                    ?>
                                    </td> 
                                    <td>
                                        
                                        <?php 
                                        $km_tech = $sc["qte_go"] * 100 ;
                                        $km_tech =  round($km_tech /  $conso) ; 
                                        echo $km_tech;
                                        ?>

                                    </td>

                                </tr>
                                <?php
                                $last_index = $sc["index_km"];
                                $last_qte = $sc["qte_go"];
                                $i++;
                            }
                            
                            ?>
                            <tr class="Entries" style="font-weight: bold">
                                <td><b>Total</b></td>
                                <td></td>
                                <td></td>
                                <td></td>

                                <td><?php echo $qte_total; ?></td>
                                <td>
                                    <?php
                                    $tab_diff = array_merge($tab_carburant, $tab_bon);
                                    sort($tab_diff);
                                    if (count($tab_indexes) != 0) {
                                        $total_km = calculDiff($tab_indexes);
                                    } else {
                                        $total_km = 0;
                                    }
                                    echo $total_km;
                                    ?> 
                                </td>
                               <td>
                                <?php
                                if ($total_km != 0) {
                                    $qte_total = $qte_total - $first_qte ; 
                                    $moy_total = ($qte_total / $total_km) * 100;
                                    $moy_total = round($moy_total, 2);
                                    echo $moy_total;
                                }
                                ?>
                                </td>
                                 <td>
                                        <?php 
                                        $km_tech = $qte_total * 100 ;
                                        $km_tech =  round($km_tech /  $conso) ; 
                                        echo $km_tech;
                                        ?>
                                    </td>
                            </tr>
                            </tbody>
                        </table>

                        <?php
                    }
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
<script>
    $(document).ready(function () {

        /*************************************************************************************************/
        $("#search").click(function () {
            var bus = $("#bus").val();
            var chauffeur = $("#chauffeur").val();
            var num_doc = $("#num_doc").val();
            var date_debut = $("#date_debut").val();
            var date_fin = $("#date_fin").val();
            if (bus == "" && chauffeur == "" && date_debut == "" && date_fin == "") {
                alert("Vueillez verifier les critére de votre recherche");
            } else {
                $("#search_form").submit();
            }
        });

        /*************************************************************************************************/
        $("#reset_search").click(function () {
            // Clear all form fields
            $("#date_debut").val("");
            $("#date_fin").val("");
            $("#bus").val("");
            $("#chauffeur").val("");
            
            // Reset TomSelect instances
            if (window.tomSelectInstances) {
                window.tomSelectInstances.forEach(function(instance) {
                    instance.clear();
                });
            }
            
            // Submit form to reset results
            $("#search_form").submit();
        });

        // ************************************************************************************************/
        $('tr.Entries').each(function () {
            var $this = $(this),
                    t = this.cells[1].textContent.split('-');
            $this.data('_ts', new Date(t[2], t[1] - 1, t[0]).getTime());
        }).sort(function (a, b) {
            return $(a).data('_ts') > $(b).data('_ts');
        }).appendTo('tbody');

        // ************************************************************************************************/

        // Setup - add a text input to each footer cell
        $('#tab tfoot th').each(function (i) {
            var title = $('#example thead th').eq($(this).index()).text();
            $(this).html('<input type="text" placeholder="" data-index="' + i + '" />');
        });

        var ch = "Etat de suivi de consommation";
        var table = $('#tab').DataTable({
            "bSort": false,
            dom: 'Bfrtip',
            bPaginate: false,
            bLengthChange: false,
            buttons: [
                {
                    extend: 'print',
                    title: ch,
                    text: 'Imprimmer',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                },
                {
                    extend: 'excelHtml5',
                    title: ch,
                    text: 'Exporter Excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    title: ch,
                    text: 'Exporter PDF',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                }
            ],

            initComplete: function () {
                this.api().columns('.search_select').every(function () {
                    var column = this;
                    var select = $('<select><option value=""></option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                        $(this).val()
                                        );

                                column
                                        .search(val ? '^' + val + '$' : '', true, false)
                                        .draw();
                            });

                    column.data().unique().sort().each(function (d, j) {
                        select.append('<option value="' + d + '">' + d + '</option>')
                    });
                });
            }
        });

        // Filter event handler
        $(table.table().container()).on('keyup', 'tfoot input', function () {
            table
                    .column($(this).data('index'))
                    .search(this.value)
                    .draw();
        });
    });
</script>
</body>
</html>