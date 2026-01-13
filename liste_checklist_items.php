<?php
require 'header.php';

try {
    $stmt = $db->query('SELECT * FROM checklist_items ORDER BY parti, code ASC');
    $checklistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $checklistItems = [];
    $error = $e->getMessage();
}
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Liste des Checklist Items</h1>
                <p class="text-sm text-slate-500">Gérez les items de checklist pour l'entretien des véhicules.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= url('ajouter-checklist-item') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Nouveau item
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

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger">
                <strong>Erreur :</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-heading">
                <span>État des items</span>
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
                            <th>Code</th>
                            <th>Partie</th>
                            <th>Libellé</th>
                            <th>Actions</th>
                        </tr>
                    </tfoot>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="search_select">Code</th>
                            <th class="search_select">Partie</th>
                            <th class="search_select">Libellé</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($checklistItems)) : ?>
                            <tr>
                                <td colspan="5" class="text-center text-slate-500 py-6">
                                    Aucun item de checklist enregistré pour le moment.
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($checklistItems as $item) : ?>
                                <tr>
                                    <td><?= (int) $item['id']; ?></td>
                                    <td><?= htmlspecialchars($item['code'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= htmlspecialchars($item['parti'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($item['label'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-right">
                                        <div class="flex gap-1 justify-center">
                                            <a href="/modifier-checklist-item?id=<?= (int) $item['id']; ?>" class="btn-warning py-1 px-2" title="Modifier l'item">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </a>
                                            <a href="/supprimer-checklist-item?id=<?= (int) $item['id']; ?>" 
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet item de checklist ?')"
                                               class="btn-danger py-1 px-2" 
                                               title="Supprimer l'item">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </a>
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

        const titreRapport = "Checklist Items";

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
                url: "//cdn.datatables.net/plug-ins/9fcb7596e1/i18n/French.json"
            },
            initComplete: function () {
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
