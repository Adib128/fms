<?php
// Only include header.php if not already routed
if (!defined('ROUTED')) {
    require 'header.php';
}

// Security check
require_once __DIR__ . '/helpers/security.php';
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

$stationsStmt = $db->query("SELECT id_station, lib FROM station ORDER BY id_station ASC");
$stations = $stationsStmt ? $stationsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Liste des documents carburant</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= url('enregistrer-carburant') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Enrégistrer Document Vrac
                </a>
                <a href="<?= url('enregistrer-bon') ?>" class="btn-default">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2h12a2 2 0 0 1 2 2v16l-8-3-8 3V4a2 2 0 0 1 2-2Z" />
                    </svg>
                    Enrégistrer Document Carte
                </a>
            </div>
        </div>

        <?php if (hasFlashMessage()) : ?>
            <div class="alert-success flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9z" />
                </svg>
                <div class="flex-1 text-sm">
                    <?= getFlashMessage() ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="panel min-h-[800px]">
            <div class="panel-heading flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <span>Documents carburant</span>
                <div class="flex items-center gap-2">
                    <select id="stationFilter" class="form-control w-64" data-skip-tom-select="true">
                        <option value="">Toutes les agences</option>
                        <?php foreach ($stations as $station) : ?>
                            <option value="<?= htmlspecialchars($station['id_station'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($station['lib'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="typeFilter" class="form-control w-48" data-skip-tom-select="true">
                        <option value="">Tous les types</option>
                        <option value="Vrac">Vrac</option>
                        <option value="Carte">Carte</option>
                    </select>
                </div>
            </div>
            <div class="panel-body overflow-x-auto">
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
                            min-width: 800px !important;
                        }
                        .table td:not(.text-right) {
                            white-space: nowrap;
                        }
                        /* Prevent layout shift during initialization */
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
                    </style>
                    <table class="table" id="tab">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Numéro Doc</th>
                                <th>Date</th>
                                <th>Index début</th>
                                <th>Index fin</th>
                                <th>Agence</th>
                                <th class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
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
    $(function () {
        const titreRapport = 'Documents carburant';

        const table = $('#tab').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,        // Defer rendering for better performance
            pageLength: 30,
            ajax: {
                url: 'liste_doc_carburant_data.php',
                data: function (data) {
                    data.station = $('#stationFilter').val();
                    data.type = $('#typeFilter').val();
                },
                error: function (xhr, error, code) {
                    console.error('DataTables Ajax Error:', xhr, error, code);
                    
                    // Hide loader and show error
                    $('#tableLoader').fadeOut(200, function() {
                        $(this).remove();
                    });
                    
                    // Try to get error message from response
                    let errorMessage = 'Erreur de chargement des données';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMessage = response.error;
                        }
                    } catch (e) {
                        errorMessage = xhr.statusText || 'Erreur réseau';
                    }
                    
                    // Display error message
                    $('#tableContainer').prepend(
                        '<div class="alert alert-danger">' +
                        '<strong>Erreur:</strong> ' + errorMessage + 
                        '<br><small>Veuillez contacter l\'administrateur système.</small>' +
                        '</div>'
                    ).show();
                }
            },
            order: [],
            columns: [
                { data: 'type' },
                { data: 'num_doc_carburant' },
                { data: 'date' },
                { data: 'index_debut' },
                { data: 'index_fin' },
                { data: 'station' },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
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
                sSearch: 'Rechercher\u00a0:',
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
                },
                select: {
                    rows: {
                        _: '%d lignes sélectionnées',
                        0: 'Aucune ligne sélectionnée',
                        1: '1 ligne sélectionnée'
                    }
                }
            },
            initComplete: function () {
                // Hide loader and show table with smooth transition
                setTimeout(function() {
                    $('#tableLoader').fadeOut(200, function() {
                        $(this).remove();
                        $('#tableContainer').fadeIn(300);
                        $('.dataTables_wrapper').addClass('loaded');
                    });
                }, 300); // Small delay to ensure everything is ready
            }
        });

        $('#stationFilter').on('change', function () {
            table.ajax.reload();
        });

        $('#typeFilter').on('change', function () {
            table.ajax.reload();
        });
    });
</script>
</body>
</html>