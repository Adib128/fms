<?php
require 'header.php';
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Disponibilité des véhicules</h1>
                <p class="text-sm text-slate-500">État en temps réel de la flotte (Réparation, Immobilisation, Disponibilité).</p>
            </div>
        </div>

        <?php
        $today = date('Y-m-d');

        // 1. Vehicles in Repair (Marked as 'En réparation' in bus table)
        $repair_query = "SELECT b.*, s.lib as station_lib
                        FROM bus b 
                        LEFT JOIN station s ON b.id_station = s.id_station
                        WHERE b.etat = 'En réparation' 
                        ORDER BY b.matricule_interne ASC";
        $repair_list = $db->query($repair_query)->fetchAll(PDO::FETCH_ASSOC);
        $repair_ids = array_column($repair_list, 'id_bus');

        // 2. Immobilized Vehicles (Status 'Immobiliser' in bus table)
        $immob_query = "SELECT DISTINCT b.*, s.lib as station_lib, i.commentaire, i.start_date, i.end_date
                        FROM bus b 
                        LEFT JOIN immobilisation i ON b.id_bus = i.id_vehicule AND :today BETWEEN i.start_date AND IFNULL(i.end_date, '9999-12-31')
                        LEFT JOIN station s ON b.id_station = s.id_station
                        WHERE b.etat = 'Immobiliser'
                        ORDER BY b.matricule_interne ASC";
        $stmt_immob = $db->prepare($immob_query);
        $stmt_immob->execute([':today' => $today]);
        $immob_list = $stmt_immob->fetchAll(PDO::FETCH_ASSOC);
        $immob_ids = array_column($immob_list, 'id_bus');

        // 3. Available Vehicles (Not in repair, not immobilized, and not reformed)
        $exclude_ids = array_unique(array_merge($repair_ids, $immob_ids));
        $exclude_sql = !empty($exclude_ids) ? "WHERE id_bus NOT IN (" . implode(',', $exclude_ids) . ") AND etat != 'Réformé'" : "WHERE etat != 'Réformé'";
        $dispo_query = "SELECT b.*, s.lib as station_lib 
                        FROM bus b 
                        LEFT JOIN station s ON b.id_station = s.id_station 
                        $exclude_sql 
                        ORDER BY b.matricule_interne ASC";
        $dispo_list = $db->query($dispo_query)->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Metrics (excluding reformed vehicles)
        $total_repair = count($repair_list);
        $total_immob = count($immob_list);
        $total_dispo = count($dispo_list);
        $total_vehicles = $total_repair + $total_immob + $total_dispo; // Only active vehicles
        $availability_rate = $total_vehicles > 0 ? (($total_vehicles - $total_immob) * 100) / $total_vehicles : 0;
        ?>


        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Vehicles -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium uppercase tracking-wider">Total Véhicules</p>
                        <p class="text-3xl font-bold mt-2"><?= $total_vehicles ?></p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <span class="iconify h-6 w-6" data-icon="mdi:bus"></span>
                    </div>
                </div>
            </div>

            <!-- En Réparation -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-sm font-medium uppercase tracking-wider">En Réparation</p>
                        <p class="text-3xl font-bold mt-2"><?= $total_repair ?></p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <span class="iconify h-6 w-6" data-icon="mdi:wrench"></span>
                    </div>
                </div>
            </div>

            <!-- Immobilisés -->
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-amber-100 text-sm font-medium uppercase tracking-wider">Immobilisés</p>
                        <p class="text-3xl font-bold mt-2"><?= $total_immob ?></p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <span class="iconify h-6 w-6" data-icon="mdi:bus-stop"></span>
                    </div>
                </div>
            </div>

            <!-- Taux de Disponibilité -->
            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-emerald-100 text-sm font-medium uppercase tracking-wider">Taux de Disponibilité</p>
                        <p class="text-3xl font-bold mt-2"><?= number_format($availability_rate, 1) ?>%</p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <span class="iconify h-6 w-6" data-icon="mdi:check-circle"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. En Réparation -->
        <div class="panel border-l-4 border-red-500">
            <div class="panel-heading flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="iconify text-red-600 h-5 w-5" data-icon="mdi:wrench"></span>
                    <span>Véhicules En Réparation</span>
                </div>
                <span class="badge bg-red-100 text-red-800"><?= count($repair_list) ?> véhicules</span>
            </div>
            <div class="panel-body overflow-x-auto">
                <table class="table compact-table datatable" data-title="Véhicules En Réparation">
                    <thead>
                        <tr>
                            <th>Numéro Parc</th>
                            <th>Genre</th>
                            <th>Marque</th>
                            <th>Agence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($repair_list as $row): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($row["matricule_interne"]) ?></td>
                                <td><?= htmlspecialchars($row["type"]) ?></td>
                                <td><?= htmlspecialchars($row["marque"]) ?></td>
                                <td><?= htmlspecialchars($row["station_lib"] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. Disponibles -->
        <div class="panel border-l-4 border-green-500">
            <div class="panel-heading flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="iconify text-green-600 h-5 w-5" data-icon="mdi:check-circle"></span>
                    <span>Véhicules Disponibles</span>
                </div>
                <span class="badge bg-green-100 text-green-800"><?= count($dispo_list) ?> véhicules</span>
            </div>
            <div class="panel-body overflow-x-auto">
                <table class="table compact-table datatable" data-title="Véhicules Disponibles">
                    <thead>
                        <tr>
                            <th>Numéro Parc</th>
                            <th>Genre</th>
                            <th>Marque</th>
                            <th>Agence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dispo_list as $row): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($row["matricule_interne"]) ?></td>
                                <td><?= htmlspecialchars($row["type"]) ?></td>
                                <td><?= htmlspecialchars($row["marque"]) ?></td>
                                <td><?= htmlspecialchars($row["station_lib"] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Immobilisés -->
        <div class="panel border-l-4 border-amber-500">
            <div class="panel-heading flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="iconify text-amber-600 h-5 w-5" data-icon="mdi:bus-stop"></span>
                    <span>Véhicules Immobilisés</span>
                </div>
                <span class="badge bg-amber-100 text-amber-800"><?= count($immob_list) ?> véhicules</span>
            </div>
            <div class="panel-body overflow-x-auto">
                <table class="table compact-table datatable" data-title="Véhicules Immobilisés">
                    <thead>
                        <tr>
                            <th>Numéro Parc</th>
                            <th>Période</th>

                            <th>Agence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($immob_list as $row): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($row["matricule_interne"]) ?></td>
                                <td class="text-xs">
                                    Du <?= date('d/m/Y', strtotime($row['start_date'])) ?>
                                    <?= $row['end_date'] ? ' au ' . date('d/m/Y', strtotime($row['end_date'])) : ' (En cours)' ?>
                                </td>

                                <td><?= htmlspecialchars($row["station_lib"] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
        // 4. Reformed Vehicles (etat = 'Réformé')
        $reforme_query = "SELECT b.*, s.lib as station_lib
                        FROM bus b 
                        LEFT JOIN station s ON b.id_station = s.id_station
                        WHERE b.etat = 'Réformé' 
                        ORDER BY b.matricule_interne ASC";
        $reforme_list = $db->query($reforme_query)->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <!-- 4. Véhicules Réformés -->
        <div class="panel border-l-4 border-gray-500">
            <div class="panel-heading flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="iconify text-gray-600 h-5 w-5" data-icon="mdi:archive"></span>
                    <span>Liste des Véhicules Réformés</span>
                </div>
                <span class="badge bg-gray-100 text-gray-800"><?= count($reforme_list) ?> véhicules</span>
            </div>
            <div class="panel-body overflow-x-auto">
                <table class="table compact-table datatable" data-title="Véhicules Réformés">
                    <thead>
                        <tr>
                            <th>Numéro Parc</th>
                            <th>Genre</th>
                            <th>Marque</th>
                            <th>Agence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reforme_list as $row): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($row["matricule_interne"]) ?></td>
                                <td><?= htmlspecialchars($row["type"]) ?></td>
                                <td><?= htmlspecialchars($row["marque"]) ?></td>
                                <td><?= htmlspecialchars($row["station_lib"] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
    $(document).ready(function () {
        $('.datatable').each(function() {
            const title = $(this).data('title') || 'Export';
            $(this).DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
                },
                pageLength: 10,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'print',
                        text: '<span class="flex items-center gap-1"><span class="iconify" data-icon="mdi:printer"></span> Imprimer</span>',
                        className: 'btn-default !py-1 !px-3 !min-h-0 !text-xs',
                        title: title
                    },
                    {
                        extend: 'excelHtml5',
                        text: '<span class="flex items-center gap-1"><span class="iconify" data-icon="mdi:file-excel"></span> Excel</span>',
                        className: 'btn-default !py-1 !px-3 !min-h-0 !text-xs',
                        title: title
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<span class="flex items-center gap-1"><span class="iconify" data-icon="mdi:file-pdf"></span> PDF</span>',
                        className: 'btn-default !py-1 !px-3 !min-h-0 !text-xs',
                        title: title
                    }
                ]
            });
        });
    });
</script>
</body>
</html>
