<?php
// Suppress any warnings/notices that might break JavaScript
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

// Prevent any output before HTML
ob_start();
require 'header.php';
ob_end_flush();
?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Etat de consommation lubrifiant et filtres</h1>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="exportPDF()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                <path d="M14 3v6h6"/>
                            </svg>
                            Exporter PDF
                        </button>
                        <a href="/liste-fiche-entretien" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Retour
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
                <form id="filterForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
                            <input type="date" name="start_date" id="start_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
                            <input type="date" name="end_date" id="end_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Véhicule</label>
                            <select name="bus_id" id="bus_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Tous les véhicules</option>
                                <?php
                                try {
                                    $busStmt = $db->query('SELECT id_bus, matricule_interne FROM bus ORDER BY matricule_interne ASC');
                                    $buses = $busStmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($buses as $bus) {
                                        echo '<option value="' . (int) $bus['id_bus'] . '">' . htmlspecialchars($bus['matricule_interne'], ENT_QUOTES, 'UTF-8') . '</option>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<option value="">Erreur chargement véhicules</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="resetFilters()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Réinitialiser
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <svg class="h-4 w-4 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Rechercher
                        </button>
                    </div>
                </form>
            </div>

            <!-- Results Section -->
            <div id="resultsSection" class="hidden">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total kilométrage index</p>
                                <p class="text-2xl font-semibold text-gray-900" id="totalKmIndex">0 km</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total kilométrage exploitation</p>
                                <p class="text-2xl font-semibold text-gray-900" id="totalKmExploitation">0 km</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                                <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Moyenne consommation huile</p>
                                <p class="text-2xl font-semibold text-gray-900" id="avgOil">0 L/1000 km</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="space-y-6 mb-8">
                    <!-- Average Oil Consumption per 1000 km Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Moyenne de consommation d'huile</h3>
                                <p class="text-xs uppercase tracking-wide text-gray-400">L / 1000 Kilomètre (Moteur - Appoint uniquement)</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <label for="source_kilometrage_avg" class="text-sm font-medium text-gray-700">Source kilométrage:</label>
                                    <select id="source_kilometrage_avg" class="px-3 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                        <option value="index" selected>Kilométrage Index</option>
                                        <option value="exploitation">Kilométrage d'exploitation</option>
                                    </select>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-blue-50 via-indigo-50 to-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm">
                                    <svg class="h-5 w-5 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                        <path d="M2 17l10 5 10-5"/>
                                        <path d="M2 12l10 5 10-5"/>
                                    </svg>
                                    <span id="avgConsumptionDisplay">0 L/1000 km</span>
                                </div>
                            </div>
                        </div>
                        <div class="relative h-96" id="avgConsumptionChartContainer">
                            <canvas id="avgConsumptionChart"></canvas>
                            <div id="avgConsumptionEmptyState" class="hidden absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-gray-500">Aucune donnée disponible</p>
                                    <p class="text-sm text-gray-400 mt-1">Opérations Moteur - Appoint requises</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Oil Statistics by Type - Same as Dashboard -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Statistiques des Huiles par Type</h3>
                                <p class="text-xs uppercase tracking-wide text-gray-400">Quantités d'huile utilisées (Vidange vs Appoint)</p>
                            </div>
                            <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-amber-50 via-yellow-50 to-white px-4 py-2 text-sm font-semibold text-amber-700 shadow-sm">
                                <svg class="h-5 w-5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>
                                </svg>
                                <span id="oilStatsTotalDisplay">0 L</span>
                            </div>
                        </div>
                        <div class="relative h-96">
                            <canvas id="oilStatsChart"></canvas>
                        </div>
                    </div>


                    <!-- Liquide Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Consommation de liquide par type</h3>
                        <div class="relative h-96">
                            <canvas id="liquideByTypeChart"></canvas>
                        </div>
                    </div>

                </div>

                <!-- Filter Statistics Section -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistiques des Filtres</h3>
                    <div class="relative h-96">
                        <canvas id="filterStatsChart"></canvas>
                    </div>
                </div>

                <!-- Detailed Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden" style="min-height: 600px;">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Détail des opérations par véhicule</h3>
                    </div>
                    <div class="panel-body overflow-x-auto" style="min-height: 500px;">
                        <style>
                            .table td, .table th {
                                padding: 0.5rem 0.75rem !important;
                                vertical-align: middle;
                            }
                            .table {
                                width: 100% !important;
                                min-width: 1000px !important;
                            }
                            .table td:not(.text-right) {
                                white-space: nowrap;
                            }
                            .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                                background: #3b82f6 !important;
                                color: white !important;
                            }
                            .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                                background: #2563eb !important;
                                color: white !important;
                            }
                            .filter-row th {
                                padding: 8px !important;
                                background-color: #f9fafb;
                                border-bottom: 2px solid #e5e7eb;
                            }
                            .filter-row .filter-select {
                                width: 100%;
                            }
                        </style>
                        <table class="table compact-table" id="operationsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Véhicule</th>
                                    <th>Index km</th>
                                    <th>Type</th>
                                    <th>Opération</th>
                                    <th>Compartiment</th>
                                    <th>Détails</th>
                                    <th>Quantité</th>
                                    <th>Fiche</th>
                                </tr>
                                <tr class="filter-row">
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="resultsTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Results will be populated here -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="mb-16"></div>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="hidden text-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Chargement des données...</p>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-2 text-gray-500">Aucune donnée trouvée pour la période sélectionnée</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="js/jquery.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
let oilStatsChart = null;
let liquideByTypeChart = null;
let filterStatsChart = null;
let avgConsumptionChart = null;
let currentData = [];
let operationsDataTable = null;
let busSelectInstance = null;

// Initialize date inputs with default values
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.getElementById('start_date').value = firstDayOfMonth.toISOString().split('T')[0];
    document.getElementById('end_date').value = today.toISOString().split('T')[0];
    
    // Initialize TomSelect for vehicle selection (only if not already initialized)
    const busSelectElement = document.getElementById('bus_id');
    if (busSelectElement && !busSelectElement.tomselect) {
        busSelectInstance = new TomSelect('#bus_id', {
        create: false,
        maxOptions: 50,
        placeholder: 'Tous les véhicules',
        searchPlaceholder: 'Rechercher un véhicule...',
        allowEmptyOption: true,
        plugins: ['dropdown_input']
    });
    }
});

// Form submission
document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    loadData();
});

// Add event listener for source_kilometrage_avg change
if (document.getElementById('source_kilometrage_avg')) {
    document.getElementById('source_kilometrage_avg').addEventListener('change', function() {
        // Reload data when source changes
        if (currentData && currentData.length > 0) {
            loadData();
        }
    });
}

