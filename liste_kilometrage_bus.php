<?php
require 'header.php' ?>
<style type="text/css">
@media print { .btn-print { display: none; } }
</style>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                <?php
                $id = $_GET["id"];
                $select = $db->query("SELECT * FROM bus WHERE id_bus = '$id'");
                foreach ($select as $item) {
                    $matricule_interne = $item["matricule_interne"];
                    $matricule = $item["matricule"];
                    $marque = $item["marque"];
                    $type = $item["type"];
                    $kilometrage_vidange = $item["vidange"];
                    $id_station = $item["id_station"];
                    $carburant_type = $item["carburant_type"];
                }
                ?>
                Suivi kilomètrage  - Véhicule : <?php echo $matricule_interne ?>
            </h3>
            <hr>
            <br>
            <button class="btn btn-default btn-print" onclick="window.print()">
                <i class="fa fa-print"></i>
               Imprimmer
            </button>
            <br><br>
            <?php
            if (isset($_SESSION["message"])) {
                ?>
                <div class='alert alert-success alert-dismissable'>
                    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times; </button>
                    <?php
                    echo $_SESSION["message"];
                    session_destroy();
                    ?>
                </div>

            <?php } ?>

            <table class="table table-striped table-bordered" style="width:100%">
                <tr>
                    <td>Matricule</td>
                    <td>Numéro Parc</td>
                    <td>Type</td>
                    <td>Marque</td>
                    <td>Fré. Vidange</td>
                    <td>Agence</td>
                    <td>Type Carburant</td>
                    <td>Totale kilométrage</td>
                </tr>
                <tr>
                  
                <td>
                  <?php 
                   echo $matricule_interne ; 
                  ?>
                </td>
                
                   <td>
                  <?php 
                   echo $matricule ; 
                  ?>
                </td>
                
                <td>
                  <?php 
                   echo $type ; 
                  ?>
                </td>
                
                <td>
                  <?php 
                   echo $marque ; 
                  ?>
                </td>
                
                <td>
                  <?php 
                   echo $kilometrage_vidange ; 
                  ?>
                </td>
                
                <td>
                     <?php
                                $id_station = $id_station;
                                $item = $db->query("SELECT * FROM station WHERE id_station='$id_station'");
                                foreach ($item as $cr) {
                                    $lib = $cr["lib"];
                                }
                                echo $lib;
                                ?>
                </td>

                <td>
                    <?php echo $carburant_type; ?>
                </td>
               
                
                
                    
                    <td>
                    <?php
                    $id_bus = $id;
                    $params = array(
                        'id_bus' => $id_bus
                    );
                    $select = $db->prepare("SELECT * FROM total_kilometrage WHERE id_bus=:id_bus");
                    $select->execute($params);
                    $e = $select->fetch(PDO::FETCH_NUM);
                    $kilometrage = 0;
                    if ($e == true) {
                        $liste_kilometrage = $db->query("SELECT * FROM total_kilometrage WHERE id_bus='$id_bus' ");
                        foreach ($liste_kilometrage as $item) {
                            $kilometrage = $item["kilometrage"];
                        }
                    }
                    echo $kilometrage." Km";
                    ?>
                </td>
                </tr>
            </table>
            <br>

            <table class="table table-striped table-bordered" style="width:100%" id="tab">
                <thead>
                    <tr>
                        <th class="col-xs-2">Date </th>
                        <th class="col-xs-2">Kilomètrage (Km)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $liste = $db->query(
                            "SELECT * FROM kilometrage "
                            . "INNER JOIN bus ON  kilometrage.id_bus=bus.id_bus WHERE kilometrage.id_bus='$id' ORDER BY date_kilometrage ASC"
                    );
                    $total_km = 0 ; 
                    foreach ($liste as $row) {
                        ?>
                        <tr>
                            <td>

                                <?php
                                $date = date_create($row["date_kilometrage"]);
                                echo date_format($date, "d/m/Y");
                                ?>
                            </td>
                            <td>
                                <?php 
                                echo $row["kilometrage"] ;
                                $total_km = $total_km + $row["kilometrage"] ;
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                        <tr style="font-weight:bold">
                            <td>Total</td>
                            <td>
                                <?php 
                                echo $total_km ;
                                ?>
                            </td>
                        </tr>
                </tbody>

            </table>
            <br><br>
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
        var ch = "Suivi kilomètrage - Véhicule : <?php echo $matricule_interne ?>";
        $('#tab').DataTable({
            "bSort": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'print',
                    title: ch,
                    text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Imprimer',
                    className: 'btn btn-indigo btn-sm',
                    exportOptions: { columns: ':visible:not(.text-right)' }
                },
                {
                    extend: 'excelHtml5',
                    title: ch,
                    text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg> Excel',
                    className: 'btn btn-emerald btn-sm',
                    exportOptions: { columns: ':visible:not(.text-right)' }
                },
                {
                    extend: 'pdfHtml5',
                    title: ch,
                    text: 'Exporter PDF',
                    exportOptions: { columns: ':visible:not(.text-right)' }
                }
            ],
            "language": {
                "sProcessing": "Traitement en cours...",
                "sSearch": "Rechercher&nbsp;:",
                "sLengthMenu": "Afficher _MENU_ &eacute;l&eacute;ments",
                "sInfo": "Affichage de l'&eacute;l&eacute;ment _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
                "sInfoEmpty": "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ment",
                "sInfoFiltered": "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                "sInfoPostFix": "",
                "sLoadingRecords": "Chargement en cours...",
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
            }
        });
    });
</script>
</body>
</html>