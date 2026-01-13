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
                <h1 class="page-title">Demandes de réparation</h1>
                <p class="text-sm text-slate-500">Suivi des demandes de réparation et de maintenance.</p>
            </div>
            <?php if ($userProfile !== 'responsable'): ?>
            <div class="flex items-center gap-3">
                <a href="<?= url('ajouter-demande') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Nouvelle demande
                </a>
            </div>
            <?php endif; ?>
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
                </span>
            </div>
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
                            <option value="Valider">Valider</option>
                            <option value="Cloturer">Cloturer</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" id="reset_filters" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Réinitialiser les filtres</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                <!-- Loading indicator -->
                <div id="tableLoader" class="flex flex-col items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
                    <p class="text-sm text-gray-600">Chargement des données...</p>
                </div>
                
                <!-- Table container - initially hidden -->
                <div id="tableContainer" style="display: none;">
                    <style>
                        .table td, .table th {
                            padding: 0.5rem 0.75rem !important;
                            vertical-align: middle;
                        }
                        
                        .table {
                            width: 100% !important;
                            min-width: auto !important;
                        }
                        
                        .table td:not(.text-right) {
                            white-space: normal;
                            word-wrap: break-word;
                            max-width: 150px;
                        }
                        
                        #tableContainer {
                            min-height: 400px;
                        }
                        
                        .dataTables_wrapper {
                            opacity: 0;
                            transition: opacity 0.3s ease-in-out;
                        }
                        
                        .dataTables_wrapper.loaded {
                            opacity: 1;
                        }
                        
                        .dataTables_wrapper tfoot input {
                            width: 100%;
                            padding: 0.25rem 0.5rem;
                            font-size: 0.875rem;
                        }
                        
                        .dataTables_wrapper tfoot select {
                            width: 100%;
                            padding: 0.25rem 0.5rem;
                            font-size: 0.875rem;
                        }
                    </style>
                    <table class="table compact-table" id="demandesTable">
                        <tfoot>
                            <tr>
                                <th>Numéro</th>
                                <th>Numéro BDC</th>
                                <th>Date</th>
                                <th>Véhicule</th>
                                <th>Chauffeur</th>
                                <th>Agence</th>
                                <th>Priorité</th>
                                <th>État</th>
                            </tr>
                        </tfoot>
                        <thead>
                            <tr>
                                <th>Numéro</th>
                                <th>Numéro BDC</th>
                                <th>Date</th>
                                <th class="search_select">Véhicule</th>
                                <th class="search_select">Chauffeur</th>
                                <th class="search_select">Agence</th>
                                <th class="search_select">Priorité</th>
                                <th class="search_select">État</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $db->query("
                                    SELECT d.*, 
                                           b.matricule_interne,
                                           c.nom_prenom, c.matricule,
                                           s.lib as station_name
                                    FROM demande d
                                    LEFT JOIN bus b ON d.id_vehicule = b.id_bus
                                    LEFT JOIN chauffeur c ON d.id_chauffeur = c.id_chauffeur
                                    LEFT JOIN station s ON d.id_station = s.id_station
                                    ORDER BY d.date DESC, d.id DESC
                                ");
                                
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    // Badge styling
                                    $typeClass = match($row['type']) {
                                        'Maintenance préventive' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'Maintenance curative' => 'bg-orange-100 text-orange-800 border-orange-200',
                                        'Maintenance palliative' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        default => 'bg-gray-100 text-gray-800 border-gray-200'
                                    };
                                    
                                    $etatClass = match($row['etat']) {
                                        'Valider' => 'bg-green-100 text-green-800 border-green-200',
                                        'Cloturer' => 'bg-slate-900 text-white border-slate-900',
                                        default => 'bg-blue-100 text-blue-800 border-blue-200' // En cours
                                    };
                                    
                                    $prioriteClass = match($row['priorite']) {
                                        'Basse' => 'bg-gray-100 text-gray-800 border-gray-200',
                                        'Moyenne' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'Haute' => 'bg-orange-100 text-orange-800 border-orange-200',
                                        'Critique' => 'bg-red-100 text-red-800 border-red-200',
                                        default => 'bg-gray-100 text-gray-800 border-gray-200'
                                    };
                                    ?>
                                    <tr>
                                        <td class="font-medium"><?= htmlspecialchars($row['numero']) ?></td>
                                        <td><?= htmlspecialchars($row['num_bdc'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['date']) ?></td>
                                        <td><?= htmlspecialchars($row['matricule_interne']) ?></td>
                                        <td><?= htmlspecialchars($row['matricule'] ?? '-') ?> <?= htmlspecialchars($row['nom_prenom'] ?? '-') ?> </td>
                                        <td><?= htmlspecialchars($row['station_name']) ?></td>
                                        <td>
                                            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium <?= $prioriteClass ?>">
                                                <?= htmlspecialchars($row['priorite']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium <?= $etatClass ?>">
                                                <?= htmlspecialchars($row['etat']) ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex gap-1 justify-center">
                                                <a href="<?= url('consulter-demande') ?>?id=<?= $row['id'] ?>" 
                                                   class="bg-blue-100 text-blue-600 hover:bg-blue-200 py-1 px-2 rounded flex items-center justify-center" 
                                                   title="Consulter">
                                                     <span class="iconify h-4 w-4" data-icon="mdi:eye-outline"></span>
                                                </a>
                                                <?php if ($userProfile !== 'responsable'): ?>
                                                    <?php if ($row['etat'] === 'Cloturer'): ?>
                                                        <button type="button" class="bg-gray-100 text-gray-400 py-1 px-2 rounded cursor-not-allowed" title="Modification impossible (Clôturée)" disabled>
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                            </svg>
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="<?= url('modifier-demande') ?>?id=<?= $row['id'] ?>" 
                                                           class="btn-success py-1 px-2" 
                                                           title="Modifier">
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                            </svg>
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if ($row['etat'] === 'Cloturer'): ?>
                                                        <button type="button" class="bg-gray-100 text-gray-400 py-1 px-2 rounded cursor-not-allowed" title="Suppression impossible (Clôturée)" disabled>
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M3 6h18"></path>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="<?= url('supprimer-demande') ?>?id=<?= $row['id'] ?>" 
                                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette demande ?');" 
                                                           class="btn-danger py-1 px-2" 
                                                           title="Supprimer">
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M3 6h18"></path>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="6" class="text-center text-red-600">Erreur: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
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
    var titreRapport = "Demandes d'intervention";

    // Custom filtering function which will search data in column four between two values
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'demandesTable') return true;

            var dateDebut = $('#filter_date_debut').val();
            var dateFin = $('#filter_date_fin').val();
            var etat = $('#filter_etat').val();
            
            var rowDate = data[2]; // Date column (now index 2)
            var cellEtat = data[7]; // Etat column (now index 7)
            var rowEtat = $('<div>').html(cellEtat).text().trim().toLowerCase(); 

            // Date filter
            if (dateDebut || dateFin) {
                var dDate = new Date(rowDate);
                if (dateDebut && new Date(dateDebut) > dDate) return false;
                if (dateFin && new Date(dateFin) < dDate) return false;
            }

            // Etat filter
            if (etat && rowEtat !== etat.toLowerCase()) {
                return false;
            }

            return true;
        }
    );

    const table = $('#demandesTable').DataTable({
        bSort: false,
        deferRender: true,
        autoWidth: false,
        scrollX: false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'print',
                title: titreRapport,
                text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Imprimer',
                className: 'btn btn-indigo btn-sm',
                exportOptions: { columns: ':visible:not(.text-right)' }
            },
            {
                extend: 'excelHtml5',
                title: titreRapport,
                text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg> Excel',
                className: 'btn btn-emerald btn-sm',
                exportOptions: { columns: ':visible:not(.text-right)' }
            },
            {
                extend: 'pdfHtml5',
                title: titreRapport,
                text: 'Exporter PDF',
                exportOptions: { columns: ':visible:not(.text-right)' }
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
            }
        },
        initComplete: function (settings, json) {
            setTimeout(function() {
                try {
                    // Setup footer filters
                    $('#demandesTable tfoot th').each(function (i) {
                        const $this = $(this);
                        if ($this.length) {
                            $this.html('<input type="text" placeholder="Filtrer" data-index="' + i + '" />');
                        }
                    });

                    // Setup select filters
                    table.api().columns('.search_select').every(function () {
                        const column = this;
                        const $footer = $(column.footer());
                        
                        if ($footer.length) {
                            const select = $('<select data-skip-tom-select="true"><option value=""></option></select>')
                                .appendTo($footer.empty())
                                .on('change', function () {
                                    const val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column.search(val ? '^' + val + '$' : '', true, false).draw();
                                });

                            column.data().unique().sort().each(function (d) {
                                if (d) {
                                    // Remove HTML tags for select options
                                    const text = $('<div>').html(d).text();
                                    select.append('<option value="' + text + '">' + text + '</option>');
                                }
                            });
                        }
                    });

                    // Hide loader and show table
                    $('#tableLoader').fadeOut(200, function() {
                        $(this).remove();
                        $('#tableContainer').fadeIn(300);
                        $('.dataTables_wrapper').addClass('loaded');
                    });
                } catch (error) {
                    console.error('DataTables initialization error:', error);
                    $('#tableLoader').fadeOut(200, function() {
                        $(this).remove();
                        $('#tableContainer').fadeIn(300);
                        $('.dataTables_wrapper').addClass('loaded');
                    });
                }
            }, 100);
        }
    });

    // Setup footer search
    $(document).on('keyup', 'tfoot input', function () {
        try {
            const columnIndex = $(this).data('index');
            if (columnIndex !== undefined && table) {
                table.column(columnIndex).search(this.value).draw();
            }
        } catch (error) {
            console.error('Footer search error:', error);
        }
    });

    // Trigger redraw on filter change
    $('#filter_date_debut, #filter_date_fin, #filter_etat').on('change', function() {
        table.draw();
    });

    // Reset filters
    $('#reset_filters').on('click', function() {
        $('#filter_date_debut, #filter_date_fin, #filter_etat').val('');
        table.draw();
    });
});
</script>
</body>
</html>
