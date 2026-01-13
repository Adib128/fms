<?php
require 'header.php';

// Default date range (last 30 days)
$date_fin = date('Y-m-d');
$date_debut = date('Y-m-d', strtotime('-30 days'));

if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
    $date_debut = $_GET['date_debut'];
}
if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
    $date_fin = $_GET['date_fin'];
}
$id_vehicule = isset($_GET['id_vehicule']) ? $_GET['id_vehicule'] : '';

// --- Demandes Query Logic ---
$where_demande = ["d.date BETWEEN :start AND :end"];
$params_demande = [':start' => $date_debut, ':end' => $date_fin];

if ($id_vehicule) {
    $where_demande[] = "d.id_vehicule = :id_vehicule";
    $params_demande[':id_vehicule'] = $id_vehicule;
}
$where_sql_demande = implode(" AND ", $where_demande);

// Demandes stats
$stmt_demandes = $db->prepare("SELECT d.etat, COUNT(*) as count FROM demande d WHERE $where_sql_demande GROUP BY d.etat");
$stmt_demandes->execute($params_demande);
$demandes_stats = $stmt_demandes->fetchAll(PDO::FETCH_ASSOC);

// --- Work Orders Query Logic ---
$where_ordre = ["o.date BETWEEN :start AND :end"];
$params_ordre = [':start' => $date_debut, ':end' => $date_fin];

if ($id_vehicule) {
    $where_ordre[] = "d.id_vehicule = :id_vehicule";
    $params_ordre[':id_vehicule'] = $id_vehicule;
}
$where_sql_ordre = implode(" AND ", $where_ordre);

// Work Orders stats
$stmt_ordres = $db->prepare("SELECT o.etat, COUNT(*) as count FROM ordre o LEFT JOIN demande d ON o.id_demande = d.id WHERE $where_sql_ordre GROUP BY o.etat");
$stmt_ordres->execute($params_ordre);
$ordres_stats = $stmt_ordres->fetchAll(PDO::FETCH_ASSOC);

// Total counts
$total_demandes = array_sum(array_column($demandes_stats, 'count'));
$total_ordres = array_sum(array_column($ordres_stats, 'count'));

