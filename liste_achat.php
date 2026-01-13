<?php
require_once __DIR__ . '/app/security.php';
require_once __DIR__ . '/config.php';
require 'header.php';

// Get station stock data (from station.ind for fuel tank levels)
$stationsStmt = $db->query('SELECT id_station, lib, ind, qte FROM station WHERE ind > 0 ORDER BY id_station ASC');
$stationsStock = $stationsStmt ? $stationsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
$totalStock = array_reduce(
    $stationsStock,
    static function (float $carry, array $station): float {
        return $carry + (float) ($station['qte'] ?? 0);
    },
    0.0
);

?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Suivi stock carburant</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= url('ajouter-achat') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Nouvel approvisionnement
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

        <!-- Réservoir Carburant par Agence -->
        <div class="card">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Réservoir Carburant par Agence</h2>
                </div>
                <div class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-orange-50 via-amber-50 to-white px-4 py-2 text-sm font-semibold text-orange-700 shadow-sm">
                    <svg class="h-5 w-5 text-orange-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2c3 3.8 6 7.6 6 11a6 6 0 0 1-12 0c0-3.4 3-7.2 6-11Z" />
                    </svg>
                    <span><?= number_format($totalStock, 0, ',', ' '); ?> L</span>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-4">
                <?php if (!empty($stationsStock)): ?>
                    <?php foreach ($stationsStock as $station): ?>
                        <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-orange-50/30 to-amber-50/20 px-5 py-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-orange-300 hover:shadow-lg h-[80px]">
                            <!-- Fuel Level Indicator -->
                            <div class="absolute top-0 left-0 h-1 bg-gradient-to-r from-orange-400 to-amber-400 transition-all duration-500" 
                                 style="width: <?= min(100, max(10, (float) $station['qte'] / 1000 * 100)) ?>%;"></div>
                            
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-100 to-amber-100 text-orange-600 group-hover:from-orange-200 group-hover:to-amber-200 group-hover:text-orange-700 transition-colors">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2c3 3.8 6 7.6 6 11a6 6 0 0 1-12 0c0-3.4 3-7.2 6-11Z" />
                                        <path d="M12 12v-1" />
                                        <path d="M12 8h.01" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 group-hover:text-orange-600 transition-colors"><?= htmlspecialchars($station['lib'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="text-xs text-slate-500 mt-0.5">Index: <?= number_format((float) $station['ind'], 0, ',', ' '); ?></p>
                                    <div class="flex items-center gap-1 mt-1">
                                        <svg class="h-3 w-3 text-orange-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 2c3 3.8 6 7.6 6 11a6 6 0 0 1-12 0c0-3.4 3-7.2 6-11Z" />
                                        </svg>
                                        <span class="text-xs font-semibold text-orange-700"><?= number_format((float) $station['qte'], 0, ',', ' '); ?> L</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        <svg class="h-12 w-12 mx-auto mb-4 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2c3 3.8 6 7.6 6 11a6 6 0 0 1-12 0c0-3.4 3-7.2 6-11Z" />
                        </svg>
                        <p>Aucun réservoir carburant n'est enregistré pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <span>Historique des approvisionnements</span>
                <span class="badge">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18" />
                        <path d="m7 16 3-3 2 2 5-5" />
                    </svg>
                    Actualisé
                </span>
            </div>
            <div class="panel-body overflow-x-auto">
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
                </style>
                <table class="table compact-table" id="tab">
                    <tfoot>
                        <tr>
                            <th>ID</th>
                            <th>Station</th>
                            <th>Date</th>
                            <th>Quantité</th>
                            <th>Num Bon Liv</th>
                        </tr>
                    </tfoot>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="search_select">Station</th>
                            <th>Date</th>
                            <th>Quantité</th>
                            <th>Num Bon Liv</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $liste = $db->query("SELECT * FROM achat INNER JOIN station ON station.id_station = achat.id_station ORDER BY achat.id_achat DESC");
                        foreach ($liste as $row) {
                            ?>
                            <tr>
                                <td><?php echo $row["id_achat"]; ?></td>
                                <td><?php echo $row["lib"]; ?></td>
                                <td><?php echo $row["date"]; ?></td>
                                <td><?php echo $row["qte_achat"]; ?></td>
                                <td><?php echo $row["num"]; ?></td>
                                <td class="text-right">
                                    <div class="flex gap-1 justify-center">
                                        <a href="modifier_achat.php?id=<?php echo $row["id_achat"] ?>" class="btn-success py-1 px-2" title="Modifier achat">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <a href="supprimer_achat.php?id=<?php echo $row["id_achat"] ?>" onclick="return confirm('Voulez vous vraiment supprimer cet achat ?');" class="btn-danger py-1 px-2" title="Supprimer achat">
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
        $('#tab tfoot th').each(function (i) {
            $(this).html('<input type="text" placeholder="Filtrer" data-index="' + i + '" />');
        });

        var titreRapport = "Historique des achats";

        const table = $('#tab').DataTable({
            bSort: false,
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
                this.api().columns('.search_select').every(function () {
                    const column = this;
                    const select = $('<select data-skip-tom-select="true"><option value=""></option></select>')
                        .appendTo($(column.footer()).empty())
                        .on('change', function () {
                            const val = $.fn.dataTable.util.escapeRegex($(this).val());
                            column.search(val ? '^' + val + '$' : '', true, false).draw();
                        });

                    column.data().unique().sort().each(function (d) {
                        select.append('<option value="' + d + '">' + d + '</option>');
                    });
                });
            }
        });

        $(table.table().container()).on('keyup', 'tfoot input', function () {
            table
                .column($(this).data('index'))
                .search(this.value)
                .draw();
        });
    });
</script>
</body>
</html>