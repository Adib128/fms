<?php
// Only include header.php if not already routed
if (!defined('ROUTED')) {
    require 'header.php';
} ?>

<div id="page-wrapper">
    <div class="mx-auto flex max-w-7xl flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Consulter document</h1>
                <p class="text-sm text-slate-500">Affichage détaillé du document carburant et des opérations associées.</p>
            </div>
            <?php 
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $document = null;
            $lignes = [];
            $qteTotal = 0;

            if ($id) {
                $docStmt = $db->prepare('SELECT d.*, s.lib AS station_lib
                    FROM doc_carburant d
                    LEFT JOIN station s ON s.id_station = d.id_station
                    WHERE d.id_doc_carburant = :id
                    LIMIT 1');
                $docStmt->execute([':id' => $id]);
                $document = $docStmt->fetch(PDO::FETCH_ASSOC);

                if ($document) {
                    $ligneStmt = $db->prepare('SELECT c.*, b.matricule_interne AS bus_matricule, ch.nom_prenom AS chauffeur_nom
                        FROM carburant c
                        LEFT JOIN bus b ON b.id_bus = c.id_bus
                        LEFT JOIN chauffeur ch ON ch.id_chauffeur = c.id_chauffeur
                        WHERE c.id_doc_carburant = :id
                        ORDER BY c.id_carburant ASC');
                    $ligneStmt->execute([':id' => $id]);
                    $lignes = $ligneStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($lignes as $ligne) {
                        $qteTotal += (float) $ligne['qte_go'];
                    }
                }
            }

            $isCarte = strtolower($document['type'] ?? '') === 'carte';
            $docDate = isset($document['date']) ? date('d/m/Y', strtotime($document['date'])) : '';
            $indexDebut = !empty($document['index_debut']) ? $document['index_debut'] : '';
            $indexFin = !empty($document['index_fin']) ? $document['index_fin'] : '';

            if ($document) : ?>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="/enregistrer-carburant-item?id=<?= $id ?>" class="btn-primary">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14" />
                            <path d="M5 12h14" />
                        </svg>
                        Enregistrer ligne
                    </a>
                    <button type="button" onclick="window.print()" class="btn-default">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 9V2h12v7" />
                            <path d="M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2" />
                            <path d="M6 14h12v8H6z" />
                        </svg>
                        Imprimer
                    </button>
                </div>
            <?php endif; ?>
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

        <?php if (!$document) : ?>
            <div class="panel">
                <div class="panel-body text-center text-sm text-slate-600">
                    Le document demandé est introuvable ou a été supprimé.
                </div>
            </div>
        <?php else : ?>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="card">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Numéro de document</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900"><?= htmlspecialchars($document['num_doc_carburant'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="card">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Type</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900"><?= htmlspecialchars($document['type'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="card">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Agence</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900"><?= htmlspecialchars($document['station_lib'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="card">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Date</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900"><?= $docDate ?: '—'; ?></p>
                </div>
                <div class="card">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Index début</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900"><?= htmlspecialchars($indexDebut, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="card">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Index fin</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900"><?= htmlspecialchars($indexFin, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="card">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Quantité totale (L)</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900"><?= formatQuantity($qteTotal); ?></p>
                </div>
            </div>

            <div class="panel">
                <div class="panel-heading">
                    <span>Lignes de consommation</span>
                    <span class="badge">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19h16" />
                            <path d="M4 11h16" />
                            <path d="M4 7h16" />
                        </svg>
                        <?= count($lignes); ?> lignes
                    </span>
                </div>
                <div class="panel-body overflow-x-auto">
                    <table class="table" id="tab">
                        <thead>
                            <tr>
                                <th>Véhicule</th>
                                <th>Type Carburant</th>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Qté GO (L)</th>
                                <th>Index (Km)</th>
                                <th>Chauffeur</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lignes as $ligne) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($ligne['bus_matricule'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($ligne['type'] ?? 'GO', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= $ligne['date'] ? date('d/m/Y', strtotime($ligne['date'])) : '—'; ?></td>
                                    <td><?= htmlspecialchars($ligne['heure'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= formatQuantity($ligne['qte_go']); ?></td>
                                    <td><?= htmlspecialchars($ligne['index_km'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($ligne['chauffeur_nom'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <a class="btn-success py-1 px-2" href="/modifier-carburant?id=<?= (int) $ligne['id_carburant']; ?>" title="Modifier">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z" />
                                                </svg>
                                            </a>
                                            <a class="btn-danger py-1 px-2" href="supprimer_carburant.php?id=<?= (int) $ligne['id_carburant']; ?>" onclick="return confirm('Voulez vous vraiment supprimer l\'enrégistrement de carburant ?');" title="Supprimer">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th class="text-right">Total</th>
                                <th><?= formatQuantity($qteTotal); ?></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endif; ?>
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
        const titreRapport = 'Consommation - Document <?= $document ? htmlspecialchars($document['num_doc_carburant'], ENT_QUOTES, 'UTF-8') : '' ?>';

        $('#tab').DataTable({
            lengthChange: false,
            pageLength: 30,
            order: [[2, 'desc']],
            columnDefs: [
                { targets: -1, orderable: false, searchable: false }
            ],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'print',
                    title: titreRapport,
                    text: 'Imprimer',
                    exportOptions: { columns: ':visible:not(:last-child)' }
                },
                {
                    extend: 'excelHtml5',
                    title: titreRapport,
                    text: 'Exporter Excel',
                    exportOptions: { columns: ':visible:not(:last-child)' }
                },
                {
                    extend: 'pdfHtml5',
                    title: titreRapport,
                    text: 'Exporter PDF',
                    exportOptions: { columns: ':visible:not(:last-child)' }
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
            }
        });
    });
</script>
</body>
</html>