// Repetitive Failures (Top 5 types de panne)
$stmt_failures = $db->prepare("
    SELECT an.designation as description, COUNT(*) as count 
    FROM ordre o 
    JOIN ordre_anomalie oa ON o.id = oa.id_ordre 
    JOIN anomalie an ON oa.id_anomalie = an.id 
    LEFT JOIN demande d ON o.id_demande = d.id 
    WHERE $where_sql_ordre 
    GROUP BY an.designation 
    ORDER BY count DESC 
    LIMIT 5
");
$stmt_failures->execute($params_ordre);
$top_failures = $stmt_failures->fetchAll(PDO::FETCH_ASSOC);

?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Historique Maintenance</h1>
                <p class="text-sm text-slate-500">Analyse et statistiques de la maintenance curative.</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="panel">
            <div class="panel-body">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Date Début</label>
                        <input type="date" name="date_debut" value="<?= $date_debut ?>" class="w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Date Fin</label>
                        <input type="date" name="date_fin" value="<?= $date_fin ?>" class="w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Véhicule</label>
                        <select name="id_vehicule" id="vehiculeSelect" class="w-full rounded-lg border-slate-200 text-sm">
                            <option value="">Tous les véhicules</option>
                            <?php
                            $vehicules = $db->query("SELECT id_bus, matricule_interne FROM bus ORDER BY matricule_interne ASC");
                            foreach ($vehicules as $v) {
                                $selected = ($id_vehicule == $v['id_bus']) ? 'selected' : '';
                                echo "<option value='{$v['id_bus']}' $selected>{$v['matricule_interne']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary flex-1">
                            <span class="iconify h-4 w-4 mr-1" data-icon="mdi:filter"></span>
                            Filtrer
                        </button>
                        <a href="<?= url('historique-maintenance') ?>" class="btn-secondary">
                            Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Analytics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="panel p-6 flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <span class="iconify h-6 w-6" data-icon="mdi:alert-circle"></span>
                </div>
                <div>
                    <p class="text-sm text-slate-500 font-medium">Total Demandes</p>
                    <p class="text-2xl font-bold text-slate-900"><?= $total_demandes ?></p>
                </div>
            </div>
            <div class="panel p-6 flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                    <span class="iconify h-6 w-6" data-icon="mdi:clipboard-list"></span>
                </div>
                <div>
                    <p class="text-sm text-slate-500 font-medium">Total Ordres de Travail</p>
                    <p class="text-2xl font-bold text-slate-900"><?= $total_ordres ?></p>
                </div>
            </div>
            <div class="panel p-6 flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl bg-green-50 flex items-center justify-center text-green-600">
                    <span class="iconify h-6 w-6" data-icon="mdi:check-circle"></span>
                </div>
                <div>
                    <p class="text-sm text-slate-500 font-medium">Taux de Clôture</p>
                    <p class="text-2xl font-bold text-slate-900">
                        <?php
                        $cloturees_ordres = 0;
                        foreach ($ordres_stats as $s) {
                            if ($s['etat'] === 'cloturer') $cloturees_ordres = $s['count'];
                        }
                        echo $total_ordres > 0 ? round(($cloturees_ordres / $total_ordres) * 100, 1) . '%' : '0%';
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="panel">
                <div class="panel-heading">Répartition des Demandes</div>
                <div class="panel-body h-64">
                    <canvas id="demandesChart"></canvas>
                </div>
            </div>
            <div class="panel">
                <div class="panel-heading">Répartition des Ordres de Travail</div>
                <div class="panel-body h-64">
                    <canvas id="ordresChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Repetitive Failures -->
        <div class="panel">
            <div class="panel-heading">Pannes Répétitives (Top 5)</div>
            <div class="panel-body">
                <div class="space-y-4">
                    <?php if (empty($top_failures)): ?>
                        <p class="text-sm text-slate-500 text-center py-4">Aucune donnée disponible pour cette période.</p>
                    <?php else: ?>
                        <?php foreach ($top_failures as $f): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-slate-900"><?= htmlspecialchars($f['description']) ?></p>
                                    <div class="w-full bg-slate-100 rounded-full h-2 mt-1">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?= ($f['count'] / $top_failures[0]['count']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <span class="ml-4 badge bg-blue-50 text-blue-700"><?= $f['count'] ?> occurrences</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Detailed Lists -->
        <div class="grid grid-cols-1 gap-6">
            <div class="panel">
                <div class="panel-heading">Détails des Demandes</div>
                <div class="panel-body overflow-x-auto">
                    <table class="table compact-table" id="demandesTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Véhicule</th>
                                <th>Description</th>
                                <th>État</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt_list = $db->prepare("SELECT d.*, b.matricule_interne FROM demande d JOIN bus b ON d.id_vehicule = b.id_bus WHERE $where_sql_demande ORDER BY d.date DESC");
                            $stmt_list->execute($params_demande);
                            foreach ($stmt_list as $row):
                                $etatClass = 'bg-slate-100 text-slate-800';
                                switch($row['etat']) {
                                    case 'en cours': $etatClass = 'bg-blue-100 text-blue-800'; break;
                                    case 'valide': $etatClass = 'bg-amber-100 text-amber-800'; break;
                                    case 'cloturer': $etatClass = 'bg-green-100 text-green-800'; break;
                                }
                            ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                                    <td><?= htmlspecialchars($row['matricule_interne']) ?></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><span class="badge <?= $etatClass ?>"><?= $row['etat'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-heading">Détails des Ordres de Travail</div>
                <div class="panel-body overflow-x-auto">
                    <table class="table compact-table" id="ordresTable">
                        <thead>
                            <tr>
                                <th>N° OT</th>
                                <th>Date</th>
                                <th>Véhicule</th>
                                <th>Atelier</th>
                                <th>État</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt_list_ordres = $db->prepare("SELECT o.*, a.nom as atelier_nom, b.matricule_interne 
                                                            FROM ordre o 
                                                            LEFT JOIN atelier a ON o.id_atelier = a.id 
                                                            LEFT JOIN demande d ON o.id_demande = d.id 
                                                            LEFT JOIN bus b ON d.id_vehicule = b.id_bus 
                                                            WHERE $where_sql_ordre 
                                                            ORDER BY o.date DESC");
                            $stmt_list_ordres->execute($params_ordre);
                            foreach ($stmt_list_ordres as $row):
                                $etatClass = 'bg-slate-100 text-slate-800';
                                switch($row['etat']) {
                                    case 'ouvert': $etatClass = 'bg-blue-100 text-blue-800'; break;
                                    case 'valider': $etatClass = 'bg-amber-100 text-amber-800'; break;
                                    case 'cloturer': $etatClass = 'bg-green-100 text-green-800'; break;
                                }
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['numero']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                                    <td><?= htmlspecialchars($row['matricule_interne'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['atelier_nom'] ?? 'N/A') ?></td>
                                    <td><span class="badge <?= $etatClass ?>"><?= $row['etat'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="js/jquery.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>


<script>
    $(document).ready(function() {


        // DataTables
        $('#demandesTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' },
            pageLength: 10,
            lengthChange: false
        });

        $('#ordresTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' },
            pageLength: 10,
            lengthChange: false
        });

        // Charts
        const ctxDemandes = document.getElementById('demandesChart').getContext('2d');
        new Chart(ctxDemandes, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($demandes_stats, 'etat')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($demandes_stats, 'count')) ?>,
                    backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#64748b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        const ctxOrdres = document.getElementById('ordresChart').getContext('2d');
        new Chart(ctxOrdres, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($ordres_stats, 'etat')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($ordres_stats, 'count')) ?>,
                    backgroundColor: ['#8b5cf6', '#f59e0b', '#10b981', '#64748b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    });
</script>
</body>
</html>
