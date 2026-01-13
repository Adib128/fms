<?php
require 'header.php';

try {
    $stmt = $db->query('SELECT ft.*, COUNT(mo.id) as usage_count FROM filter_types ft LEFT JOIN maintenance_operations mo ON mo.filter_type_id = ft.id GROUP BY ft.id ORDER BY ft.name ASC');
    $filtres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $filtres = [];
    $error = $e->getMessage();
}
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Types de Filtres</h1>
                <p class="text-sm text-slate-500">Gérez les types de filtres disponibles pour les opérations d'entretien.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= url('ajouter-filtre') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Nouveau type de filtre
                </a>
            </div>
        </div>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger">
                <strong>Erreur :</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-heading">
                <span>Liste des types de filtres</span>
                <span class="badge">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18" />
                        <path d="m7 16 3-3 2 2 5-5" />
                    </svg>
                    <?= count($filtres); ?> types
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
                        min-width: 720px;
                    }
                    .table td:not(.text-right) {
                        white-space: nowrap;
                    }
                </style>
                <table class="table compact-table" id="tab">
                    <tfoot>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Usage</th>
                            <th>Actions</th>
                        </tr>
                    </tfoot>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="search_select">Nom</th>
                            <th class="search_select">Usage</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filtres)) : ?>
                            <tr>
                                <td colspan="4" class="text-center text-slate-500 py-6">
                                    Aucun type de filtre enregistré pour le moment.
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($filtres as $filtre) : ?>
                                <tr>
                                    <td><?= (int) $filtre['id']; ?></td>
                                    <td><?= htmlspecialchars($filtre['name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $filtre['usageFilter'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?= htmlspecialchars($filtre['usageFilter'] ?: 'Non défini', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex gap-1 justify-center">
                                            <a href="<?= url('modifier-filtre') ?>?id=<?= (int) $filtre['id']; ?>" class="btn-warning py-1 px-2" title="Modifier">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </a>
                                            <form method="POST" action="<?= url('supprimer-filtre') ?>" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce type de filtre ? Cette action est irréversible.');" style="display: inline;">
                                                <input type="hidden" name="id" value="<?= (int) $filtre['id']; ?>">
                                                <?php if ($filtre['usage_count'] > 0): ?>
                                                    <button type="button" class="btn-danger py-1 px-2" disabled title="Ce filtre est utilisé dans des opérations">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="3 6 5 6 21 6"></polyline>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        </svg>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn-danger py-1 px-2" title="Supprimer">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="3 6 5 6 21 6"></polyline>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        </svg>
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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

        const titreRapport = "Types de Filtres";

        const table = $('#tab').DataTable({
            bSort: false,
            dom: 'Bfrtip',
            columnDefs: [
                { targets: 0, visible: false, searchable: false }
            ],
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
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/French.json"
            },
            initComplete: function () {
                // Apply the search
                this.api().columns().every(function () {
                    var that = this;

                    $('input', this.footer()).on('keyup change clear', function () {
                        if (that.search() !== this.value) {
                            that.search(this.value).draw();
                        }
                    });
                });
            }
        });
    });
</script>
</body>
</html>
