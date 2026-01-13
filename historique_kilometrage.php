<?php
require 'header.php' ?>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="container">
            <div class="mx-auto flex flex-col gap-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="page-title">Historique kilométrage</h1>
                        <p class="text-sm text-slate-500">Consulter l'historique des kilométrages par véhicule et période</p>
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
                                
                                <!-- Bus Field -->
                                <div class="space-y-2">
                                    <label for="bus" class="block text-sm font-medium text-gray-700">Véhicule</label>
                                    <select class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" name="bus" id="bus" required>
                                        <option value="">Choisir un véhicule</option>
                                        <option value="all">Tous les bus</option>
                                        <?php
                                        $liste_bus = $db->query("SELECT * FROM bus ORDER BY id_bus ASC");
                                        foreach ($liste_bus as $row) {
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
                if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                    $date_de = $_POST["date_debut"];
                    $date_a = $_POST["date_fin"];
                    $bus = $_POST["bus"];
                    
                    // Base SQL query
                    $base_sql = "SELECT k.id_kilometrage, k.date_kilometrage, k.kilometrage, b.matricule_interne, b.id_bus, COALESCE(s.lib, '-') AS station_lib, b.id_station 
                                 FROM kilometrage k 
                                 INNER JOIN bus b ON k.id_bus = b.id_bus 
                                 LEFT JOIN station s ON b.id_station = s.id_station 
                                 WHERE k.date_kilometrage >= '$date_de' AND k.date_kilometrage <= '$date_a'";
                    
                    if ($bus != "all") {
                        $base_sql .= " AND k.id_bus='$bus'";
                    }
                    
                    // Get total records count
                    $count_sql = "SELECT COUNT(*) as total FROM ($base_sql) as count_query";
                    $count_result = $db->query($count_sql);
                    $total_records = $count_result->fetch()['total'];
                    
                    // Prepare chart data
                    $chart_data = [];
                    $chart_labels = [];
                    $total_km = 0;
                    $record_count = 0;
                    
                    // Get data for chart
                    $chart_sql = $base_sql . " ORDER BY k.date_kilometrage ASC";
                    $liste = $db->query($chart_sql);
                    
                    // Process data for chart
                    foreach ($liste as $row) {
                        $date = date_create($row["date_kilometrage"]);
                        $chart_labels[] = date_format($date, "d/m");
                        $chart_data[] = (int)$row["kilometrage"];
                        $total_km += $row["kilometrage"];
                        $record_count++;
                    }
                    
                    $avg_daily = $record_count > 0 ? $total_km / $record_count : 0;
                    ?>

                    <!-- Chart Panel -->
                    <div class="panel">
                        <div class="panel-heading">
                            <span>Évolution du kilométrage</span>
                            <span class="badge">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v18h18" />
                                    <path d="m19 9-5 5-4-4-3 3" />
                                </svg>
                                Graphique
                            </span>
                        </div>
                        <div class="panel-body">
                            <?php if ($record_count > 0): ?>
                                <!-- Summary Cards -->
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-blue-100 text-sm font-medium">Total kilométrage</p>
                                                <p class="text-3xl font-bold mt-2"><?php echo number_format($total_km, 0, ',', ' '); ?> <span class="text-lg font-normal">km</span></p>
                                            </div>
                                            <div class="bg-white/20 rounded-full p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 8c0 3.613-3.869 7.429-5.393 8.795a1 1 0 0 1-1.214 0C9.87 15.429 6 11.613 6 8a6 6 0 0 1 12 0"/><circle cx="12" cy="8" r="2"/><path d="M8.714 14h-3.71a1 1 0 0 0-.948.683l-2.004 6A1 1 0 0 0 3 22h18a1 1 0 0 0 .948-1.316l-2-6a1 1 0 0 0-.949-.684h-3.712"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-xl">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-emerald-100 text-sm font-medium">Moyenne</p>
                                                <p class="text-3xl font-bold mt-2"><?php echo number_format($avg_daily, 0, ',', ' '); ?> <span class="text-lg font-normal">km</span></p>
                                            </div>
                                            <div class="bg-white/20 rounded-full p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M15.6 2.7a10 10 0 1 0 5.7 5.7"/><circle cx="12" cy="12" r="2"/><path d="M13.4 10.6 19 5"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-purple-100 text-sm font-medium">Enregistrements</p>
                                                <p class="text-3xl font-bold mt-2"><?php echo $record_count; ?></p>
                                            </div>
                                            <div class="bg-white/20 rounded-full p-3">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                    <polyline points="14 2 14 8 20 8"></polyline>
                                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                                    <polyline points="10 9 9 9 8 9"></polyline>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-2xl p-6 text-white shadow-xl">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-amber-100 text-sm font-medium">Période</p>
                                                <p class="text-3xl font-bold mt-2"><?php echo $record_count; ?> <span class="text-lg font-normal">jours</span></p>
                                            </div>
                                            <div class="bg-white/20 rounded-full p-3">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Chart Container -->
                                <div class="bg-white p-6 rounded-lg border border-gray-200" style="height: 500px; position: relative;">
                                    <canvas id="kilometrageChart"></canvas>
                                </div>
                                
                                <!-- Chart Data for JavaScript -->
                                <script>
                                    const chartLabels = <?php echo json_encode($chart_labels); ?>;
                                    const chartData = <?php echo json_encode($chart_data); ?>;
                                    const chartTitle = "<?php echo ($bus == 'all') ? 'Évolution globale du kilométrage' : 'Kilométrage - Bus ' . $bus; ?>";
                                    const vehicleCount = <?php echo $record_count; ?>;
                                    const totalKm = <?php echo $total_km; ?>;
                                </script>
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

                <!-- Results Panel - Only visible after search -->
                <?php if ($_SERVER["REQUEST_METHOD"] == 'POST'): ?>
                <div class="panel">
                    <div class="panel-heading">
                        <span>Liste des enregistrements kilométrage effectué</span>
                        <span class="badge">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 11H3m6 0v6m0-6l-6 6"/>
                                <path d="M15 13h6m-6 0v-6m0 6l6-6"/>
                            </svg>
                            Données
                        </span>
                    </div>
                    <div class="panel-body">
                        <div class="overflow-x-auto">
                            <table class="table table-striped table-bordered" id="kilometrageTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Agence</th>
                                        <th>Véhicule</th>
                                        <th>Kilométrage (Km)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via DataTables AJAX -->
                                </tbody>
                                <tfoot>
                                    <tr class="font-bold bg-gray-100">
                                        <td>Total</td>
                                        <td></td>
                                        <td></td>
                                        <td id="totalKmCell">0 km</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Reset form functionality
        $('#reset_search').on('click', function() {
            $('#search_form')[0].reset();
            // Clear any form submission
            window.location.href = window.location.pathname;
        });

        // Initialize Professional Kilométrage Chart if data exists
        function initializeChart() {
            const canvas = document.getElementById('kilometrageChart');
            if (!canvas) {
                console.log('Chart canvas not found');
                return;
            }
            
            if (typeof chartLabels === 'undefined' || typeof chartData === 'undefined' || !chartLabels.length || !chartData.length) {
                console.log('Chart data not available');
                return;
            }
            
            // Destroy existing chart if it exists
            if (window.kilometrageChartInstance) {
                window.kilometrageChartInstance.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            
            // Calculate professional metrics
            const minKm = Math.min(...chartData);
            const maxKm = Math.max(...chartData);
            const rangeKm = maxKm - minKm;
            const avgKm = chartData.reduce((a, b) => a + b, 0) / chartData.length;
            
            // Calculate daily averages and trends
            const dailyChanges = [];
            for (let i = 1; i < chartData.length; i++) {
                dailyChanges.push(chartData[i] - chartData[i - 1]);
            }
            const avgDailyChange = dailyChanges.length > 0 ? 
                dailyChanges.reduce((a, b) => a + b, 0) / dailyChanges.length : 0;
            
            window.kilometrageChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Kilométrage cumulé',
                            data: chartData,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.08)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointStyle: 'circle'
                        },
                        {
                            label: 'Moyenne',
                            data: Array(chartData.length).fill(avgKm),
                            borderColor: '#dc2626',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [8, 4],
                            fill: false,
                            tension: 0,
                            pointRadius: 0,
                            pointHoverRadius: 0
                        },
                        {
                            label: 'Tendance',
                            data: calculateLinearTrend(chartData),
                            borderColor: '#059669',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [4, 2],
                            fill: false,
                            tension: 0,
                            pointRadius: 0,
                            pointHoverRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: chartTitle,
                            font: {
                                size: 18,
                                weight: 'bold',
                                family: 'system-ui'
                            },
                            color: '#1f2937',
                            padding: {
                                top: 10,
                                bottom: 30
                            }
                        },
                        subtitle: {
                            display: true,
                            text: [
                                `Période: ${chartLabels[0]} - ${chartLabels[chartLabels.length - 1]}`,
                                `Progression: ${formatNumber(rangeKm)} km | Moyenne: ${formatNumber(avgKm)} km | Variation journalière: ${formatNumber(avgDailyChange)} km`
                            ],
                            font: {
                                size: 12,
                                family: 'system-ui'
                            },
                            color: '#6b7280',
                            padding: {
                                bottom: 20
                            }
                        },
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12,
                                    family: 'system-ui'
                                },
                                boxWidth: 8,
                                boxHeight: 8
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.9)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#2563eb',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            callbacks: {
                                title: function(context) {
                                    return `Date: ${context[0].label}`;
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += formatNumber(context.parsed.y) + ' km';
                                    }
                                    
                                    // Add additional insights for main data line
                                    if (context.datasetIndex === 0 && context.dataIndex > 0) {
                                        const prevValue = chartData[context.dataIndex - 1];
                                        const change = context.parsed.y - prevValue;
                                        if (change !== 0) {
                                            label += ` (${change > 0 ? '+' : ''}${formatNumber(change)} km)`;
                                        }
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Date',
                                font: {
                                    size: 14,
                                    weight: '600',
                                    family: 'system-ui'
                                },
                                color: '#374151'
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    family: 'system-ui'
                                },
                                color: '#6b7280'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Kilométrage (km)',
                                font: {
                                    size: 14,
                                    weight: '600',
                                    family: 'system-ui'
                                },
                                color: '#374151'
                            },
                            beginAtZero: false,
                            ticks: {
                                font: {
                                    size: 11,
                                    family: 'system-ui'
                                },
                                color: '#6b7280',
                                callback: function(value) {
                                    return formatNumber(value);
                                }
                            },
                            grid: {
                                color: 'rgba(229, 231, 235, 0.5)',
                                drawBorder: false
                            }
                        }
                    }
                }
            });
        }
        
        // Call the chart initialization function after a short delay to ensure DOM is ready
        setTimeout(function() {
            initializeChart();
        }, 100);

        // Format number with French locale
        function formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(num));
        }

        // Calculate linear trend
        function calculateLinearTrend(data) {
            if (data.length < 2) return data;
            
            const n = data.length;
            let sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0;
            
            for (let i = 0; i < n; i++) {
                sumX += i;
                sumY += data[i];
                sumXY += i * data[i];
                sumX2 += i * i;
            }
            
            const slope = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
            const intercept = (sumY - slope * sumX) / n;
            
            return data.map((_, i) => slope * i + intercept);
        }

        // Initialize DataTable with server-side processing
        if ($('#kilometrageTable').length) {
            var totalKmValue = 0; // Store total from server
            
            // Check if form has values and auto-load data
            var dateDebut = $('#date_debut').val();
            var dateFin = $('#date_fin').val();
            var bus = $('#bus').val();
            var autoLoad = dateDebut && dateFin && bus;
            
            const table = $('#kilometrageTable').DataTable({
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
                ],
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'ajax_kilometrage_data.php',
                    type: 'POST',
                    data: function(d) {
                        // Add search parameters from the form
                        d.date_debut = $('#date_debut').val();
                        d.date_fin = $('#date_fin').val();
                        d.bus = $('#bus').val();
                        return d;
                    },
                    dataSrc: function(json) {
                        // Store total km from server response
                        if (json.totalKm !== undefined) {
                            totalKmValue = json.totalKm;
                            $('#totalKmCell').text(formatNumber(json.totalKm) + ' km');
                        }
                        return json.data;
                    }
                },
                footerCallback: function(row, data, start, end, display) {
                    // Use the total from server (sum of all filtered records)
                    if (totalKmValue > 0) {
                        $('#totalKmCell').text(formatNumber(totalKmValue) + ' km');
                    } else {
                        // Fallback: calculate from all displayed data
                        var api = this.api();
                        var total = api
                            .column(3, { page: 'all' })
                            .data()
                            .reduce(function(a, b) {
                                return parseInt(a) + parseInt(b);
                            }, 0);
                        $('#totalKmCell').text(formatNumber(total) + ' km');
                    }
                },
                columns: [
                    { data: 'date_kilometrage', name: 'date_kilometrage' },
                    { data: 'station_lib', name: 'station_lib' },
                    { data: 'matricule_interne', name: 'matricule_interne' },
                    { 
                        data: 'kilometrage', 
                        name: 'kilometrage',
                        render: function(data, type, row) {
                            if (type === 'display' || type === 'export') {
                                return formatNumber(data);
                            }
                            return data;
                        }
                    }
                ]
            });
            
            // Handle search form submission - allow normal POST to show chart
            $('#search_form').on('submit', function(e) {
                // Allow form to submit normally so chart panel appears
                // The form will do a POST request and reload the page with chart data
                return true;
            });
            
            // Auto-reload when date or vehicle changes
            $('#date_debut, #date_fin, #bus').on('change', function() {
                // Validate that both dates are filled
                var dateDebut = $('#date_debut').val();
                var dateFin = $('#date_fin').val();
                var bus = $('#bus').val();
                
                if (dateDebut && dateFin && bus) {
                    table.ajax.reload(null, false); // false = don't reset paging
                }
            });
            
            // Auto-load data if form has values on page load
            if (autoLoad) {
                table.ajax.reload();
            }
            
            // Handle reset button
            $('#reset_search').on('click', function() {
                $('#search_form')[0].reset();
                totalKmValue = 0;
                $('#totalKmCell').text('0 km');
                // Clear table if it exists
                if (table) {
                    table.clear().draw();
                }
            });
        }
    });

    // Calculate trend line (simple linear regression)
    function calculateTrend(data) {
        if (data.length < 2) return data;
        
        const n = data.length;
        let sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0;
        
        for (let i = 0; i < n; i++) {
            sumX += i;
            sumY += data[i];
            sumXY += i * data[i];
            sumX2 += i * i;
        }
        
        const slope = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
        const intercept = (sumY - slope * sumX) / n;
        
        return data.map((_, i) => slope * i + intercept);
    }
</script>
</body>
</html>