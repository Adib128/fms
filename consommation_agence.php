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
                        <h1 class="page-title">Rapport de sortie par agence</h1>
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
                                
                                <!-- Type Field -->
                                <div class="space-y-2">
                                    <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                                    <select class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" name="type" id="type" required>
                                        <option value="Vrac" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Vrac') ? 'selected' : ''; ?>>Vrac</option>
                                        <option value="Carte" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Carte') ? 'selected' : ''; ?>>Carte</option>
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
                    $type= $_POST["type"];
                    ?> 
                    
                    <!-- Chart Container -->
                    <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                        <h3 class="text-xl font-semibold text-gray-800 mb-6">Quantité par Agence</h3>
                        <div class="relative" style="height: 400px;">
                            <canvas id="quantityByAgencyChart"></canvas>
                        </div>
                    </div>

                    <table class="table table-striped table-bordered" style="width:100%" id="tab">
                        <thead>
                            <tr>
                                <th>Agence</th>
                                <th>Qté</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            function calculDiff($tab) {
                                $diff = 0;
                                $ln = count($tab) - 1;
                                $diff = $tab[$ln] - $tab[0];
                                return $diff;
                            }

                            // Prepare data for vertical bar chart
                            $agences_chart = array();
                            $quantites_chart = array();
                            $colors_chart = array();
                            
                            // Define different colors for bars
                            $bar_colors = [
                                'rgba(59, 130, 246, 0.8)',   // blue
                                'rgba(16, 185, 129, 0.8)',   // emerald
                                'rgba(168, 85, 247, 0.8)',   // purple
                                'rgba(251, 146, 60, 0.8)',   // orange
                                'rgba(239, 68, 68, 0.8)',    // red
                                'rgba(236, 72, 153, 0.8)',   // pink
                                'rgba(34, 197, 94, 0.8)',    // green
                                'rgba(99, 102, 241, 0.8)',   // indigo
                            ];
                            
                            $color_index = 0;

                            $query_station = $db->query(
                                    "SELECT * FROM  station"
                            );
                            $liste_station = $query_station->fetchAll(PDO::FETCH_ASSOC);
                           
                            foreach ($liste_station as $it) {
                                $id_station = $it["id_station"] ; 
                                $query_doc = $db->query(
                                        "SELECT * FROM  doc_carburant WHERE id_station='$id_station' AND type='$type' AND date >= '$date_debut' AND date <= '$date_fin'"
                                );
                                 $qte_totale_doc = 0 ; 
                                foreach ($query_doc as $item) {
                                    $id_doc_carburant = $item["id_doc_carburant"];
                                    $query_carburant = $db->query(
                                            "SELECT SUM(qte_go) as qte_tot FROM  carburant WHERE id_doc_carburant='$id_doc_carburant'"
                                    );
                                   
                                    $liste_carburant = $query_carburant->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($liste_carburant as $key => $value) {
                                        $qte_totale = $value["qte_tot"];
                                    }

                                    $qte_totale_doc = $qte_totale_doc + $qte_totale ; 
                                    ?>

                                    <?php

                                }

                                // Add data to chart arrays if there's quantity data
                                if ($qte_totale_doc > 0) {
                                    $station = $it["id_station"];
                                    $item_station = $db->query("SELECT * FROM station WHERE id_station='$station'");
                                    foreach ($item_station as $cr) {
                                        $lib = $cr["lib"];
                                    }
                                    
                                    $agences_chart[] = $lib;
                                    $quantites_chart[] = $qte_totale_doc;
                                    $colors_chart[] = $bar_colors[$color_index % count($bar_colors)];
                                    $color_index++;
                                }
                                ?>
                                <tr>
                                    <td>
                                       <?php
                                        $station = $it["id_station"];
                                        $item_station = $db->query("SELECT * FROM station WHERE id_station='$station'");
                                        foreach ($item_station as $cr) {
                                            $lib = $cr["lib"];
                                        }
                                        echo $lib;
                                        ?>
                                    </td>
                                    <td>
                                    <?php echo $qte_totale_doc ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- Chart JavaScript -->
                    <?php if (!empty($agences_chart)) { ?>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const ctx = document.getElementById('quantityByAgencyChart').getContext('2d');
                            
                            const chart = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: <?php echo json_encode($agences_chart); ?>,
                                    datasets: [{
                                        label: 'Quantité',
                                        data: <?php echo json_encode($quantites_chart); ?>,
                                        backgroundColor: <?php echo json_encode($colors_chart); ?>,
                                        borderWidth: 2,
                                        borderRadius: 6,
                                        barThickness: 50
                                    }]
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
                                                label: function(context) {
                                                    return 'Quantité: ' + context.parsed.y + ' L';
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        x: {
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
                                        },
                                        y: {
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
                                                color: 'rgba(0, 0, 0, 0.05)',
                                                drawBorder: false
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
                                    }
                                }
                            });
                        });
                    </script>
                    <?php } ?>

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
            var type = $("#type").val();
            if (date_debut == "" || date_fin == "" || type == "") {
                alert("Vueillez verifier les critére de votre recherche");
                e.preventDefault();
                return false;
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