// Reset filters
function resetFilters() {
    document.getElementById('filterForm').reset();
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.getElementById('start_date').value = firstDayOfMonth.toISOString().split('T')[0];
    document.getElementById('end_date').value = today.toISOString().split('T')[0];
    
    document.getElementById('resultsSection').classList.add('hidden');
    document.getElementById('emptyState').classList.add('hidden');
    
    // Clear DataTable if exists
    if (operationsDataTable) {
        operationsDataTable.clear();
        operationsDataTable.draw();
    }
}

// Load data from API
function loadData() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const busId = document.getElementById('bus_id').value;
    const sourceKilometrage = document.getElementById('source_kilometrage_avg') ? document.getElementById('source_kilometrage_avg').value : 'index';
    
    // Show loading
    document.getElementById('loadingState').classList.remove('hidden');
    document.getElementById('resultsSection').classList.add('hidden');
    document.getElementById('emptyState').classList.add('hidden');
    
    fetch('/api/get_oil_consumption_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            start_date: startDate,
            end_date: endDate,
            bus_id: busId,
            source_kilometrage: sourceKilometrage
        })
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
            });
        }
        return response.json();
    })
    .then(data => {
        document.getElementById('loadingState').classList.add('hidden');
        
        if (data.success && data.data && data.data.length > 0) {
            currentData = data.data;
            displayResults(data.data, sourceKilometrage, data.kilometrage_data || {}, data.kilometrage_total_by_bus || {});
            updateCharts(data.data, sourceKilometrage, data.kilometrage_data || {}, data.kilometrage_total_by_bus || {});
            document.getElementById('resultsSection').classList.remove('hidden');
        } else {
            document.getElementById('emptyState').classList.remove('hidden');
        }
    })
    .catch(error => {
        document.getElementById('loadingState').classList.add('hidden');
        console.error('Error:', error);
        alert('Une erreur est survenue lors du chargement des données: ' + error.message);
    });
}

