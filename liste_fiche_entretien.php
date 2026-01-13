<?php
require 'header.php';

// Security check
require_once __DIR__ . '/helpers/security.php';
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-green-800"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Fiches d'entretien</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= url('ajouter-fiche-entretien') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Enregistrer fiche
                </a>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <span>Liste des fiches d'entretien</span>
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
                            min-width: 720px;
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
                    <table class="table compact-table" id="tab">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Numéro fiche</th>
                                <th>Date</th>
                                <th>Agence</th>
                                <th class="text-right"></th>
                            </tr>
                        </thead>
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
        const titreRapport = "Fiches d'entretien";

        const table = $('#tab').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,        // Defer rendering for better performance
            order: [[0, 'desc']],      // Default ordering by ID column (newest first)
            ajax: {
                url: 'api/liste_fiche_entretien_server.php',
                type: 'POST',
                error: function (xhr, error, code) {
                    console.error('DataTables Ajax Error:', xhr, error, code);
                    
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
            columnDefs: [
                { targets: 0, visible: false, searchable: false }
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
            pageLength: 30,
            responsive: true,
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
