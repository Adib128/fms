<?php
require 'header.php';

// Security check
require_once __DIR__ . '/helpers/security.php';
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Demandes de passation</h1>
                <p class="text-sm text-slate-500">Suivi des demandes de passation de véhicules entre chauffeurs.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= url('ajouter-passation-demande') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Nouvelle demande
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION["message"])) : ?>
            <div class="alert-success flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9z" />
                </svg>
                <div class="flex-1 text-sm">
                    <?php echo $_SESSION["message"]; unset($_SESSION["message"]); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-body">
                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg border border-gray-100">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Date Début</label>
                        <input type="date" id="filter_date_debut" class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Date Fin</label>
                        <input type="date" id="filter_date_fin" class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">État</label>
                        <select id="filter_etat" data-skip-tom-select="true" class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Tous les états</option>
                            <option value="En cours">En cours</option>
                            <option value="Accepter">Accepter</option>
                            <option value="Rejeter">Rejeter</option>
                            <option value="Cloturer">Cloturer</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" id="reset_filters" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Réinitialiser les filtres</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <div id="tableLoader" class="flex flex-col items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
                        <p class="text-sm text-gray-600">Chargement des données...</p>
                    </div>
                    
                    <div id="tableContainer" style="display: none;">
                        <table class="table compact-table" id="passationTable">
                            <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>Date</th>
                                    <th class="search_select">Véhicule</th>
                                    <th class="search_select">Cédant</th>
                                    <th class="search_select">Repreneur</th>
                                    <th class="search_select">État</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->query("
                                        SELECT dp.*, 
                                               b.matricule_interne,
                                               c1.nom_prenom as cedant_nom, c1.matricule as cedant_mat,
                                               c2.nom_prenom as repreneur_nom, c2.matricule as repreneur_mat
                                        FROM demande_passation dp
                                        LEFT JOIN bus b ON dp.id_vehicule = b.id_bus
                                        LEFT JOIN chauffeur c1 ON dp.id_chauffeur_cedant = c1.id_chauffeur
                                        LEFT JOIN chauffeur c2 ON dp.id_chauffeur_repreneur = c2.id_chauffeur
                                        ORDER BY dp.date DESC, dp.id DESC
                                    ");
                                    
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $etatClass = match($row['etat']) {
                                            'Accepter' => 'bg-green-100 text-green-800 border-green-200',
                                            'Rejeter' => 'bg-red-100 text-red-800 border-red-200',
                                            'Cloturer' => 'bg-slate-900 text-white border-slate-900',
                                            default => 'bg-blue-100 text-blue-800 border-blue-200' // En cours
                                        };
                                        ?>
                                        <tr>
                                            <td class="font-medium"><?= htmlspecialchars($row['numero']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($row['date'])) ?></td>
                                            <td><?= htmlspecialchars($row['matricule_interne']) ?></td>
                                            <td><?= htmlspecialchars($row['cedant_mat']) ?> - <?= htmlspecialchars($row['cedant_nom']) ?></td>
                                            <td><?= htmlspecialchars($row['repreneur_mat']) ?> - <?= htmlspecialchars($row['repreneur_nom']) ?></td>
                                            <td>
                                                <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium <?= $etatClass ?>">
                                                    <?= htmlspecialchars($row['etat']) ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div class="flex gap-1 justify-end">
                                                    <a href="<?= url('modifier-passation-demande') ?>?id=<?= $row['id'] ?>" 
                                                       class="btn-success py-1 px-2" 
                                                       title="Modifier">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                        </svg>
                                                    </a>
                                                    <a href="<?= url('supprimer-passation-demande') ?>?id=<?= $row['id'] ?>" 
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette demande ?');" 
                                                       class="btn-danger py-1 px-2" 
                                                       title="Supprimer">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M3 6h18"></path>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="7" class="text-center text-red-600">Erreur: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
$(document).ready(function () {
    var titreRapport = "Demandes de passation";

    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'passationTable') return true;

            var dateDebut = $('#filter_date_debut').val();
            var dateFin = $('#filter_date_fin').val();
            var etat = $('#filter_etat').val();
            
            var rowDateStr = data[1]; // Date column
            var rowDate = new Date(rowDateStr.split(' ')[0].split('/').reverse().join('-'));
            var cellEtat = data[5]; 
            var rowEtat = $('<div>').html(cellEtat).text().trim().toLowerCase(); 

            if (dateDebut || dateFin) {
                if (dateDebut && new Date(dateDebut) > rowDate) return false;
                if (dateFin && new Date(dateFin) < rowDate) return false;
            }

            if (etat && rowEtat !== etat.toLowerCase()) {
                return false;
            }

            return true;
        }
    );

    const table = $('#passationTable').DataTable({
        bSort: false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'print',
                title: titreRapport,
                text: 'Imprimer',
                className: 'btn btn-indigo btn-sm',
                exportOptions: { columns: ':visible:not(.text-right)' }
            },
            {
                extend: 'excelHtml5',
                title: titreRapport,
                text: 'Excel',
                className: 'btn btn-emerald btn-sm',
                exportOptions: { columns: ':visible:not(.text-right)' }
            }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
        },
        initComplete: function () {
            $('#tableLoader').fadeOut(200, function() {
                $(this).remove();
                $('#tableContainer').fadeIn(300);
            });
        }
    });

    $('#filter_date_debut, #filter_date_fin, #filter_etat').on('change', function() {
        table.draw();
    });

    $('#reset_filters').on('click', function() {
        $('#filter_date_debut, #filter_date_fin, #filter_etat').val('');
        table.draw();
    });
});
</script>
</body>
</html>
