<?php
require 'header.php';
?>
<div id="page-wrapper">
    <div class="mx-auto flex max-w-9xl flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Suivi Vidange</h1>
                <p class="text-sm text-slate-500">Gérez les vidanges et leurs opérations.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= url('ajouter-vidange') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Enregistrer Vidange
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
                <span>Vidanges enregistrées</span>
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
                        <th class="col-xs-2">Date de vidange</th>
                        <th class="col-xs-2">Agence</th>
                        <th class="col-xs-2">Véhicule</th>
                        <th class="col-xs-1">Indexe</th>
                        <th class="col-xs-2">Réf. Document</th>
                        <th class="col-xs-2">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
        </div>
    </div>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
$(document).ready(function() {
    console.log('Document ready, jQuery version:', $.fn.jquery);
    console.log('DataTable plugin loaded:', typeof $.fn.DataTable !== 'undefined');
    console.log('Table element exists:', $('#tab').length);
    
    // Simple DataTable initialization without buttons first
    var table = $('#tab').DataTable({
        processing: true,
        serverSide: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'print',
                title: 'Liste des vidanges',
                text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Imprimer',
                className: 'btn btn-indigo btn-sm',
                exportOptions: { columns: ':visible:not(.text-right)' }
            },
            {
                extend: 'excelHtml5',
                title: 'Liste des vidanges',
                text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg> Excel',
                className: 'btn btn-emerald btn-sm',
                exportOptions: { columns: ':visible:not(.text-right)' }
            },
            {
                extend: 'pdfHtml5',
                title: 'Liste des vidanges',
                text: 'Exporter PDF',
                exportOptions: { columns: ':visible:not(.text-right)' }
            }
        ],
        ajax: {
            url: 'liste_vidange_json.php',
            type: 'POST',
            data: function(d) {
                console.log('Sending data:', d);
                return d;
            },
            error: function(xhr, error, code) {
                console.error('DataTable AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error,
                    code: code
                });
                $('#tab').before('<div class="alert alert-danger">Error loading data: ' + xhr.statusText + '</div>');
            }
        },
        columns: [
            { data: 'date' },
            { data: 'station' },
            { data: 'bus' },
            { data: 'indexe' },
            { data: 'ref_doc' },
            {
                data: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        language: {
            sProcessing: "Traitement en cours...",
            sSearch: "Rechercher&nbsp;:",
            sLengthMenu: "Afficher _MENU_ &eacute;l&eacute;ments",
            sInfo: "Affichage de l'&eacute;l&eacute;ment _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
            sInfoEmpty: "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ment",
            sInfoFiltered: "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
            sInfoPostFix: "",
            sInfoThousands: ",",
            sLoadingRecords: "Chargement...",
            oPaginate: {
                sFirst: "Premier",
                sLast: "Dernier",
                sNext: "Suivant",
                sPrevious: "Pr&eacute;c&eacute;dent"
            },
            oAria: {
                sSortAscending: ": activer pour trier la colonne par ordre croissant",
                sSortDescending: ": activer pour trier la colonne par ordre d&eacute;croissant"
            }
        },
        initComplete: function(settings, json) {
            console.log('DataTable initialized successfully:', json);
            console.log('Number of rows loaded:', json ? json.data.length : 0);
        }
    });
});

// Delete confirmation function - Global scope for onclick access
function confirmDelete(id) {
    if (confirm('Voulez-vous vraiment supprimer cette vidange ?')) {
        window.location.href = '/supprimer-vidange?id=' + id;
    }
    return false;
}
</script>