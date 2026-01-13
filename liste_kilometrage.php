<?php
require_once __DIR__ . '/app/security.php';
require 'header.php';
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Kilomètrage d'exploitation</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= url('enregistrer-kilometrage') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Enrégistrer kilométrage
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert-success flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9z" />
                </svg>
                <div class="flex-1 text-sm">
                    <?= htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="panel min-h-[800px]">
            <div class="panel-heading">
                <span>Liste kilomètrage d'exploitation</span>
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
                                <th>Date</th>
                                <th>Agence</th>
                                <th>Véhicule</th>
                                <th>Kilométrage</th>
                                <th class="text-center">Actions</th>
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
        const titreRapport = 'Historique des kilométrages';

        const table = $('#tab').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,        // Defer rendering for better performance
            ajax: {
                url: 'liste_kilometrage_json.php',
                type: 'POST',
                error: function (xhr, error, code) {
                    console.error('DataTables error:', error, code);
                    console.error('Response:', xhr.responseText);
                    
                    // Hide loader and show error
                    $('#tableLoader').fadeOut(200, function() {
                        $(this).remove();
                    });
                    
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
            columns: [
                { data: 'date', name: 'date' },
                { data: 'station', name: 'station' },
                { data: 'bus', name: 'bus' },
                { 
                    data: 'kilometrage', 
                    name: 'kilometrage',
                    className: 'text-left',
                    render: function(data) {
                        return data ? data.toLocaleString('fr-FR') : '0';
                    }
                },
                { 
                    data: null, // Actions column
                    className: 'text-center',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return '<a href="supprimer_kilometrage.php?id=' + row.id + '" onclick="return confirm(\'Voulez vous vraiment supprimer la saisie de kilomètrage ?\');" class="btn-danger py-1 px-2 inline-flex items-center justify-center" title="Supprimer">' +
                            '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
                            '<path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />' +
                            '</svg>' +
                            '</a>';
                    }
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr.json',
                processing: "Traitement en cours...",
                search: "Rechercher:",
                lengthMenu: "Afficher _MENU_ enregistrements",
                info: "Affichage de _START_ à _END_ sur _TOTAL_ enregistrements",
                infoEmpty: "Affichage de 0 à 0 sur 0 enregistrements",
                infoFiltered: "(filtré de _MAX_ enregistrements au total)",
                zeroRecords: "Aucun enregistrement trouvé",
                emptyTable: "Aucune donnée disponible dans ce tableau"
            },
            pageLength: 30,
            responsive: true,
            order: [[0, 'desc']],
            dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                  "<'row'<'col-sm-12'tr>>" +
                  "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
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
    });
</script>
</body>
</html>