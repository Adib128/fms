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
                        <h1 class="page-title">Rapport de consommation de carburant par véhicule</h1>
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
                        <form method="POST" action="#" role="form" id="search_form" class="search-form">
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
                                
                                <!-- Bus Field -->
                                <div class="space-y-2">
                                    <label for="bus" class="block text-sm font-medium text-gray-700">Véhicule</label>
                                    <select class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" name="bus" id="bus" required>
                                        <option value="">Choisir un véhicule</option>
                                        <option value="all">Tous les bus</option>
                            <?php
                            $query_station = $db->query(
                                    "SELECT * FROM  station"
                            );
                            $liste_station = $query_station->fetchAll(PDO::FETCH_ASSOC);

                            $tab_station = array();

                            foreach ($liste_station as $item) {
                                $tab_station[] = $item["id_station"];
                                ?>

                                <option value="<?php echo $item["id_station"]; ?>" <?php echo (isset($_POST['bus']) && $_POST['bus'] == $item["id_station"]) ? 'selected' : ''; ?>>
                                    Agence <?php echo $item["lib"]; ?>
                                </option>
                                <?php
                            }

                            $liste_station = $db->query("SELECT * FROM bus ORDER BY id_bus ASC");
                            foreach ($liste_station as $row) {
                                echo '<option value="' . $row["id_bus"] . '" ' . ((isset($_POST['bus']) && $_POST['bus'] == $row["id_bus"]) ? 'selected' : '') . '>';
                                echo $row["matricule_interne"] . " : " . $row["type"];
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
        // Get stations list BEFORE processing POST (needed for bus selection logic)
        $query_station = $db->query("SELECT * FROM station");
        $liste_station = $query_station->fetchAll(PDO::FETCH_ASSOC);
        $tab_station = array();
        foreach ($liste_station as $item) {
            $tab_station[] = $item["id_station"];
        }
        
        if ($_SERVER["REQUEST_METHOD"] == 'POST') {
            $date_debut = $_POST["date_debut"];
            $date_fin = $_POST["date_fin"];
            $bus_selection = isset($_POST["bus"]) ? $_POST["bus"] : "";
            
            // Handle single selection
            if ($bus_selection == "all" || $bus_selection == "") {
                $bus = "all";
            } else {
                $bus = $bus_selection;
            }
            
            // Debug: Log POST data
            error_log("DEBUG POST: bus_selection=$bus_selection, bus=$bus, date_debut=$date_debut, date_fin=$date_fin");
            error_log("DEBUG POST: tab_station count=" . count($tab_station));
            ?>
            
            <!-- Results Panel -->
            <div class="panel">
                <div class="panel-heading">
                    <span>Résultats</span>
                    <span class="badge">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11H3m6 0v6m0-6l-6 6"/>
                            <path d="M15 13h6m-6 0v-6m0 6l6-6"/>
                        </svg>
                        Données
                    </span>
                </div>
                <div class="panel-body">
                    <?php
                    // Prepare data for statistics
                    
                    // Initialize variables that will be used later
                    $tab_all = array();
                    $tab_bus = array();
                    $qte_totale_sum = 0;
                    $km_total_sum = 0;
                    $count_vehicles = 0;
                    $moyenne_conso_calculee = 0;
                    $nombre_vehicules = 0;
                    
                    // We'll populate these after processing the data
                    // KPI Cards and Chart will be displayed AFTER data processing
                    ?>
                    
                    <br> 
                    <table class="table table-striped table-bordered" style="width:100%" id="tab">
                   
                        <thead>
                            <tr>
                                <th>Véhicule</th>
                                <th>Qté</th>
                                <th>Km (Index)</th>
                                <th>Moy CO (Index)</th>
                                <th>Km (Exp)</th>
                                <th>Moy CO (Exp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            function calculDiff($tab) {
                                $ln = count($tab) - 1;
                                $diff = $tab[$ln] - $tab[0];
                                return $diff;
                            }

                            function calculDiffQte($tab) {
                                $total = 0;
                                $ln = count($tab);
                                for ($i = 0; $i < $ln; $i++) {
                                    $total = $total + $tab[$i];
                                }
                                return $total;
                            }

                            function calculDiffQteForCal($tab) {
                                $total = 0;
                                $ln = count($tab);
                                for ($i = 0; $i < $ln; $i++) {
                                    $total = $total + $tab[$i];
                                }
                                return $total;
                            }

                            $tab_all = array();
                            $qte_totale = 0;
                            $tab_carburant = array();
                            $tab_bon = array();
                            $tab_carburant_qte = array();
                            $tab_bon_qte = array();
                            
                            // Determine which buses to process
                            $tab_bus = array();
                            
                            error_log("DEBUG: Processing bus selection, bus value: " . var_export($bus, true));
                            error_log("DEBUG: tab_station: " . print_r($tab_station, true));
                            
                            if ($bus == "all") {
                                // Get all buses
                                error_log("DEBUG: Getting all buses");
                                $query_bus = $db->query(
                                        "SELECT * FROM  bus ORDER BY matricule_interne ASC"
                                );
                                $liste_bus = $query_bus->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($liste_bus as $item) {
                                    $tab_bus[] = $item["id_bus"];
                                }
                                error_log("DEBUG: Found " . count($tab_bus) . " buses (all)");
                            } elseif (is_array($bus)) {
                                // Multiple specific buses selected
                                error_log("DEBUG: Processing array of buses");
                                foreach ($bus as $bus_id) {
                                    // Check if it's a station ID
                                    if (isset($tab_station) && in_array($bus_id, $tab_station)) {
                                        error_log("DEBUG: $bus_id is a station ID");
                                        $query_bus = $db->query(
                                                "SELECT * FROM  bus WHERE id_station='$bus_id' "
                                        );
                                        $liste_bus = $query_bus->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($liste_bus as $item) {
                                            $tab_bus[] = $item["id_bus"];
                                        }
                                    } elseif ((int) $bus_id > 600) {
                                        // It's a bus ID
                                        error_log("DEBUG: $bus_id is a bus ID");
                                        $tab_bus[] = $bus_id;
                                    }
                                }
                            } else {
                                // Single bus selected (legacy support)
                                error_log("DEBUG: Processing single bus: $bus (type: " . gettype($bus) . ")");
                                
                                // Convert to int for comparison
                                $bus_int = (int) $bus;
                                // Use strict comparison to avoid false matches
                                $bus_in_stations = isset($tab_station) && in_array($bus_int, $tab_station, true);
                                
                                error_log("DEBUG: bus_int=$bus_int, bus_in_stations=" . ($bus_in_stations ? 'true' : 'false'));
                                error_log("DEBUG: tab_station values: " . print_r($tab_station, true));
                                
                                if ($bus_in_stations) {
                                    error_log("DEBUG: $bus is a station ID");
                                    $query_bus = $db->query(
                                            "SELECT * FROM  bus WHERE id_station='$bus' "
                                    );
                                    $liste_bus = $query_bus->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($liste_bus as $item) {
                                        $tab_bus[] = $item["id_bus"];
                                    }
                                    error_log("DEBUG: Found " . count($tab_bus) . " buses for station $bus");
                                } elseif ($bus_int > 0) {
                                    // It's a bus ID (any positive integer)
                                    error_log("DEBUG: $bus is a bus ID (int: $bus_int)");
                                    // Verify bus exists in database
                                    $verify_bus = $db->query("SELECT id_bus FROM bus WHERE id_bus='$bus_int' LIMIT 1");
                                    if ($verify_bus->rowCount() > 0) {
                                        $tab_bus[] = $bus_int;
                                        error_log("DEBUG: Added bus ID $bus_int to tab_bus (verified in DB)");
                                    } else {
                                        error_log("DEBUG: WARNING - bus ID $bus_int not found in database!");
                                    }
                                } else {
                                    error_log("DEBUG: WARNING - bus value '$bus' doesn't match station or bus ID pattern!");
                                }
                            }
                            
                            error_log("DEBUG: Final tab_bus count: " . count($tab_bus));
                            error_log("DEBUG: tab_bus contents: " . print_r($tab_bus, true));

                            $i = 0;
                            foreach ($tab_bus as $item) {
                                $id_bus = $item;
                                
                                // Initialize arrays for this bus
                                $tab_carburant = array();
                                $tab_carburant_qte = array();
                                
                                $query_carburant = $db->query(
                                        "SELECT * FROM  carburant INNER JOIN bus ON carburant.id_bus=bus.id_bus WHERE carburant.id_bus='$item' AND date >= '$date_debut' AND date <= '$date_fin'  ORDER BY date ASC"
                                );
                                $liste_carburant = $query_carburant->fetchAll(PDO::FETCH_ASSOC);
                                

                                foreach ($liste_carburant as $key => $value) {
                                    if (isset($value["index_km"]) && $value["index_km"] > 0) {
                                        $tab_carburant[] = $value["index_km"];
                                    }
                                    if (isset($value["qte_go"]) && $value["qte_go"] > 0) {
                                        $tab_carburant_qte[] = $value["qte_go"];
                                    }
                                }

                                $tab_diff = array_merge($tab_carburant);
                                sort($tab_diff);

                                if (count($tab_diff) != 0) {
                                    $total_km = calculDiff($tab_diff);
                                } else {
                                    $total_km = 0;
                                }

                                $qte_totale = calculDiffQte($tab_carburant_qte);
                                $qte_totale_cal = calculDiffQteForCal($tab_carburant_qte);

                                if ($qte_totale_cal != 0 && $total_km != 0) {
                                    $moy = ($qte_totale_cal / $total_km) * 100;
                                    $moy = round($moy, 2);
                                } else {
                                    $moy = 0;
                                }

                                $query_km = $db->query("SELECT sum(kilometrage) as sum_kilometrage FROM kilometrage WHERE id_bus='$id_bus' AND date_kilometrage >= '$date_debut' AND date_kilometrage <= '$date_fin' ORDER BY date_kilometrage ASC ");
                                $total_km_exp = 0;
                                foreach ($query_km as $cr) {
                                    $total_km_exp = $cr["sum_kilometrage"] ? floatval($cr["sum_kilometrage"]) : 0;
                                }

                                if ($qte_totale_cal != 0 && $total_km_exp != 0) {
                                    $moy_exp = ($qte_totale_cal / $total_km_exp) * 100;
                                    $moy_exp = round($moy_exp, 2);
                                } else {
                                    $moy_exp = 0;
                                }

                                // Only add to tab_all if there's at least some data (carburant or kilometrage)
                                if ($qte_totale > 0 || $total_km > 0 || $total_km_exp > 0) {
                                    $tab_all[$i]["moy"] = $moy;
                                    $tab_all[$i]["km_total"] = $total_km;
                                    $tab_all[$i]["qte_totale"] = $qte_totale;
                                    $tab_all[$i]["id_bus"] = $id_bus;
                                    $tab_all[$i]["total_km_exp"] = $total_km_exp;
                                    $tab_all[$i]["moy_exp"] = $moy_exp;
                                    
                                    // Debug: Log what's being added
                                    
                                    $i++;
                                }
                            }
                            
                            error_log("DEBUG: tab_bus count: " . count($tab_bus));
                            error_log("DEBUG: tab_all count: " . count($tab_all));
                            error_log("DEBUG: tab_all data: " . print_r($tab_all, true));
                            // Initialize sum variables BEFORE processing
                            $qte_totale_sum = 0;
                            $km_total_sum = 0;
                            $total_km_exp_sum = 0;
                            $moy_sum = 0;
                            $total_km_exp = 0;
                            $qte_totale = 0;
                            $count_vehicles = 0;
                            
                            // Calculate sums for KPI metrics from tab_all
                            if (isset($tab_all) && is_array($tab_all) && count($tab_all) > 0) {
                                foreach ($tab_all as $key => $value) {
                                    if (!isset($value["id_bus"])) {
                                        error_log("DEBUG: Skipping entry without id_bus at key: " . $key);
                                        continue;
                                    }
                                    
                                    // Calculate sums for KPI metrics (ensure values are numeric)
                                    $qte_val = isset($value["qte_totale"]) ? floatval($value["qte_totale"]) : 0;
                                    $km_val = isset($value["km_total"]) ? floatval($value["km_total"]) : 0;
                                    $km_exp_val = isset($value["total_km_exp"]) ? floatval($value["total_km_exp"]) : 0;
                                    
                                    $qte_totale_sum += $qte_val;
                                    $km_total_sum += $km_val;
                                    $moy_sum += isset($value["moy"]) ? floatval($value["moy"]) : 0;
                                    $total_km_exp_sum += $km_exp_val;
                                    $count_vehicles++;
                                }
                                
                            } else {
                                error_log("DEBUG: tab_all is empty or not set!");
                            }
                            
                            // Calculate final values for display
                            $nombre_vehicules = isset($count_vehicles) ? $count_vehicles : (isset($tab_all) ? count($tab_all) : 0);
                            $moyenne_conso_calculee = ($nombre_vehicules > 0 && $km_total_sum > 0) ? round(($qte_totale_sum / $km_total_sum) * 100, 2) : 0;
                            
                            ?>
                            
                            <!-- Statistics Cards - Moved here AFTER data processing -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-blue-100 text-sm font-medium">Total Carburant</p>
                                            <p class="text-3xl font-bold mt-2" id="stat-total-carburant">
                                                <?php echo isset($qte_totale_sum) && $qte_totale_sum > 0 ? formatQuantity($qte_totale_sum) : '0'; ?> <span class="text-lg font-normal">L</span>
                                            </p>
                                        </div>
                                        <div class="bg-white/20 rounded-full p-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-fuel-icon lucide-fuel"><path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 4 0v-6.998a2 2 0 0 0-.59-1.42L18 5"/><path d="M14 21V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16"/><path d="M2 21h13"/><path d="M3 9h11"/></svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-xl">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-emerald-100 text-sm font-medium">Distance Totale (Index)</p>
                                            <p class="text-3xl font-bold mt-2" id="stat-distance-totale">
                                                <?php echo isset($km_total_sum) && $km_total_sum > 0 ? number_format($km_total_sum, 0, ',', ' ') : '0'; ?> <span class="text-lg font-normal">km</span>
                                            </p>
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
                                            <p class="text-3xl font-bold mt-2" id="stat-moyenne-conso">
                                                <?php echo isset($moyenne_conso_calculee) && $moyenne_conso_calculee > 0 ? number_format($moyenne_conso_calculee, 2, ',', ' ') : '0'; ?> <span class="text-lg font-normal">L/100km</span>
                                            </p>
                                        </div>
                                        <div class="bg-white/20 rounded-full p-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-gauge-icon lucide-circle-gauge"><path d="M15.6 2.7a10 10 0 1 0 5.7 5.7"/><circle cx="12" cy="12" r="2"/><path d="M13.4 10.6 19 5"/></svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-2xl p-6 text-white shadow-xl">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-amber-100 text-sm font-medium">Nombre de Véhicules</p>
                                            <p class="text-3xl font-bold mt-2" id="stat-nombre-vehicules">
                                                <?php echo isset($nombre_vehicules) ? $nombre_vehicules : '0'; ?>
                                            </p>
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
                            
                            <!-- Now display the table -->
                            <?php foreach ($tab_all as $key => $value) { ?>
                                <tr>
                                    <td>
                                        <?php
                                        $id_bus = $value["id_bus"];
                                        $item = $db->query("SELECT * FROM bus WHERE id_bus='$id_bus'");
                                        foreach ($item as $cr) {
                                            $matricule_interne = $cr["matricule_interne"];
                                        }
                                        echo $matricule_interne;
                                        ?>
                                    </td>
                                    <td><?php
                                        echo formatQuantity($value["qte_totale"]);
                                        ?>
                                    </td>
                                    <td><?php
                                        echo $value["km_total"];
                                        ?></td>
                                    <td><?php
                                        echo $value["moy"];
                                        ?></td>

                                    <td><?php
                                        echo $value["total_km_exp"];
                                        ?></td>

                                    <td><?php
                                        echo $value["moy_exp"];
                                        ?></td>
                                </tr>
                            <?php } ?>
                            <tr style="font-weight:bold">
                                <td>Totale : </td>
                                <td>
                                    <?php echo formatQuantity($qte_totale_sum); ?>
                                </td>
                                <td>
                                    <?php echo $km_total_sum; ?>
                                </td>
                                <td>
                                    <?php
                                    echo round($moy_sum / $i, 2);
                                    ?>
                                </td>
                                <td>
                                    <?php echo $total_km_exp_sum; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($total_km_exp_sum != 0) {
                                        echo round(($qte_totale_sum / $total_km_exp_sum ) * 100, 2);
                                    } else {
                                        echo 0;
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php
                    // Fetch Oil Statistics by Type for the selected period and buses
                    $busIdsForStats = implode(',', array_map('intval', $tab_bus));
                    
                    // Oil Statistics
                    $oilStatsQuery = $db->query("
                        SELECT 
                            ot.name as oil_type,
                            ot.usageOil,
                            mo.oil_operation,
                            SUM(mo.quantity) as total_quantity
                        FROM maintenance_operations mo
                        JOIN maintenance_records mr ON mo.record_id = mr.id
                        JOIN oil_types ot ON mo.oil_type_id = ot.id
                        WHERE mr.date >= '$date_debut' AND mr.date <= '$date_fin'
                        AND mr.id_bus IN ($busIdsForStats)
                        AND mo.type = 'Huile' AND mo.quantity > 0
                        AND mo.oil_operation IN ('Vidange', 'Apoint')
                        GROUP BY ot.id, ot.name, ot.usageOil, mo.oil_operation
                        ORDER BY ot.name
                    ");
                    $oilStatsRaw = $oilStatsQuery ? $oilStatsQuery->fetchAll(PDO::FETCH_ASSOC) : [];
                    
                    $oilStatsData = [];
                    $totalOilStats = 0;
                    foreach ($oilStatsRaw as $row) {
                        $oilType = $row['oil_type'];
                        if (!isset($oilStatsData[$oilType])) {
                            $oilStatsData[$oilType] = ['Vidange' => 0, 'Apoint' => 0];
                        }
                        $oilStatsData[$oilType][$row['oil_operation']] = floatval($row['total_quantity']);
                        $totalOilStats += floatval($row['total_quantity']);
                    }
                    
                    // Liquide Statistics
                    $liquideStatsQuery = $db->query("
                        SELECT 
                            l.type as liquide_type,
                            SUM(mo.quantity) as total_quantity
                        FROM maintenance_operations mo
                        JOIN maintenance_records mr ON mo.record_id = mr.id
                        JOIN liquides l ON mo.liquide_type_id = l.id
                        WHERE mr.date >= '$date_debut' AND mr.date <= '$date_fin'
                        AND mr.id_bus IN ($busIdsForStats)
                        AND mo.type = 'Liquide' AND mo.quantity > 0
                        GROUP BY l.id, l.type
                        ORDER BY l.type
                    ");
                    $liquideStats = $liquideStatsQuery ? $liquideStatsQuery->fetchAll(PDO::FETCH_ASSOC) : [];
                    
                    // Filter Statistics
                    $filterStatsQuery = $db->query("
                        SELECT 
                            ft.name as filter_type,
                            ft.usageFilter,
                            mo.filter_operation,
                            COUNT(*) as count
                        FROM maintenance_operations mo
                        JOIN maintenance_records mr ON mo.record_id = mr.id
                        JOIN filter_types ft ON mo.filter_type_id = ft.id
                        WHERE mr.date >= '$date_debut' AND mr.date <= '$date_fin'
                        AND mr.id_bus IN ($busIdsForStats)
                        AND mo.type = 'Filter' AND mo.filter_operation IS NOT NULL
                        GROUP BY ft.id, ft.name, ft.usageFilter, mo.filter_operation
                        ORDER BY ft.name
                    ");
                    $filterStats = $filterStatsQuery ? $filterStatsQuery->fetchAll(PDO::FETCH_ASSOC) : [];
                    ?>
                    
                    <!-- Maintenance Statistics Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Oil Statistics by Type -->
                        <div class="bg-white rounded-2xl shadow-xl p-6">
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Statistiques des Huiles par Type</h3>
                                    <p class="text-xs text-gray-400">Vidange vs Appoint</p>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-amber-50 via-yellow-50 to-white px-3 py-1.5 text-sm font-semibold text-amber-700 shadow-sm">
                                    <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>
                                    <span><?= formatQuantity($totalOilStats) ?> L</span>
                                </div>
                            </div>
                            <div style="height: 300px;">
                                <canvas id="oilStatsChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Liquide Statistics by Type -->
                        <div class="bg-white rounded-2xl shadow-xl p-6">
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Consommation de Liquide par Type</h3>
                                    <p class="text-xs text-gray-400">Quantités utilisées</p>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-purple-50 via-pink-50 to-white px-3 py-1.5 text-sm font-semibold text-purple-700 shadow-sm">
                                    <svg class="h-4 w-4 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>
                                    <span><?= formatQuantity(array_sum(array_column($liquideStats, 'total_quantity'))) ?> L</span>
                                </div>
                            </div>
                            <div style="height: 300px;">
                                <canvas id="liquideStatsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Statistics -->
                    <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Statistiques des Filtres</h3>
                                <p class="text-xs text-gray-400">Changement vs Nettoyage</p>
                            </div>
                        </div>
                        <div style="height: 350px;">
                            <canvas id="filterStatsChart"></canvas>
                        </div>
                    </div>

                    <script>
                        // Update statistics cards with calculated values
                        <?php 
                        // Debug: Check if variables exist and have values
                        // Ensure variables are initialized
                        if (!isset($tab_all)) $tab_all = [];
                        if (!isset($qte_totale_sum)) $qte_totale_sum = 0;
                        if (!isset($km_total_sum)) $km_total_sum = 0;
                        if (!isset($total_km_exp_sum)) $total_km_exp_sum = 0;
                        if (!isset($moy_sum)) $moy_sum = 0;
                        if (!isset($i)) $i = 0;
                        
                        // Use count_vehicles if available, otherwise count tab_all
                        $nombre_vehicules = isset($count_vehicles) ? $count_vehicles : (isset($tab_all) ? count($tab_all) : 0);
                        $moyenne_conso_calculee = ($nombre_vehicules > 0 && $km_total_sum > 0) ? round(($qte_totale_sum / $km_total_sum) * 100, 2) : 0;
                        
                        // Ensure all variables are set
                        if (!isset($qte_totale_sum)) $qte_totale_sum = 0;
                        if (!isset($km_total_sum)) $km_total_sum = 0;
                        if (!isset($total_km_exp_sum)) $total_km_exp_sum = 0;
                        if (!isset($moy_sum)) $moy_sum = 0;
                        
                        // Debug: Log final values before JavaScript
                        error_log("DEBUG FINAL VALUES:");
                        error_log("  qte_totale_sum: $qte_totale_sum");
                        error_log("  km_total_sum: $km_total_sum");
                        error_log("  total_km_exp_sum: $total_km_exp_sum");
                        error_log("  nombre_vehicules: $nombre_vehicules");
                        error_log("  moyenne_conso_calculee: $moyenne_conso_calculee");
                        error_log("  chart_vehicles count: " . (isset($chart_vehicles) ? count($chart_vehicles) : 0));
                        error_log("  chart_moy_index count: " . (isset($chart_moy_index) ? count($chart_moy_index) : 0));
                        ?>
                        // Function to update KPI metrics
                        function updateKPIMetrics() {
                            const statTotalCarburant = document.getElementById('stat-total-carburant');
                            const statDistanceTotale = document.getElementById('stat-distance-totale');
                            const statMoyenneConso = document.getElementById('stat-moyenne-conso');
                            const statNombreVehicules = document.getElementById('stat-nombre-vehicules');
                            
                            // Debug: Log PHP values passed to JavaScript
                            const phpValues = {
                                qte_totale_sum: <?php echo $qte_totale_sum; ?>,
                                km_total_sum: <?php echo $km_total_sum; ?>,
                                moyenne_conso: <?php echo $moyenne_conso_calculee; ?>,
                                nombre_vehicules: <?php echo $nombre_vehicules; ?>,
                                chart_vehicles: <?php echo isset($chart_vehicles) ? json_encode($chart_vehicles) : '[]'; ?>,
                                chart_moy_index: <?php echo isset($chart_moy_index) ? json_encode($chart_moy_index) : '[]'; ?>
                            };
                            
                            console.log('=== DEBUG KPI VALUES ===');
                            console.log('PHP Values passed to JavaScript:', phpValues);
                            console.log('DOM Elements found:', {
                                statTotalCarburant: !!statTotalCarburant,
                                statDistanceTotale: !!statDistanceTotale,
                                statMoyenneConso: !!statMoyenneConso,
                                statNombreVehicules: !!statNombreVehicules
                            });
                            
                            if (statTotalCarburant) {
                                statTotalCarburant.innerHTML = '<?php echo formatQuantity($qte_totale_sum); ?> <span class="text-lg font-normal">L</span>';
                                console.log('Updated statTotalCarburant');
                            } else {
                                console.error('statTotalCarburant element not found!');
                            }
                            
                            if (statDistanceTotale) {
                                statDistanceTotale.innerHTML = '<?php echo number_format($km_total_sum, 0, ',', ' '); ?> <span class="text-lg font-normal">km</span>';
                                console.log('Updated statDistanceTotale');
                            } else {
                                console.error('statDistanceTotale element not found!');
                            }
                            
                            if (statMoyenneConso) {
                                statMoyenneConso.innerHTML = '<?php echo $moyenne_conso_calculee; ?> <span class="text-lg font-normal">L/100km</span>';
                                console.log('Updated statMoyenneConso');
                            } else {
                                console.error('statMoyenneConso element not found!');
                            }
                            
                            if (statNombreVehicules) {
                                statNombreVehicules.textContent = '<?php echo $nombre_vehicules; ?>';
                                console.log('Updated statNombreVehicules');
                            } else {
                                console.error('statNombreVehicules element not found!');
                            }
                        }
                        
                        // Update immediately if DOM is ready, otherwise wait for DOMContentLoaded
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', updateKPIMetrics);
                        } else {
                            // DOM is already ready
                            updateKPIMetrics();
                        }
                        
                        // Also try updating after a short delay as fallback
                        setTimeout(updateKPIMetrics, 100);
                        
                        // Wait for DOM to be ready for charts
                        document.addEventListener('DOMContentLoaded', function() {
                            
                            // Oil Statistics Chart
                            <?php if (!isset($oilStatsData)) $oilStatsData = []; ?>
                            const oilStatsData = <?= json_encode($oilStatsData) ?>;
                            const oilLabels = Object.keys(oilStatsData);
                            const vidangeData = oilLabels.map(type => oilStatsData[type].Vidange || 0);
                            const apointData = oilLabels.map(type => oilStatsData[type].Apoint || 0);
                            
                            if (oilLabels.length > 0) {
                                const oilChartEl = document.getElementById('oilStatsChart');
                                if (oilChartEl) {
                                    new Chart(oilChartEl.getContext('2d'), {
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
                            }
                            
                            // Liquide Statistics Chart
                            <?php if (!isset($liquideStats)) $liquideStats = []; ?>
                            const liquideStats = <?= json_encode($liquideStats) ?>;
                            if (liquideStats.length > 0) {
                                const liquideChartEl = document.getElementById('liquideStatsChart');
                                if (liquideChartEl) {
                                    new Chart(liquideChartEl.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: liquideStats.map(d => d.liquide_type || 'Non spécifié'),
                                            datasets: [{ label: 'Quantité (L)', data: liquideStats.map(d => parseFloat(d.total_quantity) || 0), backgroundColor: 'rgba(168, 85, 247, 0.8)', borderColor: 'rgba(168, 85, 247, 1)', borderWidth: 2, borderRadius: 6 }]
                                        },
                                        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { callback: v => v + ' L' } } } }
                                    });
                                }
                            }
                            
                            // Filter Statistics Chart
                            <?php if (!isset($filterStats)) $filterStats = []; ?>
                            const filterStats = <?= json_encode($filterStats) ?>;
                            const filterTypes = [...new Set(filterStats.map(d => d.filter_type))];
                            const changementData = filterTypes.map(type => { const item = filterStats.find(d => d.filter_type === type && d.filter_operation === 'Changement'); return item ? parseInt(item.count) : 0; });
                            const nettoyageData = filterTypes.map(type => { const item = filterStats.find(d => d.filter_type === type && d.filter_operation === 'Nettoyage'); return item ? parseInt(item.count) : 0; });
                            
                            if (filterTypes.length > 0) {
                                const filterChartEl = document.getElementById('filterStatsChart');
                                if (filterChartEl) {
                                    new Chart(filterChartEl.getContext('2d'), {
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
                            }
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
        $("#search").click(function () {
            var bus = $("#bus").val();
            var date_debut = $("#date_debut").val();
            var date_fin = $("#date_fin").val();
            if (!bus || bus.length === 0 || date_debut == "" || date_fin == "") {
                alert("Veuillez vérifier les critères de votre recherche");
            } else {
                $("#search_form").submit();
            }
        });
        // Setup - add a text input to each footer cell
        $('#tab tfoot th').each(function (i) {
            var title = $('#example thead th').eq($(this).index()).text();
            $(this).html('<input type="text" placeholder="" data-index="' + i + '" />');
        });

        var ch = "Rapport consommation par bus";
        var table = $('#tab').DataTable({
            "bSort": false,
            dom: 'Bfrtip',
            bPaginate: false,
            bLengthChange: false,
            searching: true,
            buttons: [
                {
                    extend: 'print',
                    title: ch,
                    text: 'Imprimmer',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'excelHtml5',
                    title: ch,
                    text: 'Exporter Excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    title: ch,
                    text: 'Exporter PDF',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
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