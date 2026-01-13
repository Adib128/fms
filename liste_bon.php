<?php
require 'header.php' ?>
<style>
    tfoot {
        display: table-header-group;
    }
    tfoot input {
        width: 100%;
        padding: 3px;
        box-sizing: border-box;
    }

</style>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Liste Carte Bon
            </h3>
            <hr>
            <br>
            <div class="flex gap-3 mb-6">
                <a href="enregistrer_bon_item.php" class="btn btn-default">
                    <i class="fa fa-plus"></i>
                    Enrégistrer
                </a>
                <a href="liste_bon.php" class="btn btn-default" id="refresh">
                    <i class="fa fa-refresh"></i>
                    Actualiser
                </a>
            </div> 
            <?php
            if (isset($_SESSION["message"])) {
                ?>
                <br>
                <div class="alert alert-success">
                    <button type="button" class="float-right text-gray-400 hover:text-gray-600" onclick="this.parentElement.style.display='none'">&times;</button>
                    <?php
                    echo $_SESSION["message"];
                    session_destroy();
                    ?>
                </div>

            <?php } ?>

            <br>
            <table class="table" style="width:100%" id="tab">
                <thead>
                    <tr>
                        <th class="w-48">Numéro Bon </th>
                        <th class="w-48 search_select">Véhicule</th>
                        <th class="w-48">Date </th>
                        <th class="w-48">Heure </th>
                        <th class="w-36">Q té GO</th>
                        <th class="w-36 search_select">Type</th>
                        <th class="w-48">Index Km</th>
                        <th class="w-48"></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th class="w-48">Numéro Bon </th>
                        <th class="w-48 search_select">Véhicule</th>
                        <th class="w-48">Date </th>
                        <th class="w-48">Heure </th>
                        <th class="w-36">Q té GO</th>
                        <th class="w-36 search_select">Type</th>
                        <th class="w-48">Index Km</th>
                        <th class="w-48"></th>
                    </tr>
                </tfoot>
            </table>
            <br>
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

    $(document).ready(function () {
        // Setup - add a text input to each footer cell
        $('#tab tfoot th').each(function (i) {
            var title = $('#tab thead th').eq($(this).index()).text();
            $(this).html('<input type="text" placeholder="" data-index="' + i + '" />');
        });

        var table = $('#tab').DataTable({
            /*"ordering": false,*/
            ajax: "liste_bon_json.php",
            columns: [
                {data: "num"},
                {data: "bus"},
                {data: "date"},
                {data: "heure"},
                {data: "qte_go"},
                {data: "type"},
                {data: "index_km"},
                {
                    mRender: function (data, type, row) {
                        return '<div class="flex items-center gap-2">' +
                            '<a href="modifier_bon.php?id=' + row.id + '" class="btn-success py-1 px-2" title="Modifier">' +
                            '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                            '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>' +
                            '<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>' +
                            '</svg>' +
                            '</a>' +
                            '<a href="supprimer_bon.php?id=' + row.id + '" onclick="return confirm(\'Voulez vous vraiment supprimer le bon ?\');" class="btn-danger py-1 px-2" title="Supprimer">' +
                            '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                            '<path d="M3 6h18"></path>' +
                            '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>' +
                            '</svg>' +
                            '</a>' +
                            '</div>';
                    }
                }
            ],
            "bSort": false,
            "language": {
                "sProcessing": "Traitement en cours...",
                "sSearch": "Rechercher&nbsp;:",
                "sLengthMenu": "Afficher _MENU_ &eacute;l&eacute;ments",
                "sInfo": "Affichage de l'&eacute;l&eacute;ment _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
                "sInfoEmpty": "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ment",
                "sInfoFiltered": "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                "sInfoPostFix": "",
                "sLoadingRecords": '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span> ',
                "sZeroRecords": "Aucun &eacute;l&eacute;ment &agrave; afficher",
                "sEmptyTable": "Aucune donn&eacute;e disponible dans le tableau",
                "oPaginate": {
                    "sFirst": "Premier",
                    "sPrevious": "Pr&eacute;c&eacute;dent",
                    "sNext": "Suivant",
                    "sLast": "Dernier"
                },
                "oAria": {
                    "sSortAscending": ": activer pour trier la colonne par ordre croissant",
                    "sSortDescending": ": activer pour trier la colonne par ordre d&eacute;croissant"
                },
                "select": {
                    "rows": {
                        _: "%d lignes séléctionnées",
                        0: "Aucune ligne séléctionnée",
                        1: "1 ligne séléctionnée"
                    }

                }
            },
            initComplete: function () {
                this.api().columns('.search_select').every(function () {
                    var column = this;
                    var select = $('<select><option value=""></option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                        $(this).val()
                                        );

                                column
                                        .search(val ? '^' + val + '$' : '', true, false)
                                        .draw();
                            });

                    column.data().unique().sort().each(function (d, j) {
                        select.append('<option value="' + d + '">' + d + '</option>')
                    });
                });
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'print',
                    title: '',
                    text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Imprimer',
                    className: 'btn btn-indigo btn-sm',
                    exportOptions: { columns: ':visible:not(.text-right)' }
                },
                {
                    extend: 'excelHtml5',
                    title: '',
                    text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg> Excel',
                    className: 'btn btn-emerald btn-sm',
                    exportOptions: { columns: ':visible:not(.text-right)' }
                },
                {
                    extend: 'pdfHtml5',
                    title: '',
                    text: 'Exporter PDF',
                    exportOptions: { columns: ':visible:not(.text-right)' }
                }
            ]
        });

        // Filter event handler
        $(table.table().container()).on('keyup', 'tfoot input', function () {
            table
                    .column($(this).data('index'))
                    .search(this.value)
                    .draw();
        });
    });

    $("#refresh").click(function () {
        location.reload();
    });

    $("#datepicker_to").datepicker({
        showOn: "button",
        buttonImage: "images/calendar.gif",
        buttonImageOnly: false,
        "onSelect": function (date) {
            maxDateFilter = new Date(date).getTime();
            oTable.fnDraw();
        }
    });

    $("#datepicker_from").datepicker({
        showOn: "button",
        buttonImage: "images/calendar.gif",
        buttonImageOnly: false,
        "onSelect": function (date) {
            minDateFilter = new Date(date).getTime();
            oTable.fnDraw();
        }
    });
</script>
</body>
</html>