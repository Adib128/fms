<?php
require 'header.php' ?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Liste des véhicules</h1>
                <p class="text-sm text-slate-500">Vue consolidée des véhicules du parc et de leurs indicateurs clés.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= url('ajouter-vehicule') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Nouveau véhicule
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
            <div class="panel-heading">
                <span>État du parc</span>
                <span class="badge">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18" />
                        <path d="m7 16 3-3 2 2 5-5" />
                    </svg>
                    Actualisé
                </span>
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
                        /* Responsive table styles */
                        .table td, .table th {
                            padding: 0.5rem 0.75rem !important;
                            vertical-align: middle;
                        }
                        
                        /* Remove fixed min-width to allow responsive behavior */
                        .table {
                            width: 100% !important;
                            min-width: auto !important;
                        }
                        
                        /* Allow text wrapping for better responsiveness */
                        .table td:not(.text-right) {
                            white-space: normal;
                            word-wrap: break-word;
                            max-width: 150px;
                        }
                        
                        /* Responsive column widths */
                        .table th:nth-child(1), /* Numéro Parc */
                        .table td:nth-child(1) {
                            min-width: 90px;
                            max-width: 110px;
                        }
                        
                        .table th:nth-child(2), /* Genre */
                        .table td:nth-child(2) {
                            min-width: 80px;
                            max-width: 100px;
                        }
                        
                        .table th:nth-child(3), /* Marque */
                        .table td:nth-child(3) {
                            min-width: 100px;
                            max-width: 120px;
                        }
                        
                        .table th:nth-child(4), /* Agence */
                        .table td:nth-child(4) {
                            min-width: 80px;
                            max-width: 110px;
                        }
                        
                        .table th:nth-child(5), /* Km Exp */
                        .table td:nth-child(5),
                        .table th:nth-child(6), /* Km Index */
                        .table td:nth-child(6) {
                            min-width: 80px;
                            max-width: 100px;
                            text-align: right;
                        }
                        
                        .table th:nth-child(7), /* Actions */
                        .table td:nth-child(7) {
                            min-width: 120px;
                            max-width: 140px;
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
                        
                        /* Responsive adjustments for small screens */
                        @media (max-width: 1024px) {
                            .table td:not(.text-right) {
                                max-width: 120px;
                                font-size: 0.875rem;
                            }
                            
                            /* Smaller columns on tablet */
                            .table th:nth-child(1), /* Numéro Parc */
                            .table td:nth-child(1) {
                                min-width: 80px;
                                max-width: 95px;
                            }
                            
                            .table th:nth-child(4), /* Agence */
                            .table td:nth-child(4) {
                                min-width: 70px;
                                max-width: 95px;
                            }
                            
                            .table th,
                            .table td {
                                padding: 0.375rem 0.5rem !important;
                            }
                            
                            /* Compact action buttons */
                            .btn-info, .btn-success, .btn-danger {
                                padding: 0.25rem 0.5rem !important;
                                font-size: 0.75rem;
                            }
                            
                            .btn-info svg, .btn-success svg, .btn-danger svg {
                                width: 1rem;
                                height: 1rem;
                            }
                        }
                        
                        @media (max-width: 768px) {
                            .table td:not(.text-right) {
                                max-width: 100px;
                                font-size: 0.8rem;
                            }
                            
                            /* Even smaller columns on mobile */
                            .table th:nth-child(1), /* Numéro Parc */
                            .table td:nth-child(1) {
                                min-width: 70px;
                                max-width: 85px;
                            }
                            
                            .table th:nth-child(4), /* Agence */
                            .table td:nth-child(4) {
                                min-width: 65px;
                                max-width: 85px;
                            }
                            
                            .table th,
                            .table td {
                                padding: 0.25rem 0.375rem !important;
                            }
                            
                            /* Even more compact buttons */
                            .btn-info, .btn-success, .btn-danger {
                                padding: 0.125rem 0.25rem !important;
                            }
                            
                            .btn-info svg, .btn-success svg, .btn-danger svg {
                                width: 0.875rem;
                                height: 0.875rem;
                            }
                            
                            /* Stack action buttons vertically if needed */
                            .flex.gap-1 {
                                flex-direction: column;
                                gap: 0.25rem !important;
                            }
                        }
                        
                        /* DataTables responsive adjustments */
                        .dataTables_length,
                        .dataTables_filter {
                            margin-bottom: 0.5rem;
                        }
                        
                        .dataTables_paginate {
                            margin-top: 0.5rem;
                        }
                        
                        /* Footer search inputs responsive */
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
                    <table class="table compact-table" id="tab">
                        <tfoot>
                            <tr>
                                <th>Numéro Parc</th>
                                <th>Genre</th>
                                <th>Marque</th>
                                <th>Agence</th>
                                <th>Km Exp</th>
                                <th>Km Index</th>
                                <th>Type Carburant</th>
                                <th>État</th>
                            </tr>
                        </tfoot>
                        <thead>
                            <tr>
                                <th>Numéro Parc</th>
                                <th class="search_select">Genre</th>
                                <th class="search_select">Marque</th>
                                <th class="search_select">Agence</th>
                                <th>Km Exp</th>
                                <th>Km Index</th>
                                <th class="search_select">Type Carburant</th>
                                <th class="search_select">État</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $liste = $db->query("
                                SELECT b.*, 
                                       om.name as huile_moteur_name, 
                                       ob.name as huile_boite_name, 
                                       op.name as huile_pont_name 
                                FROM bus b
                                LEFT JOIN oil_types om ON b.huile_moteur = om.id
                                LEFT JOIN oil_types ob ON b.huile_boite_vitesse = ob.id
                                LEFT JOIN oil_types op ON b.huile_pont = op.id
                                ORDER BY b.id_bus DESC
                            ");
                            foreach ($liste as $row) {
                                ?>
                                <tr>
                                    <td><?php echo $row["matricule_interne"]; ?></td>
                                    <td><?php echo $row["type"]; ?></td>
                                    <td><?php echo $row["marque"]; ?></td>
                                    <td>
                                        <?php
                                        $id_station = $row["id_station"];
                                        $item = $db->query("SELECT * FROM station WHERE id_station='$id_station'");
                                        foreach ($item as $cr) {
                                            $lib = $cr["lib"];
                                        }
                                        echo $lib;
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $id_bus = $row["id_bus"];
                                        $params = array(
                                            'id_bus' => $id_bus
                                        );
                                        $select = $db->prepare("SELECT * FROM total_kilometrage WHERE id_bus=:id_bus");
                                        $select->execute($params);
                                        $e = $select->fetch(PDO::FETCH_NUM);

                                        if ($e == true) {
                                            $liste_kilometrage = $db->query("SELECT * FROM total_kilometrage WHERE id_bus='$id_bus'");
                                            foreach ($liste_kilometrage as $item) {
                                                $kilometrage = $item["kilometrage"];
                                            }
                                            echo $kilometrage;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $id_bus = $row["id_bus"];
                                        $select = $db->query("SELECT MAX(index_km) AS mx FROM carburant WHERE id_bus='$id_bus'");
                                        foreach ($select as $item) {
                                            $max_carburant = $item["mx"];
                                        }
                                        echo $max_carburant;
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $row["carburant_type"]; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $etat = $row['etat'] ?? 'Disponible';
                                        $etatClass = match($etat) {
                                            'Disponible' => 'bg-green-100 text-green-800',
                                            'En réparation' => 'bg-blue-100 text-blue-800',
                                            'Immobiliser' => 'bg-red-100 text-red-800',
                                            'Réformé' => 'bg-slate-900 text-white',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $etatClass ?>">
                                            <?= $etat ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex gap-1 justify-center">
                                            <a href="<?= url('vehicule-details') ?>?id=<?php echo $row["id_bus"] ?>" class="btn-info py-1 px-2" title="Détails du véhicule">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                    <polyline points="14 2 14 8 20 8"></polyline>
                                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                                    <polyline points="10 9 9 9 8 9"></polyline>
                                                </svg>
                                            </a>
                                            <a href="<?= url('modifier-vehicule') ?>?id=<?php echo $row["id_bus"] ?>" class="btn-success py-1 px-2" title="Modifier véhicule">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </a>
                                            <a href="<?= url('supprimer-vehicule') ?>?id=<?php echo $row["id_bus"] ?>" onclick="return confirm('Voulez vous vraiment supprimer le véhicule ?');" class="btn-danger py-1 px-2" title="Supprimer véhicule">
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
        // Initialize DataTables with deferred rendering
        var titreRapport = "État des véhicules";

        const table = $('#tab').DataTable({
            bSort: false,
            deferRender: true,        // Defer rendering for better performance
            autoWidth: false,         // Disable automatic column width calculation
            scrollX: false,           // Disable horizontal scrolling
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
                    exportOptions: { columns: [0, 1, 2, 3, 4] }
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
                },
                select: {
                    rows: {
                        _: '%d lignes sélectionnées',
                        0: 'Aucune ligne sélectionnée',
                        1: '1 ligne sélectionnée'
                    }
                }
            },
            initComplete: function (settings, json) {
                // Add a small delay to ensure DOM is ready
                setTimeout(function() {
                    try {
                        // Setup footer filters
                        $('#tab tfoot th').each(function (i) {
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
                                        select.append('<option value="' + d + '">' + d + '</option>');
                                    }
                                });
                            }
                        });

                        // Hide loader and show table with smooth transition
                        $('#tableLoader').fadeOut(200, function() {
                            $(this).remove();
                            $('#tableContainer').fadeIn(300);
                            $('.dataTables_wrapper').addClass('loaded');
                        });
                    } catch (error) {
                        console.error('DataTables initialization error:', error);
                        // Fallback: show table even if filters fail
                        $('#tableLoader').fadeOut(200, function() {
                            $(this).remove();
                            $('#tableContainer').fadeIn(300);
                            $('.dataTables_wrapper').addClass('loaded');
                        });
                    }
                }, 100);
            }
        });

        // Setup footer search functionality with error handling
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
    });
</script>
</body>
</html>