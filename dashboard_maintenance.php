<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and helpers BEFORE header.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/helpers.php';

// Security check
require_once __DIR__ . '/helpers/security.php';

// Year handling
$currentYear = date('Y');
$selectedYear = $_GET['year'] ?? $currentYear;
$startDate = $selectedYear . '-01-01';
$endDate = $selectedYear . '-12-31';

// Fetch available years for the dropdown
try {
    $stmtYears = $db->query("SELECT DISTINCT YEAR(date) as year FROM ordre ORDER BY year DESC");
    $availableYears = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
    if (empty($availableYears)) {
        $availableYears = [$currentYear];
    }
} catch (PDOException $e) {
    $availableYears = [$currentYear];
}

// Fetch statistics data
$ateliers = [];
$statsOverall = [
    'total_ordres' => 0,
    'ordres_ouverts' => 0,
    'ordres_valides' => 0,
    'ordres_clotures' => 0,
    'total_ateliers' => 0,
    'total_systemes' => 0,
    'total_anomalies' => 0,
    'total_interventions' => 0,
    'interventions_realisees' => 0
];
$monthlyData = [];

try {
    $params = [':start' => $startDate, ':end' => $endDate];

    // Atelier Statistics
    $stmtAteliers = $db->prepare("
        SELECT 
            a.id,
            a.nom,
            COUNT(DISTINCT o.id) as total_ordres,
            COUNT(DISTINCT CASE WHEN o.etat = 'Ouvert' THEN o.id END) as ordres_ouverts,
            COUNT(DISTINCT CASE WHEN o.etat = 'Valider' THEN o.id END) as ordres_valides,
            COUNT(DISTINCT CASE WHEN o.etat = 'Cloturer' THEN o.id END) as ordres_clotures
        FROM atelier a
        LEFT JOIN ordre o ON a.id = o.id_atelier AND o.date BETWEEN :start AND :end
        GROUP BY a.id, a.nom
        ORDER BY total_ordres DESC
    ");
    $stmtAteliers->execute($params);
    $ateliers = $stmtAteliers->fetchAll(PDO::FETCH_ASSOC);

    // Overall Statistics - simplified query with date filtering
    // Using positional placeholders (?) because named parameters cannot be reused when ATTR_EMULATE_PREPARES is false
    $stmtOverall = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM ordre WHERE date BETWEEN ? AND ?) as total_ordres,
            (SELECT COUNT(*) FROM ordre WHERE etat = 'Ouvert' AND date BETWEEN ? AND ?) as ordres_ouverts,
            (SELECT COUNT(*) FROM ordre WHERE etat = 'Valider' AND date BETWEEN ? AND ?) as ordres_valides,
            (SELECT COUNT(*) FROM ordre WHERE etat = 'Cloturer' AND date BETWEEN ? AND ?) as ordres_clotures,
            (SELECT COUNT(*) FROM atelier) as total_ateliers,
            (SELECT COUNT(*) FROM systeme) as total_systemes,
            (SELECT COUNT(*) FROM anomalie) as total_anomalies,
            (SELECT COUNT(*) FROM ordre_intervention oi JOIN ordre o ON oi.id_ordre = o.id WHERE o.date BETWEEN ? AND ?) as total_interventions,
            (SELECT COUNT(*) FROM ordre_intervention oi JOIN ordre o ON oi.id_ordre = o.id WHERE oi.status = 'réaliser' AND o.date BETWEEN ? AND ?) as interventions_realisees
    ");
    $stmtOverall->execute([
        $startDate, $endDate,
        $startDate, $endDate,
        $startDate, $endDate,
        $startDate, $endDate,
        $startDate, $endDate,
        $startDate, $endDate
    ]);
    $statsOverall = $stmtOverall->fetch(PDO::FETCH_ASSOC);

    // Monthly trend data - filtered by selected period
    $stmtMonthly = $db->prepare("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as mois,
            COUNT(*) as total,
            COUNT(CASE WHEN etat = 'Cloturer' THEN 1 END) as clotures
        FROM ordre
        WHERE date BETWEEN :start AND :end
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY mois ASC
    ");
    $stmtMonthly->execute($params);
    $monthlyData = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

    // Demand Statistics
    $stmtDemande = $db->prepare("
        SELECT 
            COUNT(*) as total_demandes,
            COUNT(CASE WHEN etat = 'En cours' THEN 1 END) as demandes_en_cours,
            COUNT(CASE WHEN etat = 'Valider' THEN 1 END) as demandes_valider,
            COUNT(CASE WHEN etat = 'Cloturer' THEN 1 END) as demandes_cloturer
        FROM demande
        WHERE date BETWEEN :start AND :end
    ");
    $stmtDemande->execute($params);
    $statsDemande = $stmtDemande->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Erreur de chargement des statistiques : " . $e->getMessage();
}

// Only include header.php if not already routed
if (!defined('ROUTED')) {
    require 'header.php';
}

// Enforce route access
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <!-- Page Header & Filter -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 no-print">
            <div class="flex items-center justify-between w-full lg:w-auto">
                <div>
                    <h1 class="page-title">Tableau de Bord gestion de flotte</h1>
                    <p class="text-sm text-gray-600 mt-1">Vue d'ensemble des activités de maintenance</p>
                </div>
                <div class="flex lg:hidden gap-2">
                    <button onclick="window.print()" class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors shadow-sm" title="Imprimer">
                        <span class="iconify h-5 w-5" data-icon="mdi:printer"></span>
                    </button>
                    <button onclick="downloadPDF()" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors shadow-sm" title="Télécharger PDF">
                        <span class="iconify h-5 w-5" data-icon="mdi:file-pdf-box"></span>
                    </button>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-4 flex-1 lg:max-w-3xl justify-end">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 w-full sm:w-auto">
                    <form method="GET" class="flex flex-col sm:flex-row items-end gap-3">
                        <div class="w-full sm:w-48">
                            <label for="year" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Année</label>
                            <select name="year" id="year" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" onchange="this.form.submit()">
                                <?php foreach ($availableYears as $year): ?>
                                    <option value="<?= $year ?>" <?= $year == $selectedYear ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <div class="hidden lg:flex gap-2">
                    <button onclick="window.print()" class="p-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors shadow-sm" title="Imprimer">
                        <span class="iconify h-6 w-6" data-icon="mdi:printer"></span>
                    </button>
                    <button onclick="downloadPDF()" class="p-2 bg-white border border-red-100 text-red-600 rounded-lg hover:bg-red-50 transition-colors shadow-sm" title="Télécharger PDF">
                        <span class="iconify h-6 w-6" data-icon="mdi:file-pdf-box"></span>
                    </button>
                </div>
            </div>
        </div>

        <style>
        @media print {
            .no-print, #sidebar, header, .sidebar-overlay { display: none !important; }
            #page-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .mx-auto { max-width: none !important; width: 100% !important; }
            .panel { border: 1px solid #e5e7eb !important; break-inside: avoid; margin-bottom: 1rem !important; }
            .grid { display: block !important; }
            .grid > div { margin-bottom: 1rem !important; width: 100% !important; }
            canvas { max-width: 100% !important; height: auto !important; }
        }
        </style>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Overall Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Ordres</p>
                        <p class="text-3xl font-bold mt-2"><?= number_format($statsOverall['total_ordres']) ?></p>
                        <p class="text-blue-100 text-xs mt-1">Année <?= $selectedYear ?></p>
                    </div>
                    <span class="iconify h-12 w-12 text-blue-200" data-icon="mdi:clipboard-list"></span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Ordres Clôturés</p>
                        <p class="text-3xl font-bold mt-2"><?= number_format($statsOverall['ordres_clotures']) ?></p>
                        <p class="text-green-100 text-xs mt-1">Année <?= $selectedYear ?></p>
                    </div>
                    <span class="iconify h-12 w-12 text-green-200" data-icon="mdi:check-circle"></span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-amber-100 text-sm font-medium">Taux Clôture Ordres</p>
                        <?php 
                        $tauxClotureOrdres = $statsOverall['total_ordres'] > 0 
                            ? round(($statsOverall['ordres_clotures'] / $statsOverall['total_ordres']) * 100, 1) 
                            : 0;
                        ?>
                        <p class="text-3xl font-bold mt-2"><?= $tauxClotureOrdres ?>%</p>
                        <p class="text-amber-100 text-xs mt-1">Global ateliers</p>
                    </div>
                    <span class="iconify h-12 w-12 text-amber-200" data-icon="mdi:percent"></span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-sm font-medium">Taux Clôture Demandes</p>
                        <?php 
                        $tauxClotureDemandes = $statsDemande['total_demandes'] > 0 
                            ? round(($statsDemande['demandes_cloturer'] / $statsDemande['total_demandes']) * 100, 1) 
                            : 0;
                        ?>
                        <p class="text-3xl font-bold mt-2"><?= $tauxClotureDemandes ?>%</p>
                        <p class="text-red-100 text-xs mt-1">Global ateliers</p>
                    </div>
                    <span class="iconify h-12 w-12 text-red-200" data-icon="mdi:progress-check"></span>
                </div>
            </div>
        </div>

        <!-- Demand Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                    <span class="iconify h-6 w-6" data-icon="mdi:file-document-outline"></span>
                </div>
                <div>
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Demandes</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($statsDemande['total_demandes']) ?></p>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500">
                    <span class="iconify h-6 w-6" data-icon="mdi:clock-outline"></span>
                </div>
                <div>
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Demandes En cours</p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($statsDemande['demandes_en_cours']) ?></p>
                        <span class="text-xs font-medium text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">
                            <?= $statsDemande['total_demandes'] > 0 ? round(($statsDemande['demandes_en_cours'] / $statsDemande['total_demandes']) * 100, 1) : 0 ?>%
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-green-50 flex items-center justify-center text-green-500">
                    <span class="iconify h-6 w-6" data-icon="mdi:check-all"></span>
                </div>
                <div>
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Demandes Validées</p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($statsDemande['demandes_valider']) ?></p>
                        <span class="text-xs font-medium text-green-600 bg-green-50 px-1.5 py-0.5 rounded">
                            <?= $statsDemande['total_demandes'] > 0 ? round(($statsDemande['demandes_valider'] / $statsDemande['total_demandes']) * 100, 1) : 0 ?>%
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500">
                    <span class="iconify h-6 w-6" data-icon="mdi:archive-outline"></span>
                </div>
                <div>
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Demandes Clôturées</p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($statsDemande['demandes_cloturer']) ?></p>
                        <span class="text-xs font-medium text-gray-600 bg-gray-100 px-1.5 py-0.5 rounded">
                            <?= $statsDemande['total_demandes'] > 0 ? round(($statsDemande['demandes_cloturer'] / $statsDemande['total_demandes']) * 100, 1) : 0 ?>%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Monthly Trend Chart -->
            <div class="panel">
                <div class="panel-heading">Évolution Mensuelle des Ordres</div>
                <div class="panel-body">
                    <canvas id="monthlyTrendChart" height="300"></canvas>
                </div>
            </div>

            <!-- Order Status Distribution -->
            <div class="panel">
                <div class="panel-heading">Répartition par État</div>
                <div class="panel-body">
                    <canvas id="statusPieChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Atelier Statistics -->
        <div class="panel">
            <div class="panel-heading flex items-center justify-between">
                <span>Performance par Atelier</span>
                <span class="text-xs text-gray-500 font-normal"><?= count($ateliers) ?> ateliers</span>
            </div>
            <div class="panel-body">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Atelier</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ouverts</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Validés</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Clôturés</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Taux Clôture</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($ateliers as $atelier): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($atelier['nom']) ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-900 font-semibold">
                                        <?= number_format($atelier['total_ordres']) ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= number_format($atelier['ordres_ouverts']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <?= number_format($atelier['ordres_valides']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?= number_format($atelier['ordres_clotures']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        <?php 
                                        $tauxCloture = $atelier['total_ordres'] > 0 
                                            ? round(($atelier['ordres_clotures'] / $atelier['total_ordres']) * 100, 1) 
                                            : 0;
                                        $colorClass = $tauxCloture >= 75 ? 'text-green-600' : ($tauxCloture >= 50 ? 'text-amber-600' : 'text-red-600');
                                        ?>
                                        <span class="font-semibold <?= $colorClass ?>"><?= $tauxCloture ?>%</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
// Chart.js configuration
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#64748b';

// Monthly Trend Chart
const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthlyData, 'mois')) ?>,
        datasets: [
            {
                label: 'Total Ordres',
                data: <?= json_encode(array_column($monthlyData, 'total')) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Ordres Clôturés',
                data: <?= json_encode(array_column($monthlyData, 'clotures')) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});

// Status Pie Chart
const statusCtx = document.getElementById('statusPieChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Ouverts', 'Validés', 'Clôturés'],
        datasets: [{
            data: [
                <?= $statsOverall['ordres_ouverts'] ?>,
                <?= $statsOverall['ordres_valides'] ?>,
                <?= $statsOverall['ordres_clotures'] ?>
            ],
            backgroundColor: ['#3b82f6', '#10b981', '#6b7280'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// PDF Download Functionality
function downloadPDF() {
    const element = document.querySelector('.mx-auto');
    const opt = {
        margin: [10, 10],
        filename: 'tableau_bord_maintenance_' + new Date().toISOString().slice(0,10) + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, logging: false },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    // Hide elements that shouldn't be in PDF
    const noPrintElements = document.querySelectorAll('.no-print');
    noPrintElements.forEach(el => el.style.display = 'none');

    html2pdf().set(opt).from(element).save().then(() => {
        // Restore elements
        noPrintElements.forEach(el => el.style.display = '');
    });
}
</script>

<?php require 'footer.php'; ?>
