<?php
require 'header.php' ?>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="container">
            <div class="mx-auto flex flex-col gap-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="page-title">Rapport de consommation carburant par agence</h1>
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
                        <form method="POST" action="<?= url('rapport-consommation-agence') ?>" role="form" id="search_form" class="search-form">
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
                    
                    
                    <br><br>
                    <table class="table table-striped table-bordered" style="width:100%" id="tab">
                        <thead>
                            <tr>
                                <th>Véhicule</th>
                                <th>Qté</th>
                                <th>Tot. Km</th>
                                <th>Moy CO %</th>
                                <th>Km (Exp)</th>
                                <th>Moy CO (Exp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                function calculDiff($tab) {
                                    $diff = 0;
                                    for ($i = 0; $i < count($tab) - 1; $i++) {
                                        $diff += $tab[$i + 1] - $tab[$i];
                                    }
                                    return $diff;
                                }

                                $query_station = $db->query(
                                        "SELECT * FROM  station"
                                );
                                $liste_station = $query_station->fetchAll(PDO::FETCH_ASSOC);

                                $tab_station = array();

                                foreach ($liste_station as $item) {
                                    $tab_station[] = $item["id_station"];
                                }
                                $tab_all = array();
                                $qte_totale = 0;
                                $tab_carburant = array();
                                $tab_bon = array();
                                $total_km = 0;
                                foreach ($tab_station as $it) {
                                    $tab_bus = array();
                                    $query_bus = $db->query(
                                            "SELECT * FROM  bus WHERE id_station='$it' "
                                    );
                                    $liste_bus = $query_bus->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($liste_bus as $xs) {
                                        $tab_bus[] = $xs["id_bus"];
                                    }
                                    
                                    $qte_totale = 0;
                                    $total_km = 0;
                                    $total_km_exp = 0;
                                    $tab_carburant = array();
                                    
                                    foreach ($tab_bus as $item) {
                                        $id_bus = $item;

                                        $query_carburant = $db->query(
                                                "SELECT * FROM  carburant INNER JOIN bus ON carburant.id_bus=bus.id_bus WHERE carburant.id_bus='$item' AND date >= '$date_debut' AND date <= '$date_fin'  ORDER BY id_carburant ASC"
                                        );
                                        $liste_carburant = $query_carburant->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($liste_carburant as $key => $value) {
                                            $tab_carburant[] = $value["index_km"];
                                            $qte_totale = $qte_totale + $value["qte_go"];
                                        }
                                    }
                                  
                                    $tab_diff = $tab_carburant;
                                    sort($tab_diff);
                                    
                                    if (count($tab_diff) != 0) {
                                        $total_km = $total_km + calculDiff($tab_diff);
                                    } else {
                                        $total_km = $total_km + 0;
                                    }
                                    
                                    // Calculate total km_exp for this station
                                    $total_km_exp = 0;
                                    foreach ($tab_bus as $bus_id) {
                                        $query_km = $db->query("SELECT * FROM kilometrage WHERE id_bus='$bus_id' AND date_kilometrage >= '$date_debut' AND date_kilometrage <= '$date_fin' ");
                                        foreach ($query_km as $cr) {
                                            $total_km_exp = $total_km_exp + $cr["kilometrage"];
                                        }
                                    }

                                    if ($qte_totale != 0 && $total_km != 0) {
                                        $moy = ($qte_totale / $total_km) * 100;
                                        $moy = round($moy, 2);
                                    } else {
                                        $moy = 0;
                                    }

                                    if ($qte_totale != 0 && $total_km_exp != 0) {
                                        $moy_exp = ($qte_totale / $total_km_exp) * 100;
                                        $moy_exp = round($moy_exp, 2);
                                    } else {
                                        $moy_exp = 0;
                                    }

                                    $tab_all[$it]["moy"] = $moy;
                                    $tab_all[$it]["km_total"] = $total_km;
                                    $tab_all[$it]["qte_totale"] = $qte_totale;
                                    $tab_all[$it]["id_station"] = $it;
                                    $tab_all[$it]["total_km_exp"] = $total_km_exp;
                                    $tab_all[$it]["moy_exp"] = $moy_exp;
                                }
                            ?>
                            <?php
                            $qte_totale_sum = 0;
                            $km_total_sum = 0;
                            $total_km_exp_sum = 0;
                            $moy_sum = 0;
                            
                            // Prepare data for grouped horizontal bar chart
                            $agences_chart = array();
                            $moyennes_chart = array();
                            $quantites_chart = array();
                            $kilometrage_chart = array();
                            
                            $color_index = 0;
                            foreach ($tab_all as $key => $value) {
                                if ($value["moy"] > 0 || $value["qte_totale"] > 0 || $value["km_total"] > 0) {  // Include agencies with any data
                                    // Get agency name
                                    $station = $value["id_station"];
                                    $item = $db->query("SELECT * FROM station WHERE id_station='$station'");
                                    $lib = "";
                                    foreach ($item as $cr) {
                                        $lib = $cr["lib"];
                                    }
                                    
                                    $agences_chart[] = $lib;
                                    $moyennes_chart[] = $value["moy"];
                                    $quantites_chart[] = $value["qte_totale"];
                                    $kilometrage_chart[] = $value["km_total"];
                                }
                            }
                            
                            // Calculate totals for summary cards
                            $total_kilometrage = array_sum($kilometrage_chart);
                            $total_quantite = array_sum($quantites_chart);
                            $total_moyenne = array_sum($moyennes_chart);
                            $average_moyenne = count($moyennes_chart) > 0 ? round($total_moyenne / count($moyennes_chart), 2) : 0;
                            
                            if (!empty($agences_chart)) {
                            ?>
                            
                            <!-- Summary Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                                <!-- Total Quantité Card -->
                                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-emerald-100 text-sm font-medium mb-1">Quantité Totale</p>
                                            <p class="text-3xl font-bold"><?php echo number_format($total_quantite, 0, ',', ' '); ?></p>
                                            <p class="text-emerald-100 text-xs mt-2">litres</p>
                                        </div>
                                        <div class="bg-white/20 rounded-xl p-3">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Kilométrage Card -->
                                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-blue-100 text-sm font-medium mb-1">Kilométrage Total</p>
                                            <p class="text-3xl font-bold"><?php echo number_format($total_kilometrage, 0, ',', ' '); ?></p>
                                            <p class="text-blue-100 text-xs mt-2">kilomètres</p>
                                        </div>
                                        <div class="bg-white/20 rounded-xl p-3">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Moyenne de Consommation Card -->
                                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-purple-100 text-sm font-medium mb-1">Moyenne de Consommation</p>
                                            <p class="text-3xl font-bold"><?php echo $average_moyenne; ?></p>
                                            <p class="text-purple-100 text-xs mt-2">L/100km</p>
                                        </div>
                                        <div class="bg-white/20 rounded-xl p-3">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nombre d'Agences Card -->
                                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-orange-100 text-sm font-medium mb-1">Nombre d'Agences</p>
                                            <p class="text-3xl font-bold"><?php echo count($agences_chart); ?></p>
                                            <p class="text-orange-100 text-xs mt-2">agences actives</p>
                                        </div>
                                        <div class="bg-white/20 rounded-xl p-3">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart Container -->
                            <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                                <h3 class="text-xl font-semibold text-gray-800 mb-6">Analyse des Agences - Consommation, Quantité et Kilométrage</h3>
                                <div class="relative" style="height: 450px;">
                                    <canvas id="consumptionByAgencyChart"></canvas>
                                </div>
                            </div>

                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const ctx = document.getElementById('consumptionByAgencyChart').getContext('2d');
                                    
                                    const chart = new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: <?php echo json_encode($agences_chart); ?>,
                                            datasets: [
                                                {
                                                    label: 'Kilométrage (km)',
                                                    data: <?php echo json_encode($kilometrage_chart); ?>,
                                                    backgroundColor: 'rgba(59, 130, 246, 0.8)',  // blue
                                                    borderWidth: 2,
                                                    borderRadius: 4,
                                                    barPercentage: 0.8,
                                                    categoryPercentage: 0.7,
                                                    xAxisID: 'x'
                                                },
                                                {
                                                    label: 'Quantité (L)',
                                                    data: <?php echo json_encode($quantites_chart); ?>,
                                                    backgroundColor: 'rgba(16, 185, 129, 0.8)',  // emerald
                                                    borderWidth: 2,
                                                    borderRadius: 4,
                                                    barPercentage: 0.8,
                                                    categoryPercentage: 0.7,
                                                    xAxisID: 'x1'
                                                },
                                                {
                                                    label: 'Moyenne de consommation (L/100km)',
                                                    data: <?php echo json_encode($moyennes_chart); ?>,
                                                    backgroundColor: 'rgba(168, 85, 247, 0.8)',  // purple
                                                    borderWidth: 2,
                                                    borderRadius: 4,
                                                    barPercentage: 0.8,
                                                    categoryPercentage: 0.7,
                                                    xAxisID: 'x2'
                                                }
                                            ]
                                        },
                                        options: {
                                            indexAxis: 'y', // This makes it horizontal
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
                                                        label: function(context) {
                                                            let label = context.dataset.label || '';
                                                            if (label) {
                                                                label += ': ';
                                                            }
                                                            if (context.parsed.x !== null) {
                                                                label += context.parsed.x;
                                                            }
                                                            return label;
                                                        }
                                                    }
                                                }
                                            },
                                            scales: {
                                                x: {
                                                    type: 'linear',
                                                    display: true,
                                                    position: 'bottom',
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Kilométrage (km)',
                                                        font: {
                                                            size: 12,
                                                            weight: '500'
                                                        }
                                                    },
                                                    grid: {
                                                        color: 'rgba(59, 130, 246, 0.1)',
                                                        drawBorder: false
                                                    },
                                                    ticks: {
                                                        font: {
                                                            size: 11
                                                        }
                                                    }
                                                },
                                                x1: {
                                                    type: 'linear',
                                                    display: true,
                                                    position: 'top',
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Quantité (L)',
                                                        font: {
                                                            size: 12,
                                                            weight: '500'
                                                        }
                                                    },
                                                    grid: {
                                                        drawOnChartArea: false, // only show the grid for the first X axis
                                                    },
                                                    ticks: {
                                                        font: {
                                                            size: 11
                                                        }
                                                    }
                                                },
                                                x2: {
                                                    type: 'linear',
                                                    display: true,
                                                    position: 'top',
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Moyenne (L/100km)',
                                                        font: {
                                                            size: 12,
                                                            weight: '500'
                                                        }
                                                    },
                                                    grid: {
                                                        drawOnChartArea: false, // only show the grid for the first X axis
                                                    },
                                                    ticks: {
                                                        font: {
                                                            size: 11
                                                        }
                                                    }
                                                },
                                                y: {
                                                    title: {
                                                        display: true,
                                                        text: 'Agences',
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
                                                        }
                                                    }
                                                }
                                            },
                                            interaction: {
                                                intersect: false,
                                                mode: 'index'
                                            },
                                            layout: {
                                                padding: {
                                                    left: 10,
                                                    right: 20,
                                                    top: 10,
                                                    bottom: 10
                                                }
                                            },
                                            // Ensure all datasets are visible
                                            datasets: {
                                                bar: {
                                                    barPercentage: 0.8,
                                                    categoryPercentage: 0.7
                                                }
                                            }
                                        }
                                    });
                                });
                            </script>
                            <?php } ?>
                            
                            <?php
                            foreach ($tab_all as $key => $value) {
                                ?>
                                <tr>
                                    <td>
                                        <?php
                                        $station = $value["id_station"];
                                        $item = $db->query("SELECT * FROM station WHERE id_station='$station'");
                                        foreach ($item as $cr) {
                                            $lib = $cr["lib"];
                                        }
                                        echo $lib;
                                        ?>
                                    </td>
                                    <td><?php
                                        echo $value["qte_totale"];
                                        $qte_totale_sum = $qte_totale_sum + $value["qte_totale"];
                                        ?>
                                    </td>
                                    <td><?php
                                        echo $value["km_total"];
                                        $km_total_sum = $km_total_sum + $value["km_total"];
                                        ?></td>
                                    <td><?php
                                        echo $value["moy"];
                                        ?></td>
                                    <td><?php
                                        echo $value["total_km_exp"];
                                        $total_km_exp_sum = $total_km_exp_sum + $value["total_km_exp"];
                                        ?></td>

                                    <td><?php
                                        echo $value["moy_exp"];
                                        ?></td>
                                </tr>
                            <?php } ?>

                            <tr style="font-weight:bold">
                                <td>Totale : </td>
                                <td>
                                    <?php echo $qte_totale_sum; ?>
                                </td>
                                <td>
                                    <?php echo $km_total_sum; ?>
                                </td>
                                <td>
                                    <?php
                                    echo round(($qte_totale_sum / $km_total_sum) * 100, 2);
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

                </div>
            </div>
        </div>
        
        <?php
        }
        ?>
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
        var ch = "Etat de suivi de consommation";
        var table = $('#tab').DataTable({
            bPaginate: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bAutoWidth: false,
            searching: false,
            bSort: false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'print',
                    title: ch,
                    text: 'Imprimmer',
                    exportOptions: {
                        columns: [0, 1, 2, 3]
                    }
                },
                {
                    extend: 'excelHtml5',
                    title: ch,
                    text: 'Exporter Excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    title: ch,
                    text: 'Exporter PDF',
                    exportOptions: {
                        columns: [0, 1, 2, 3]
                    }
                }
            ]
        });
    });
</script>
</body>
</html>