// Display results in table and summary cards
function displayResults(data, sourceKilometrage = 'index', kilometrageData = {}, kilometrageTotalByBus = {}) {
    // Update summary cards
    const totalVehicles = data.length;
    const totalOil = data.reduce((sum, item) => sum + parseFloat(item.total_oil || 0), 0);
    const totalLiquide = data.reduce((sum, item) => sum + parseFloat(item.total_liquide || 0), 0);
    const totalFilters = data.reduce((sum, item) => sum + parseInt(item.total_filters || 0), 0);
    
    // Calculate average consumption (L/1000 km) from Moteur + Apoint operations
    let totalOilConsumption = 0;
    let totalKmConsumption = 0;
    let avgConsumption = 0;
    
    if (sourceKilometrage === 'exploitation') {
        // DEBUG: Start calculation
        console.log('=== DEBUG: Moyenne Consommation avec Kilométrage d\'exploitation ===');
        console.log('Total vehicles in data:', data.length);
        console.log('kilometrageTotalByBus keys:', Object.keys(kilometrageTotalByBus || {}).length);
        console.log('kilometrageData keys:', Object.keys(kilometrageData || {}).length);
        
        // Use kilometrage from kilometrage table
        // Match rapport_conso_huile.php approach: 
        // 1. Get oil consumption per vehicle (only Moteur + Apoint)
        // 2. Get kilometrage for vehicles that have oil consumption
        // 3. Sum totals and calculate average
        
        // First, collect vehicles with Moteur + Apoint operations and their oil consumption
        const vehiclesWithOil = new Map(); // vehicleId -> totalOil
        
        data.forEach(vehicle => {
            const vehicleId = vehicle.id_bus;
            let vehicleOil = 0;
            
            // Sum oil consumption from Moteur + Apoint operations for this vehicle
            if (vehicle.operations && vehicle.operations.length > 0) {
                vehicle.operations.forEach(op => {
                    const compartiment = (op.compartiment_name || '').trim();
                    const operation = (op.oil_operation || '').trim();
                    
                    // Only count Huile operations with compartiment = "Moteur" and operation = "Apoint" (not Vidange)
                    if (op.type === 'Huile' && 
                        compartiment.toLowerCase() === 'moteur' && 
                        operation.toLowerCase() === 'apoint' && 
                        op.quantity && parseFloat(op.quantity) > 0) {
                        
                        vehicleOil += parseFloat(op.quantity) || 0;
                    }
                });
            }
            
            // Store vehicles that have oil consumption
            if (vehicleOil > 0) {
                vehiclesWithOil.set(vehicleId, vehicleOil);
            }
        });
        
        console.log('Vehicles with Moteur + Apoint operations:', vehiclesWithOil.size);
        console.log('Vehicles with oil:', Array.from(vehiclesWithOil.entries()).map(([id, oil]) => `Vehicle ${id}: ${oil.toFixed(2)}L`));
        
        // Now sum oil and kilometrage for vehicles that have oil consumption
        const vehicleDetails = [];
        vehiclesWithOil.forEach((vehicleOil, vehicleId) => {
            // Get kilometrage for this vehicle
            // Try kilometrageTotalByBus first, then fallback to kilometrageData
            let vehicleKm = 0;
            let kmSource = 'none';
            
            if (kilometrageTotalByBus && kilometrageTotalByBus[vehicleId]) {
                vehicleKm = parseFloat(kilometrageTotalByBus[vehicleId]) || 0;
                kmSource = 'kilometrageTotalByBus';
            } else if (kilometrageData && kilometrageData[vehicleId]) {
                // Fallback: sum from kilometrageData
                Object.keys(kilometrageData[vehicleId]).forEach(date => {
                    vehicleKm += parseFloat(kilometrageData[vehicleId][date]) || 0;
                });
                kmSource = 'kilometrageData';
            }
            
            vehicleDetails.push({
                vehicleId: vehicleId,
                oil: vehicleOil.toFixed(2),
                km: vehicleKm.toFixed(0),
                source: kmSource
            });
            
            if (vehicleKm > 0) {
                totalOilConsumption += vehicleOil;
                totalKmConsumption += vehicleKm;
            } else {
                console.warn(`Vehicle ${vehicleId} has oil (${vehicleOil.toFixed(2)}L) but no kilometrage data`);
            }
        });
        
        console.log('Vehicle details:', vehicleDetails);
        console.log('Total Oil Consumption:', totalOilConsumption.toFixed(2), 'L');
        console.log('Total Km Consumption:', totalKmConsumption.toFixed(0), 'km');
        
        // Calculate average: (total oil / total kilometrage) * 1000
        if (totalKmConsumption > 0) {
            avgConsumption = (totalOilConsumption / totalKmConsumption) * 1000;
            console.log('Calculated Average:', avgConsumption.toFixed(2), 'L/1000 km');
            console.log('Formula: (', totalOilConsumption.toFixed(2), '/', totalKmConsumption.toFixed(0), ') * 1000');
        } else {
            console.error('ERROR: totalKmConsumption is 0, cannot calculate average');
        }
        
        // Compare with total kilometrage exploitation metric
        let totalKmExploitationDebug = 0;
        if (kilometrageTotalByBus && Object.keys(kilometrageTotalByBus).length > 0) {
            data.forEach(vehicle => {
                const vehicleId = vehicle.id_bus;
                if (kilometrageTotalByBus[vehicleId]) {
                    totalKmExploitationDebug += parseFloat(kilometrageTotalByBus[vehicleId]) || 0;
                }
            });
        }
        console.log('Total Km Exploitation (all vehicles):', totalKmExploitationDebug.toFixed(0), 'km');
        console.log('Difference:', (totalKmExploitationDebug - totalKmConsumption).toFixed(0), 'km');
        console.log('=== END DEBUG ===');
    } else {
        // Use index_km from maintenance_records
        const allIndexKm = [];
        
        data.forEach(vehicle => {
            if (vehicle.operations && vehicle.operations.length > 0) {
                vehicle.operations.forEach(op => {
                    const compartiment = (op.compartiment_name || '').trim();
                    const operation = (op.oil_operation || '').trim();
                    
                    // Only count Huile operations with compartiment = "Moteur" and operation = "Apoint" (not Vidange)
                    if (op.type === 'Huile' && 
                        compartiment.toLowerCase() === 'moteur' && 
                        operation.toLowerCase() === 'apoint' && 
                        op.quantity && parseFloat(op.quantity) > 0 &&
                        op.index_km && parseFloat(op.index_km) > 0) {
                        
                        totalOilConsumption += parseFloat(op.quantity) || 0;
                        allIndexKm.push(parseFloat(op.index_km));
                    }
                });
            }
        });
        
        // Calculate total kilometrage from min to max
        if (allIndexKm.length > 0) {
            const minKm = Math.min(...allIndexKm);
            const maxKm = Math.max(...allIndexKm);
            totalKmConsumption = maxKm - minKm;
            
            if (totalKmConsumption > 0) {
                avgConsumption = (totalOilConsumption / totalKmConsumption) * 1000;
            }
        }
    }
    
    // Calculate total kilometrage index (from all operations with index_km)
    let totalKmIndex = 0;
    const allIndexKmForTotal = [];
    data.forEach(vehicle => {
        if (vehicle.operations && vehicle.operations.length > 0) {
            vehicle.operations.forEach(op => {
                if (op.index_km && parseFloat(op.index_km) > 0) {
                    allIndexKmForTotal.push(parseFloat(op.index_km));
                }
            });
        }
    });
    if (allIndexKmForTotal.length > 0) {
        const minKmIndex = Math.min(...allIndexKmForTotal);
        const maxKmIndex = Math.max(...allIndexKmForTotal);
        totalKmIndex = maxKmIndex - minKmIndex;
    }
    
    // Calculate total kilometrage exploitation (from kilometrage table)
    // Sum kilometrage for ALL vehicles in the data for the entire period
    let totalKmExploitation = 0;
    if (kilometrageTotalByBus && Object.keys(kilometrageTotalByBus).length > 0) {
        // Use pre-calculated totals by bus
        data.forEach(vehicle => {
            const vehicleId = vehicle.id_bus;
            if (kilometrageTotalByBus[vehicleId]) {
                totalKmExploitation += parseFloat(kilometrageTotalByBus[vehicleId]) || 0;
            }
        });
    } else {
        // Fallback: sum from kilometrageData
        data.forEach(vehicle => {
            const vehicleId = vehicle.id_bus;
            if (kilometrageData[vehicleId]) {
                Object.keys(kilometrageData[vehicleId]).forEach(date => {
                    totalKmExploitation += parseFloat(kilometrageData[vehicleId][date]) || 0;
                });
            }
        });
    }
    
    
    // Update display
    document.getElementById('totalKmIndex').textContent = totalKmIndex > 0 ? totalKmIndex.toLocaleString('fr-FR') + ' km' : '0 km';
    document.getElementById('totalKmExploitation').textContent = totalKmExploitation > 0 ? totalKmExploitation.toLocaleString('fr-FR') + ' km' : '0 km';
    document.getElementById('avgOil').textContent = avgConsumption > 0 ? avgConsumption.toFixed(2) + ' L/1000 km' : '0 L/1000 km';
    document.getElementById('avgConsumptionDisplay').textContent = avgConsumption > 0 ? avgConsumption.toFixed(2) + ' L/1000 km' : '0 L/1000 km';
    
    // Collect all operations from all vehicles
    const allOperations = [];
    data.forEach(vehicle => {
        if (vehicle.operations) {
            vehicle.operations.forEach(op => {
                allOperations.push({
                    ...op,
                    matricule_interne: vehicle.matricule_interne
                });
            });
        }
    });
    
    // Sort operations by date (ascending - oldest first)
    allOperations.sort((a, b) => {
        const dateA = new Date(a.date.split('/').reverse().join('-'));
        const dateB = new Date(b.date.split('/').reverse().join('-'));
        return dateA - dateB; // Oldest first
    });
    
    // Prepare data for DataTables
    const tableData = allOperations.map(op => {
        // Format operation type (Vidange / Apoint / Controle / Changement / Nettoyage)
        let operationType = '';
        let operationDetails = '';
        let quantity = '';
        
        if (op.type === 'Huile') {
            operationType = op.oil_operation || '-';
            operationDetails = op.oil_type_name || '-';
            quantity = `${parseFloat(op.quantity || 0).toFixed(2)} L`;
        } else if (op.type === 'Liquide') {
            operationType = op.oil_operation || '-';
            operationDetails = op.liquide_type_name || '-';
            quantity = `${parseFloat(op.quantity || 0).toFixed(2)} L`;
        } else if (op.type === 'Filter') {
            operationType = op.filter_operation || '-';
            operationDetails = op.filter_type_name || '-';
            quantity = '-';
        }
        
        // Format operation badge
        let operationBadge = '';
        if (operationType === 'Vidange') {
            operationBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">${operationType}</span>`;
        } else if (operationType === 'Apoint') {
            operationBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Appoint</span>`;
        } else if (operationType === 'Controle') {
            operationBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">${operationType}</span>`;
        } else if (operationType === 'Changement') {
            operationBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">${operationType}</span>`;
        } else if (operationType === 'Nettoyage') {
            operationBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">${operationType}</span>`;
        } else {
            operationBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">${operationType}</span>`;
        }
        
        // Format type badge
        let typeBadge = '';
        if (op.type === 'Huile') {
            typeBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${op.type}</span>`;
        } else if (op.type === 'Liquide') {
            typeBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">${op.type}</span>`;
        } else if (op.type === 'Filter') {
            typeBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">${op.type}</span>`;
        }
        
        // Format fiche link
        const ficheLink = op.fiche_id ? 
            `<a href="/details-fiche-entretien?id=${op.fiche_id}" target="_blank" class="text-blue-600 hover:text-blue-900" title="Voir la fiche d'entretien">
                ${op.fiche_num || op.fiche_id}
            </a>` :
            `<span class="text-gray-400">-</span>`;
        
        // Format index_km
        const indexKm = op.index_km ? parseInt(op.index_km).toLocaleString('fr-FR') : '-';
        
        return [
            op.date,
            op.matricule_interne,
            indexKm,
            typeBadge,
            operationBadge,
            op.compartiment_name,
            operationDetails,
            quantity,
            ficheLink
        ];
    });
    
    // Initialize or update DataTables
    const titreRapport = "Détail des opérations";
    
    if (operationsDataTable) {
        operationsDataTable.clear();
        operationsDataTable.rows.add(tableData);
        operationsDataTable.draw();
    } else {
        operationsDataTable = $('#operationsTable').DataTable({
            data: tableData,
            dom: '<"row"<"col-sm-12 col-md-6"f>>Brtip',
            buttons: [
                {
                    extend: 'print',
                    title: titreRapport,
                    text: 'Imprimer',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                },
                {
                    extend: 'excelHtml5',
                    title: titreRapport,
                    text: 'Exporter Excel',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                },
                {
                    extend: 'pdfHtml5',
                    title: titreRapport,
                    text: 'Exporter PDF',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
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
                },
                oAria: {
                    sSortAscending: ': activer pour trier la colonne par ordre croissant',
                    sSortDescending: ': activer pour trier la colonne par ordre décroissant'
                }
            },
            pageLength: 25,
            responsive: true,
            searching: true,
            ordering: false,
            initComplete: function() {
                var api = this.api();
                
                // Add select filters for Type (column 3), Opération (column 4), and Compartiment (column 5) in the header
                api.columns([3, 4, 5]).every(function() {
                    var column = this;
                    var header = $(column.header());
                    var columnIndex = column.index();
                    
                    // Find the filter row (second row in thead)
                    var filterRow = header.closest('thead').find('tr.filter-row');
                    var filterCell = filterRow.find('th').eq(columnIndex);
                    
                    // Create regular select element with non-selectable placeholder
                    var placeholderText = '';
                    if (columnIndex === 3) {
                        placeholderText = 'Type';
                    } else if (columnIndex === 4) {
                        placeholderText = 'Opération';
                    } else if (columnIndex === 5) {
                        placeholderText = 'Compartiment';
                    }
                    
                    var select = $('<select class="filter-select" data-column="' + columnIndex + '" style="width:100%; padding: 4px 8px; height: 32px; font-size: 13px; border-radius: 6px; border: 1px solid #d1d5db; background-color: white;"><option value="" disabled selected>' + placeholderText + '</option></select>')
                        .appendTo(filterCell.empty());
                    
                    // Get unique values from the column (extract text from HTML)
                    var uniqueValues = [];
                    column.data().unique().sort().each(function(d, j) {
                        // Extract text content from HTML badge
                        var text = $('<div>').html(d).text().trim();
                        if (text && text !== '-' && uniqueValues.indexOf(text) === -1) {
                            uniqueValues.push(text);
                            select.append('<option value="' + text + '">' + text + '</option>');
                        }
                    });
                    
                    // Regular select change handler
                    select.on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });
                });
            }
        });
    }
}

