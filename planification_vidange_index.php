<?php
require 'header.php';
?>
<div id="page-wrapper">
    <div class="mx-auto flex max-w-9xl flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Planification Vidange Index</h1>
                <p class="text-sm text-slate-500">Vue index standard des planifications de vidanges.</p>
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
                <span>Planification des vidanges</span>
                <span class="badge">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18" />
                        <path d="m7 16 3-3 2 2 5-5" />
                    </svg>
                    Vue Index
                </span>
            </div>
            <div class="panel-body overflow-x-auto">
                <table class="table" id="tab">
                    <thead>
                        <tr>
                            <th>Véhicule</th>
                            <th>Agence</th>
                            <th>Freq vidange</th>
                            <th>Dernier index</th>
                            <th>Index der vidange</th>
                            <th>Kilomètrage Reste</th>
                            <th>Kilomètrage Technique</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $liste_bus = $db->query(
                                " SELECT * FROM bus ORDER BY id_bus DESC "
                        );
                        
                        $index_dernier_vidange = 0;
                        $kilometrage_vidange = 0;
                        $dernier_index = 0;
                        foreach ($liste_bus as $item) {

                            $id_bus = $item["id_bus"];
                            $matricule_interne = $item["matricule_interne"];
                            $freq_vidange = $item["vidange"];

                            //selection vidange
                            $liste = $db->query(
                                    "SELECT * FROM  vidange WHERE id_bus = '$id_bus' ORDER BY date_vidange DESC LIMIT 1"
                            );
                            $date_dernier_vidange = 0;
                            foreach ($liste as $row) {
                                $index_dernier_vidange = $row["indexe"];
                                $date_dernier_vidange = $row["date_vidange"];
                            }
                        
                            $liste = $db->query(
                                    "SELECT * FROM  carburant WHERE id_bus = '$id_bus' ORDER BY id_carburant DESC LIMIT 1"
                            );
                            foreach ($liste as $row) {
                                $dernier_index = $row["index_km"];
                            }
                        
                            //selection freq
                            $liste = $db->query(
                                    "SELECT * FROM  bus WHERE id_bus = '$id_bus'"
                            );
                            foreach ($liste as $row) {
                                $freq_vidange = $row["vidange"];
                                $matricule_interne = $row["matricule_interne"];
                                $conso = $row["conso"];
                            }

                            //calcul reste index
                            $reste_index = (int)$dernier_index - (int)$index_dernier_vidange;

                            //calcul difference vidange
                            $diff_index = (int) $freq_vidange - (int) $reste_index;

                            if($date_dernier_vidange != 0){
                                //selection Qte
                                $liste = $db->query(
                                        "SELECT SUM(qte_go) AS sum_qte  FROM  carburant WHERE id_bus = '$id_bus' AND carburant.date > '$date_dernier_vidange'"
                                );
                                foreach ($liste as $row) {
                                    $sum_qte = $row["sum_qte"];
                                }
                        
                                if($sum_qte == 0){
                                    $km_tech = 0 ; 
                                }else{
                                    $km_tech = (int)$sum_qte * 100;
                                    $km_tech = round($km_tech / $conso);
                                }
                            }else{
                                $km_tech = "NoVidange" ; 
                            }
                            ?>
                            <tr>
                                <td><?php echo $matricule_interne; ?></td>
                                <td>
                                    <?php
                                    $id_station = $item["id_station"];
                                    $stas = $db->query("SELECT * FROM station WHERE id_station='$id_station'");
                                    foreach ($stas as $cr) {
                                        $lib = $cr["lib"];
                                    }
                                    echo $lib;
                                    ?>
                                </td>
                                <td><?php echo $freq_vidange; ?></td>
                                <td><?php echo $dernier_index; ?></td>
                                <td><?php echo $index_dernier_vidange; ?></td>
                                <td><?php echo $diff_index; ?></td>
                                <td>
                                    <?php echo $km_tech; ?>
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
    var table = $('#tab').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'print',
                text: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h14l-1 12H5z" /><path d="M7 21h10" /></svg> Imprimer',
                className: 'btn btn-default'
            },
            {
                extend: 'excel',
                text: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18" /><path d="m7 16 3-3 2 2 5-5" /></svg> Excel',
                className: 'btn btn-default'
            },
            {
                extend: 'pdf',
                text: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v4H4z" /><path d="M4 12h16v2H4z" /><path d="M4 18h16v2H4z" /></svg> PDF',
                className: 'btn btn-default'
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
        order: [[4, "asc"]] // Sort by "Index der vidange" column (index 4)
    });
});
</script>
