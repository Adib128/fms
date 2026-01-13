<?php
require 'header.php';
$huilesStmt = $db->query('SELECT *, (SELECT COUNT(*) FROM maintenance_operations WHERE oil_type_id = oil_types.id) as usage_count FROM oil_types ORDER BY id DESC');
$huiles = $huilesStmt ? $huilesStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<div id="page-wrapper">
    <div class="mx-auto flex max-w-9xl flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Liste des huiles</h1>
                <p class="text-sm text-slate-500">Gérez les huiles et leurs types.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= url('ajouter-huile') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Ajouter une huile
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])) : ?>
            <div class="alert-success flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9z" />
                </svg>
                <div class="flex-1 text-sm">
                    <?= $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="panel min-h-[800px]">
            <div class="panel-heading">
                <span>Huiles enregistrées</span>
                <span class="badge">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18" />
                        <path d="m7 16 3-3 2 2 5-5" />
                    </svg>
                    Liste à jour
                </span>
            </div>
            <div class="panel-body overflow-x-auto">
                <table class="table" id="tab">
                    <thead>
                        <tr>
                            <th>Libellé</th>
                            <th>Désignation</th>
                            <th>Type</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($huiles as $row) : ?>
                            <tr>
                                <td class="font-medium text-slate-900"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['designation'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['usageOil'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a
                                            href="<?= url('modifier-huile') ?>?id=<?= (int) $row['id']; ?>"
                                            class="btn-success py-1 px-2"
                                            title="Modifier"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="<?= url('supprimer-huile') ?>" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce type d\'huile ? Cette action est irréversible.');" style="display: inline;">
                                            <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                            <?php if ($row['usage_count'] > 0): ?>
                                                <button type="button" class="btn-danger py-1 px-2" disabled title="Cette huile est utilisée dans des opérations">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn-danger py-1 px-2" title="Supprimer">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
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
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
    $(function () {
        const titreRapport = 'Liste des huiles';

        $('#tab').DataTable({
            order: [[0, 'asc']],
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
            columnDefs: [
                { targets: -1, orderable: false, searchable: false }
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
            }
        });
    });
</script>
</body>
</html>