// Update charts
function updateCharts(data, sourceKilometrage = 'index', kilometrageData = {}, kilometrageTotalByBus = {}) {
    // Calculate oil consumption by type with Vidange/Apoint separation
    const oilStatsData = {};
    let totalOilStats = 0;
    
    data.forEach(vehicle => {
        if (vehicle.operations) {
            vehicle.operations.forEach(op => {
                if (op.type === 'Huile' && op.oil_type_name && op.quantity) {
                    const oilType = op.oil_type_name;
                    const operation = op.oil_operation || 'Autre';
                    const quantity = parseFloat(op.quantity) || 0;
                    
                    if (!oilStatsData[oilType]) {
                        oilStatsData[oilType] = { Vidange: 0, Apoint: 0 };
                    }
                    
                    if (operation === 'Vidange') {
                        oilStatsData[oilType].Vidange += quantity;
                    } else if (operation === 'Apoint') {
                        oilStatsData[oilType].Apoint += quantity;
                    }
                    
                    totalOilStats += quantity;
                }
            });
        }
    });
    
    // Update total display
    document.getElementById('oilStatsTotalDisplay').textContent = totalOilStats.toFixed(2) + ' L';
    
    const oilStatsLabels = Object.keys(oilStatsData);
    const vidangeData = oilStatsLabels.map(type => oilStatsData[type].Vidange);
    const apointData = oilStatsLabels.map(type => oilStatsData[type].Apoint);
    
    // Oil Stats Chart (Vertical Bars - Same as Dashboard)
    const oilStatsCtx = document.getElementById('oilStatsChart').getContext('2d');
    if (oilStatsChart) oilStatsChart.destroy();
    
    oilStatsChart = new Chart(oilStatsCtx, {
        type: 'bar',
        data: {
            labels: oilStatsLabels,
            datasets: [
                {
                    label: 'Vidange',
                    data: vidangeData,
                    backgroundColor: 'rgba(245, 158, 11, 0.8)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                },
                {
                    label: 'Appoint',
                    data: apointData,
                    backgroundColor: 'rgba(251, 191, 36, 0.8)',
                    borderColor: 'rgba(251, 191, 36, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }
            ]
        },
        options: {
            indexAxis: 'x', // Vertical bars
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
    

    // Calculate liquide consumption by type
    const liquideByTypeData = {};
    data.forEach(vehicle => {
        if (vehicle.operations) {
            vehicle.operations.forEach(op => {
                if (op.type === 'Liquide' && op.liquide_type_name && op.quantity) {
                    const liquideType = op.liquide_type_name;
                    const quantity = parseFloat(op.quantity) || 0;
                    liquideByTypeData[liquideType] = (liquideByTypeData[liquideType] || 0) + quantity;
                }
            });
        }
    });
    
    const liquideTypeLabels = Object.keys(liquideByTypeData);
    const liquideTypeQuantities = Object.values(liquideByTypeData);
    
    // Liquide by type chart
    const liquideByTypeCtx = document.getElementById('liquideByTypeChart').getContext('2d');
    if (liquideByTypeChart) liquideByTypeChart.destroy();
    
    liquideByTypeChart = new Chart(liquideByTypeCtx, {
        type: 'bar',
        data: {
            labels: liquideTypeLabels,
            datasets: [{
                label: 'Quantité (L)',
                data: liquideTypeQuantities,
                backgroundColor: [
                    'rgba(168, 85, 247, 0.5)',
                    'rgba(196, 181, 253, 0.5)',
                    'rgba(147, 51, 234, 0.5)',
                    'rgba(126, 34, 206, 0.5)',
                    'rgba(219, 39, 119, 0.5)',
                    'rgba(236, 72, 153, 0.5)'
                ],
                borderColor: [
                    'rgba(168, 85, 247, 1)',
                    'rgba(196, 181, 253, 1)',
                    'rgba(147, 51, 234, 1)',
                    'rgba(126, 34, 206, 1)',
                    'rgba(219, 39, 119, 1)',
                    'rgba(236, 72, 153, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y', // This makes it horizontal bars
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantité (L)'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Type de liquide'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Calculate filter statistics
    const filterStatsData = {};
    data.forEach(vehicle => {
        if (vehicle.operations) {
            vehicle.operations.forEach(op => {
                if (op.type === 'Filter' && op.filter_type_name && op.filter_operation) {
                    const filterKey = op.filter_type_name;
                    if (!filterStatsData[filterKey]) {
                        filterStatsData[filterKey] = {
                            Changement: 0,
                            Nettoyage: 0,
                            usageFilter: op.usage_filter || 'Autre'
                        };
                    }
                    filterStatsData[filterKey][op.filter_operation]++;
                }
            });
        }
    });
    
    const filterTypes = Object.keys(filterStatsData);
    const changementData = filterTypes.map(type => filterStatsData[type].Changement);
    const nettoyageData = filterTypes.map(type => filterStatsData[type].Nettoyage);
    const filterCategories = filterTypes.map(type => filterStatsData[type].usageFilter);

    // Filter statistics chart (vertical bars like dashboard)
    const filterCtx = document.getElementById('filterStatsChart').getContext('2d');
    if (filterStatsChart) filterStatsChart.destroy();
    
    filterStatsChart = new Chart(filterCtx, {
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

    // Calculate Average Oil Consumption per 1000 km by Date
    // Only count operations with compartiment = "Moteur" and operation = "Apoint" (exclude Vidange)
    const dateConsumptions = [];
    
    if (sourceKilometrage === 'exploitation') {
        // Use kilometrage from kilometrage table
        // Group oil operations by date and vehicle
        const oilByDateVehicle = new Map(); // "date|vehicleId" -> { totalOil, vehicleId, vehicleName }
        
        data.forEach(vehicle => {
            if (vehicle.operations && vehicle.operations.length > 0) {
                vehicle.operations.forEach(op => {
                    const compartiment = (op.compartiment_name || '').trim();
                    const operation = (op.oil_operation || '').trim();
                    
                    // Only count Huile operations with compartiment = "Moteur" and operation = "Apoint"
                    if (op.type === 'Huile' && 
                        compartiment.toLowerCase() === 'moteur' && 
                        operation.toLowerCase() === 'apoint' && 
                        op.quantity && parseFloat(op.quantity) > 0) {
                        
                        const quantity = parseFloat(op.quantity) || 0;
                        const date = op.date; // Format: dd/mm/yyyy
                        const key = `${date}|${vehicle.id_bus}`;
                        
                        if (!oilByDateVehicle.has(key)) {
                            oilByDateVehicle.set(key, {
                                totalOil: 0,
                                vehicleId: vehicle.id_bus,
                                vehicleName: vehicle.matricule_interne,
                                date: date
                            });
                        }
                        
                        oilByDateVehicle.get(key).totalOil += quantity;
                    }
                });
            }
        });
        
        // Group by date and sum kilometrage from kilometrage table
        // IMPORTANT: For cumulative calculation, we need to use total kilometrage per vehicle for the period,
        // not just kilometrage for dates with operations
        const dateOilMap = new Map(); // date -> { totalOil, totalKm }
        const vehiclesWithOilForChart = new Set(); // Track vehicles with operations
        
        oilByDateVehicle.forEach((value, key) => {
            const date = value.date;
            const vehicleId = value.vehicleId;
            const oilQty = value.totalOil;
            
            vehiclesWithOilForChart.add(vehicleId);
            
            // Get kilometrage for this vehicle and date (for chart display)
            const kmForDate = kilometrageData[vehicleId] && kilometrageData[vehicleId][date] ? kilometrageData[vehicleId][date] : 0;
            
            if (!dateOilMap.has(date)) {
                dateOilMap.set(date, {
                    totalOil: 0,
                    totalKm: 0
                });
            }
            
            const dateData = dateOilMap.get(date);
            dateData.totalOil += oilQty;
            dateData.totalKm += kmForDate;
        });
        
        // Calculate cumulative average consumption
        // For cumulative calculation, use total kilometrage per vehicle (not just dates with operations)
        let cumulativeOil = 0;
        let cumulativeKm = 0;
        const vehicleKmTotals = new Map(); // vehicleId -> totalKm for period
        
        // Get total kilometrage for each vehicle with operations
        vehiclesWithOilForChart.forEach(vehicleId => {
            let vehicleTotalKm = 0;
            if (kilometrageTotalByBus && kilometrageTotalByBus[vehicleId]) {
                vehicleTotalKm = parseFloat(kilometrageTotalByBus[vehicleId]) || 0;
            } else if (kilometrageData && kilometrageData[vehicleId]) {
                Object.keys(kilometrageData[vehicleId]).forEach(date => {
                    vehicleTotalKm += parseFloat(kilometrageData[vehicleId][date]) || 0;
                });
            }
            vehicleKmTotals.set(vehicleId, vehicleTotalKm);
        });
        
        const sortedDates = Array.from(dateOilMap.keys()).sort((a, b) => {
            const dateA = a.split('/').reverse().join('-');
            const dateB = b.split('/').reverse().join('-');
            return new Date(dateA) - new Date(dateB);
        });
        
        // Calculate cumulative values
        // For cumulative kilometrage, use total kilometrage per vehicle (not just dates with operations)
        // This matches the summary calculation approach
        const totalKmForVehicles = Array.from(vehicleKmTotals.values()).reduce((sum, km) => sum + km, 0);
        
        sortedDates.forEach((date) => {
            const dateData = dateOilMap.get(date);
            cumulativeOil += dateData.totalOil;
            
            // Use total kilometrage for all vehicles with operations (not cumulative by date)
            // This ensures the chart average matches the summary calculation
            cumulativeKm = totalKmForVehicles;
            
            // Calculate cumulative average consumption: (total oil / total km) * 1000
            let avgConsumption = 0;
            if (cumulativeKm > 0) {
                avgConsumption = (cumulativeOil / cumulativeKm) * 1000;
            }
            
            dateConsumptions.push({
                date: date,
                consumption: avgConsumption,
                totalOil: dateData.totalOil,
                cumulativeOil: cumulativeOil,
                cumulativeKm: cumulativeKm,
                kmTraveled: dateData.totalKm
            });
        });
    } else {
        // Use index_km from maintenance_records (original logic)
        const dateDataMap = new Map(); // date -> { totalOil, totalKm, operations }

        // Collect all operations with Moteur + Apoint
        data.forEach(vehicle => {
            if (vehicle.operations && vehicle.operations.length > 0) {
                vehicle.operations.forEach(op => {
                    const compartiment = (op.compartiment_name || '').trim();
                    const operation = (op.oil_operation || '').trim();
                    
                    // Only count Huile operations with compartiment = "Moteur" and operation = "Apoint" (not Vidange)
                    if (op.type === 'Huile' && 
                        compartiment.toLowerCase() === 'moteur' && 
                        operation.toLowerCase() === 'apoint' && 
                        op.quantity && parseFloat(op.quantity) > 0 &&
                        op.index_km && parseFloat(op.index_km) > 0) {
                        
                        const quantity = parseFloat(op.quantity) || 0;
                        const indexKm = parseFloat(op.index_km);
                        const date = op.date; // Format: dd/mm/yyyy
                        
                        if (!dateDataMap.has(date)) {
                            dateDataMap.set(date, {
                                totalOil: 0,
                                indexKmValues: [],
                                operations: []
                            });
                        }
                        
                        const dateData = dateDataMap.get(date);
                        dateData.totalOil += quantity;
                        dateData.indexKmValues.push(indexKm);
                        dateData.operations.push({
                            quantity: quantity,
                            indexKm: indexKm,
                            vehicle: vehicle.matricule_interne
                        });
                    }
                });
            }
        });

        // Calculate cumulative average consumption over time
        let cumulativeOil = 0;
        let cumulativeKm = 0;
        let initialIndexKm = null;
        let previousIndexKm = null;
        const sortedDates = Array.from(dateDataMap.keys()).sort((a, b) => {
            // Convert dd/mm/yyyy to Date for sorting
            const dateA = a.split('/').reverse().join('-');
            const dateB = b.split('/').reverse().join('-');
            return new Date(dateA) - new Date(dateB);
        });

        sortedDates.forEach((date, index) => {
            const dateData = dateDataMap.get(date);
            cumulativeOil += dateData.totalOil;
            
            // Get min and max index_km for this date
            const minKm = Math.min(...dateData.indexKmValues);
            const maxKm = Math.max(...dateData.indexKmValues);
            
            // Track initial kilometrage
            if (initialIndexKm === null) {
                initialIndexKm = minKm;
            }
            
            // Calculate kilometrage traveled from start to this date
            let kmTraveledThisPeriod = 0;
            if (previousIndexKm !== null && maxKm > previousIndexKm) {
                kmTraveledThisPeriod = maxKm - previousIndexKm;
            } else if (index === 0 && maxKm > minKm) {
                kmTraveledThisPeriod = maxKm - minKm;
            } else if (previousIndexKm === null && maxKm > initialIndexKm) {
                kmTraveledThisPeriod = maxKm - initialIndexKm;
            }
            
            cumulativeKm += kmTraveledThisPeriod;
            
            // Calculate cumulative average consumption: (total oil / total km) * 1000
            let avgConsumption = 0;
            if (cumulativeKm > 0) {
                avgConsumption = (cumulativeOil / cumulativeKm) * 1000;
            }
            
            dateConsumptions.push({
                date: date,
                consumption: avgConsumption, // This is now the cumulative average
                totalOil: dateData.totalOil,
                cumulativeOil: cumulativeOil,
                cumulativeKm: cumulativeKm,
                kmTraveled: kmTraveledThisPeriod
            });
            
            previousIndexKm = maxKm;
        });
    }

    // Get standard consumption (conso_huile) from bus data
    // If multiple vehicles, use average; if single vehicle, use its value
    let standardConsumption = 0;
    const consoHuileValues = [];
    data.forEach(vehicle => {
        if (vehicle.conso_huile && parseFloat(vehicle.conso_huile) > 0) {
            consoHuileValues.push(parseFloat(vehicle.conso_huile));
        }
    });
    
    if (consoHuileValues.length > 0) {
        // Use average if multiple vehicles, or single value if one vehicle
        standardConsumption = consoHuileValues.reduce((sum, val) => sum + val, 0) / consoHuileValues.length;
    }
    
    // Calculate overall average for display based on source
    // Use the same calculation as displayResults() based on sourceKilometrage
    let totalOilForDisplay = 0;
    let totalKmForDisplay = 0;
    let overallAvgConsumption = 0;
    
    if (sourceKilometrage === 'exploitation') {
        // Use kilometrage from kilometrage table (same as displayResults)
        const vehiclesWithOilForDisplay = new Map();
        
        // Collect vehicles with Moteur + Apoint operations
        data.forEach(vehicle => {
            const vehicleId = vehicle.id_bus;
            let vehicleOil = 0;
            
            if (vehicle.operations && vehicle.operations.length > 0) {
                vehicle.operations.forEach(op => {
                    const compartiment = (op.compartiment_name || '').trim();
                    const operation = (op.oil_operation || '').trim();
                    
                    if (op.type === 'Huile' && 
                        compartiment.toLowerCase() === 'moteur' && 
                        operation.toLowerCase() === 'apoint' && 
                        op.quantity && parseFloat(op.quantity) > 0) {
                        
                        vehicleOil += parseFloat(op.quantity) || 0;
                    }
                });
            }
            
            if (vehicleOil > 0) {
                vehiclesWithOilForDisplay.set(vehicleId, vehicleOil);
                totalOilForDisplay += vehicleOil;
            }
        });
        
        // Get total kilometrage for vehicles with operations
        vehiclesWithOilForDisplay.forEach((vehicleOil, vehicleId) => {
            let vehicleKm = 0;
            if (kilometrageTotalByBus && kilometrageTotalByBus[vehicleId]) {
                vehicleKm = parseFloat(kilometrageTotalByBus[vehicleId]) || 0;
            } else if (kilometrageData && kilometrageData[vehicleId]) {
                Object.keys(kilometrageData[vehicleId]).forEach(date => {
                    vehicleKm += parseFloat(kilometrageData[vehicleId][date]) || 0;
                });
            }
            if (vehicleKm > 0) {
                totalKmForDisplay += vehicleKm;
            }
        });
        
        overallAvgConsumption = totalKmForDisplay > 0 ? (totalOilForDisplay / totalKmForDisplay) * 1000 : 0;
    } else {
        // Use index_km from maintenance_records (same as displayResults)
        const allIndexKm = [];
        
        data.forEach(vehicle => {
            if (vehicle.operations && vehicle.operations.length > 0) {
                vehicle.operations.forEach(op => {
                    const compartiment = (op.compartiment_name || '').trim();
                    const operation = (op.oil_operation || '').trim();
                    
                    if (op.type === 'Huile' && 
                        compartiment.toLowerCase() === 'moteur' && 
                        operation.toLowerCase() === 'apoint' && 
                        op.quantity && parseFloat(op.quantity) > 0 &&
                        op.index_km && parseFloat(op.index_km) > 0) {
                        
                        totalOilForDisplay += parseFloat(op.quantity) || 0;
                        allIndexKm.push(parseFloat(op.index_km));
                    }
                });
            }
        });
        
        if (allIndexKm.length > 0) {
            const minKm = Math.min(...allIndexKm);
            const maxKm = Math.max(...allIndexKm);
            totalKmForDisplay = maxKm - minKm;
            
            if (totalKmForDisplay > 0) {
                overallAvgConsumption = (totalOilForDisplay / totalKmForDisplay) * 1000;
            }
        }
    }
    
    console.log('Chart Display Average - Source:', sourceKilometrage, 'Oil:', totalOilForDisplay.toFixed(2), 'L, Km:', totalKmForDisplay.toFixed(0), 'km, Avg:', overallAvgConsumption.toFixed(2), 'L/1000 km');
    document.getElementById('avgConsumptionDisplay').textContent = overallAvgConsumption > 0 ? overallAvgConsumption.toFixed(2) + ' L/1000 km' : '0 L/1000 km';

    // Average Consumption Chart
    const avgConsumptionCtx = document.getElementById('avgConsumptionChart');
    const avgConsumptionEmptyState = document.getElementById('avgConsumptionEmptyState');
    
    if (avgConsumptionChart) avgConsumptionChart.destroy();

    if (avgConsumptionCtx) {
        if (dateConsumptions.length > 0) {
            // Hide empty state
            if (avgConsumptionEmptyState) avgConsumptionEmptyState.classList.add('hidden');
            
            // Create chart data - dates on X-axis, consumption on Y-axis
            const dateLabels = dateConsumptions.map(d => d.date);
            const consumptionValues = dateConsumptions.map(d => d.consumption);
        avgConsumptionChart = new Chart(avgConsumptionCtx, {
            type: 'line',
            data: {
                labels: dateLabels,
                datasets: [{
                    label: 'Moyenne consommation (L/1000 km)',
                    data: consumptionValues,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }, {
                    label: 'Standard consommation',
                    data: Array(dateLabels.length).fill(standardConsumption),
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [8, 4],
                    pointRadius: 0,
                    pointHoverRadius: 0
                }]
            },
            options: {
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
                            title: function(context) {
                                return 'Date: ' + context[0].label;
                            },
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    const dateData = dateConsumptions[context.dataIndex];
                                    return [
                                        'Consommation: ' + context.parsed.y.toFixed(2) + ' L/1000 km',
                                        'Huile: ' + dateData.totalOil.toFixed(2) + ' L',
                                        'Kilométrage: ' + dateData.kmTraveled.toLocaleString('fr-FR') + ' km'
                                    ];
                                } else {
                                    return 'Standard consommation: ' + context.parsed.y.toFixed(2) + ' L/1000 km';
                                }
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Moyenne consommation (L/1000 km)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(2) + ' L';
                            }
                        },
                        grid: {
                            color: 'rgba(229, 231, 235, 0.5)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        } else {
            // Show empty state message
            if (avgConsumptionEmptyState) avgConsumptionEmptyState.classList.remove('hidden');
        }
    }
}

// Export data to PDF
function exportPDF() {
    if (!currentData.length) {
        alert('Aucune donnée à exporter');
        return;
    }
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Get filter values
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const busId = document.getElementById('bus_id').value;
    const selectedVehicle = busId ? currentData.find(item => item.id_bus === busId)?.matricule_interne : 'Tous les véhicules';
    
    // Add title page
    doc.setFontSize(20);
    doc.text('Etat de consommation lubrifiant et liquides', 105, 20, { align: 'center' });
    
    doc.setFontSize(12);
    doc.text('Période du ' + startDate + ' au ' + endDate, 105, 30, { align: 'center' });
    doc.text('Véhicule: ' + selectedVehicle, 105, 40, { align: 'center' });
    
    // Add summary
    const totalVehicles = currentData.length;
    const totalOil = currentData.reduce((sum, item) => sum + parseFloat(item.total_oil || 0), 0);
    const totalLiquide = currentData.reduce((sum, item) => sum + parseFloat(item.total_liquide || 0), 0);
    const totalFilters = currentData.reduce((sum, item) => sum + parseInt(item.total_filters || 0), 0);
    
    // Calculate average consumption (L/1000 km) from Moteur + Apoint operations
    let totalOilConsumption = 0;
    let totalKmConsumption = 0;
    const allIndexKm = [];
    
    currentData.forEach(vehicle => {
        if (vehicle.operations && vehicle.operations.length > 0) {
            vehicle.operations.forEach(op => {
                const compartiment = (op.compartiment_name || '').trim();
                const operation = (op.oil_operation || '').trim();
                
                if (op.type === 'Huile' && 
                    compartiment.toLowerCase() === 'moteur' && 
                    operation.toLowerCase() === 'apoint' && 
                    op.quantity && parseFloat(op.quantity) > 0 &&
                    op.index_km && parseFloat(op.index_km) > 0) {
                    
                    totalOilConsumption += parseFloat(op.quantity) || 0;
                    allIndexKm.push(parseFloat(op.index_km));
                }
            });
        }
    });
    
    let avgConsumption = 0;
    if (allIndexKm.length > 0) {
        const minKm = Math.min(...allIndexKm);
        const maxKm = Math.max(...allIndexKm);
        totalKmConsumption = maxKm - minKm;
        
        if (totalKmConsumption > 0) {
            avgConsumption = (totalOilConsumption / totalKmConsumption) * 1000;
        }
    }
    
    const avgOil = avgConsumption > 0 ? avgConsumption.toFixed(2) + ' L/1000 km' : '0 L/1000 km';
    
    doc.setFontSize(14);
    doc.text('Résumé', 20, 60);
    
    doc.setFontSize(11);
    doc.text('Véhicules analysés: ' + totalVehicles, 20, 70);
    doc.text('Huile totale consommée: ' + totalOil.toFixed(2) + ' L', 20, 80);
    doc.text('Liquide totale consommée: ' + totalLiquide.toFixed(2) + ' L', 20, 90);
    doc.text('Filtres changés: ' + totalFilters, 20, 100);
    doc.text('Moyenne huile/veh: ' + avgOil + ' L', 20, 110);
    
    // Add oil chart
    doc.text('Graphique de consommation par type d\'huile', 20, 130);
    
    // Capture oil chart as image
    const oilChartElement = document.getElementById('oilStatsChart');
    if (oilChartElement) {
        html2canvas(oilChartElement).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            doc.addImage(imgData, 'PNG', 20, 140, 170, 100);
            
            // Add liquide chart
            doc.text('Graphique de consommation par type de liquide', 20, 250);
            
            // Capture liquide chart as image
            const liquideChartElement = document.getElementById('liquideByTypeChart');
            if (liquideChartElement) {
                html2canvas(liquideChartElement).then(liquideCanvas => {
                    const liquideImgData = liquideCanvas.toDataURL('image/png');
                    doc.addImage(liquideImgData, 'PNG', 20, 260, 170, 100);
                    
                    // Add filter chart
                    doc.text('Statistiques des filtres', 20, 370);
                
                    // Capture filter chart as image
                    const filterChartElement = document.getElementById('filterStatsChart');
                    if (filterChartElement) {
                        html2canvas(filterChartElement).then(filterCanvas => {
                            const filterImgData = filterCanvas.toDataURL('image/png');
                            doc.addImage(filterImgData, 'PNG', 20, 380, 170, 100);
                            
                            // Continue with table export
                            exportTableToPDF(doc, totalOil, startDate, endDate);
                        }).catch(error => {
                            console.error('Error capturing filter chart:', error);
                            // Continue even if chart capture fails
                            exportTableToPDF(doc, totalOil, startDate, endDate);
                        });
                    } else {
                        // Continue even if chart doesn't exist
                        exportTableToPDF(doc, totalOil, startDate, endDate);
                    }
                }).catch(error => {
                    console.error('Error capturing liquide chart:', error);
                    // Continue even if chart capture fails
                    exportTableToPDF(doc, totalOil, startDate, endDate);
                });
            } else {
                // Continue even if chart doesn't exist
                exportTableToPDF(doc, totalOil, startDate, endDate);
            }
        }).catch(error => {
            console.error('Error capturing oil chart:', error);
            // Continue even if chart capture fails
            exportTableToPDF(doc, totalOil, startDate, endDate);
        });
    } else {
        // Continue even if chart doesn't exist
        exportTableToPDF(doc, totalOil, startDate, endDate);
    }
}

// Helper function to export table to PDF
function exportTableToPDF(doc, totalOil, startDate, endDate) {
    // Add detailed table
    doc.addPage();
    doc.setFontSize(14);
    doc.text('Détail des opérations', 20, 20);
    
    // Collect all operations from all vehicles
    const allOperations = [];
    currentData.forEach(vehicle => {
        if (vehicle.operations) {
            vehicle.operations.forEach(op => {
                allOperations.push({
                    ...op,
                    matricule_interne: vehicle.matricule_interne
                });
            });
        }
    });
    
    // Sort operations by date
    allOperations.sort((a, b) => {
        const dateA = new Date(a.date.split('/').reverse().join('-'));
        const dateB = new Date(b.date.split('/').reverse().join('-'));
        return dateB - dateA;
    });
    
    // Table headers
    doc.setFontSize(10);
    let yPosition = 35;
    doc.text('Date', 20, yPosition);
    doc.text('Véhicule', 40, yPosition);
    doc.text('Type', 70, yPosition);
    doc.text('Compartiment', 90, yPosition);
    doc.text('Détails', 130, yPosition);
    doc.text('Quantité', 170, yPosition);
    
    yPosition += 10;
    
    // Table data
    allOperations.forEach(op => {
        if (yPosition > 270) {
            doc.addPage();
            yPosition = 20;
            doc.setFontSize(10);
            doc.text('Date', 20, yPosition);
            doc.text('Véhicule', 40, yPosition);
            doc.text('Type', 70, yPosition);
            doc.text('Compartiment', 90, yPosition);
            doc.text('Détails', 130, yPosition);
            doc.text('Quantité', 170, yPosition);
            yPosition += 10;
        }
        
        // Format operation details
        let operationDetails = '';
        let quantity = '';
        
        if (op.type === 'Huile') {
            operationDetails = `${op.oil_operation} - ${op.oil_type_name}`;
            quantity = `${parseFloat(op.quantity || 0).toFixed(2)} L`;
        } else if (op.type === 'Filter') {
            operationDetails = `${op.filter_operation} - ${op.filter_type_name}`;
            quantity = '-';
        }
        
        doc.text(op.date, 20, yPosition);
        doc.text(op.matricule_interne, 40, yPosition);
        doc.text(op.type, 70, yPosition);
        doc.text(op.compartiment_name, 90, yPosition);
        doc.text(operationDetails.substring(0, 35), 130, yPosition); // Truncate long text
        doc.text(quantity, 170, yPosition);
        
        yPosition += 8;
    });
    
    // Add oil type breakdown
    doc.addPage();
    doc.setFontSize(14);
    doc.text('Consommation d\'huile par type', 20, 20);
    
    // Calculate oil by type
    const oilByTypeData = {};
    currentData.forEach(vehicle => {
        if (vehicle.operations) {
            vehicle.operations.forEach(op => {
                if (op.type === 'Huile' && op.oil_type_name && op.quantity) {
                    const oilType = op.oil_type_name;
                    const quantity = parseFloat(op.quantity) || 0;
                    oilByTypeData[oilType] = (oilByTypeData[oilType] || 0) + quantity;
                }
            });
        }
    });
    
    doc.setFontSize(10);
    yPosition = 35;
    doc.text('Type d\'huile', 20, yPosition);
    doc.text('Quantité (L)', 100, yPosition);
    doc.text('% du total', 150, yPosition);
    
    yPosition += 10;
    
    Object.entries(oilByTypeData).forEach(([oilType, quantity]) => {
        if (yPosition > 270) {
            doc.addPage();
            yPosition = 20;
            doc.setFontSize(10);
            doc.text('Type d\'huile', 20, yPosition);
            doc.text('Quantité (L)', 100, yPosition);
            doc.text('% du total', 150, yPosition);
            yPosition += 10;
        }
        
        const percentage = totalOil > 0 ? ((quantity / totalOil) * 100).toFixed(1) : 0;
        doc.text(oilType, 20, yPosition);
        doc.text(quantity.toFixed(2), 100, yPosition);
        doc.text(percentage + '%', 150, yPosition);
        
        yPosition += 8;
    });
    
    // Save the PDF
    doc.save(`consommation_huile_${startDate}_${endDate}.pdf`);
}
</script>
</body>
</html>
