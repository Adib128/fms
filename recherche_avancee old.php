<?php
require 'header.php' ?>
<style>
    input[type="date"]:before {
        content: attr(placeholder) !important;
        color: #aaa;
        margin-right: 0.5em;
    }
    input[type="date"]:focus:before,
    input[type="date"]:valid:before {
        content: "";
    }
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
                Historique ravitaillement
            </h3>
            <hr>
            <br>
            <p>Critére de recherche : </p>
            <form method="POST" action="#" role="form" id="search_form">

                <div class="row">
                    <div class="col-lg-6">
                        <input type="date" name="date_debut" id="date_debut"  class="form-control" placeholder="Date debut : ">
                    </div>
                    <div class="col-lg-6">
                        <input type="date" name="date_fin" id="date_fin"  class="form-control" placeholder="Date fin : ">
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-lg-6">
                        <select class="form-control selectpicker" name="bus"  data-live-search="true" title="Choisire le bus" id="bus">
                            <?php
                            $liste_station = $db->query("SELECT * FROM bus ORDER BY id_bus ASC");
                            foreach ($liste_station as $row) {
                                echo '<option value="' . $row["id_bus"] . '">';
                                echo $row["matricule_interne"] . " : " . $row["type"];
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-lg-6">
                        <select class="form-control selectpicker" name="chauffeur"  data-live-search="true" title="Choisire le chauffeur" id="chauffeur">
                            <?php
                            $liste_station = $db->query("SELECT * FROM chauffeur ORDER BY id_chauffeur ASC");
                            foreach ($liste_station as $row) {
                                echo '<option value="' . $row["id_chauffeur"] . '">';
                                echo $row["matricule"] . " : " . $row["nom_prenom"];
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>

                </div>
                <br>
                <div class="row">
                    <button type="button" class="btn btn-primary btn-block btn-sm" id="search">Chercher</button>
                </div>

                <br><br>

                <?php
                if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                    ?> 
                    <table class="table table-striped table-bordered" style="width:100%" id="tab">
                        <tfoot>
                            <tr>
                                <th>Date </th>
                                <th>Type</th>
                                <th>Num Bon</th>
                                <th>Véhicule</th>
                                <th>Chauffeur</th>
                                <th>Q té GO (l)</th>
                                <th>Index Km</th>
                                <th>Moy Co</th>
                            </tr>
                        </tfoot>
                        <thead>
                            <tr>
                                <th>Date </th>
                                <th class="search_select">Type</th>
                                <th>Num Bon</th>
                                <th>Véhicule</th>
                                <th>Chauffeur</th>
                                <th>Q té GO (l)</th>
                                <th>Index Km</th>
                                <th>Moy Co</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $bus = $_POST["bus"];
                            $chauffeur = $_POST["chauffeur"];

                            $date_debut = strtotime($_POST["date_debut"]);
                            $date_debut = date('Y-m-d', $date_debut);

                            $date_fin = strtotime($_POST["date_fin"]);
                            $date_fin = date('Y-m-d', $date_fin);

                            $where = " WHERE 1 = 1 ";

                            if ($bus != "") {
                                $where = $where . " AND id_bus = " . $bus;
                            }

                            if ($chauffeur != "") {
                                $where = $where . " AND id_chauffeur = " . $chauffeur . " ";
                            }

                            if ($date_debut != "1970-01-01") {
                                $where = $where . " AND date >= " . "'" . $date_debut . "' ";
                            }

                            if ($date_fin != "1970-01-01") {
                                $where = $where . " AND date <= " . "'" . $date_fin . "' ";
                            }

                            $liste_carburant = $db->query(
                                    "SELECT * FROM  carburant " . $where . " ORDER BY id_carburant ASC"
                            );
                            $e_carburant = $liste_carburant->fetch(PDO::FETCH_NUM);
                

                            $tab_all = array();
                            if ($e_carburant == false) {
                                ?>
                                <tr>
                                    <td colspan="8">
                            <center>
                                <strong>Aucune résultat pour ce recherche</strong>
                            </center>
                            </td>
                            </tr>
                            <?php
                        }
                        $last_index = 0;
                        $tab_carburant = array();
                        $tab_bon = array();
                        if ($e_carburant == true) {
                            $liste_carburant = $db->query(
                                    "SELECT * FROM  carburant " . $where . " ORDER BY date ASC"
                            );
                            $i = 0;
                            foreach ($liste_carburant as $item) {
                                $id_doc_carburant = $item["id_doc_carburant"];
                                $query = $db->query(
                                        "SELECT * FROM  doc_carburant WHERE id_doc_carburant='$id_doc_carburant'"
                                );
                                foreach ($query as $it) {
                                    $num_doc = $it["num_doc_carburant"];
                                    $date = $it["date"];
                                }
                                $tab_carburant[] = $item["index_km"];

                                $date = $item["date"];
                                $date = date_create($date);
                                $date = date_format($date, "d/m/Y");
                                $tab_all[$i]["id_bus"] = $item["id_bus"];
                                $tab_all[$i]["id_chauffeur"] = $item["id_chauffeur"];
                                $tab_all[$i]["num"] = $item["ref"];
                                $tab_all[$i]["date"] = $date;
                                $tab_all[$i]["qte_go"] = $item["qte_go"];
                                $tab_all[$i]["index_km"] = $item["index_km"];
                                $tab_all[$i]["type"] = $item["type"];
                                $tab_all[$i]["id_carburant"] = $item["id_carburant"];
                                $i++;
                            }

                            $qte_total = 0;
                            $km_total = 0;
                            $last_index = 0;
                            $last_qte = 0;
                            $tab_indexes = array();
                            $i = 0 ; 
                            $first_qte = 0 ; 
                            foreach ($tab_all as $sc) {
                                if($i == 0){
                                    $first_qte = $sc["qte_go"];
                                }
                                ?>
                                <tr class="Entries">
                                    <td>
                                        <a href="modifier_carburant.php?id=<?php echo $sc["id_carburant"] ?>" target="_blank"><?php echo $sc["date"]  ?> </a>
                                    </td>
                                    <td><?php echo $sc["type"] ?></td>
                                    <td>
                                        <?php 
                                        if($sc["num"] != 0){
                                            echo $sc["num"] ; 
                                        } 
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $id_bus = $sc["id_bus"];
                                        $item = $db->query("SELECT * FROM bus WHERE id_bus='$id_bus'");
                                        foreach ($item as $cr) {
                                            $matricule_interne = $cr["matricule_interne"];
                                        }
                                        echo $matricule_interne;
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if($sc["id_chauffeur"] != "") {
                                            $id_chauffeur = $sc["id_chauffeur"];
                                            $item = $db->query("SELECT * FROM chauffeur WHERE id_chauffeur='$id_chauffeur'");
                                            foreach ($item as $cr) {
                                                $matricule_interne = $cr["nom_prenom"];
                                            }
                                            echo $matricule_interne;
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        $qte = $sc["qte_go"];
                                        $qte_total = $qte_total + $qte;
                                        echo $sc["qte_go"];
                                        ?>
                                    </td>
                                    <td><?php echo $sc["index_km"] ?></td>
                                    <?php $tab_indexes[] = $sc["index_km"] ?>
                                   <td>
                                    <?php
                                   
                                    if($i > 0){
                                    if ($last_index != 0) {
                                        $km = $sc["index_km"] - $last_index;
                                        if ($km > 0) {
                                            $moy = $qte / $km;
                                            $moy = $moy * 100;
                                            $moy = round($moy, 2);
                                        } else {
                                            $moy = 0;
                                        }

                                        echo $moy;
                                    }
                                }
                                    ?>
                                    </td> 

                                </tr>
                                <?php
                                $last_index = $sc["index_km"];
                                $last_qte = $sc["qte_go"];
                                $i++;
                            }
                            
                            ?>
                            <tr class="Entries" style="font-weight: bold">
                                <td><b>Total</b></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>

                                <td><?php echo $qte_total; ?></td>
                                <td>
                                    <?php

                                    function calculDiff($tab) {
                                        $diff = 0;
                                        $ln = count($tab) - 1;
                                        $diff = $tab[$ln] - $tab[0];
                                        return $diff;
                                    }

                                    $tab_diff = array_merge($tab_carburant, $tab_bon);
                                    sort($tab_diff);
                                    if (count($tab_diff) != 0) {
                                        $total_km = calculDiff($tab_diff);
                                    } else {
                                        $total_km = 0;
                                    }
                                    echo $total_km;
                                    ?> 
                                </td>
                               <td>
                                <?php
                                if ($total_km != 0) {
                                    $qte_total = $qte_total - $first_qte ; 
                                    $moy_total = ($qte_total / $total_km) * 100;
                                    $moy_total = round($moy_total, 2);
                                    echo $moy_total;
                                }
                                ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <?php
                    }
                }
                ?>
            </form>
            <br>
        </div>
    </div>
</div>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/i18n/defaults-*.min.js"></script>
<script>
    $(document).ready(function () {

        /*************************************************************************************************/
        $("#search").click(function () {
            var bus = $("#bus").val();
            var chauffeur = $("#chauffeur").val();
            var num_doc = $("#num_doc").val();
            var date_debut = $("#date_debut").val();
            var date_fin = $("#date_fin").val();
            if (bus == "" && chauffeur == "" && date_debut == "" && date_fin == "") {
                alert("Vueillez verifier les critére de votre recherche");
            } else {
                $("#search_form").submit();
            }
        });

        // ************************************************************************************************/
        $('tr.Entries').each(function () {
            var $this = $(this),
                    t = this.cells[1].textContent.split('-');
            $this.data('_ts', new Date(t[2], t[1] - 1, t[0]).getTime());
        }).sort(function (a, b) {
            return $(a).data('_ts') > $(b).data('_ts');
        }).appendTo('tbody');

        // ************************************************************************************************/

        // Setup - add a text input to each footer cell
        $('#tab tfoot th').each(function (i) {
            var title = $('#example thead th').eq($(this).index()).text();
            $(this).html('<input type="text" placeholder="" data-index="' + i + '" />');
        });

        var ch = "Etat de suivi de consommation";
        var table = $('#tab').DataTable({
            "bSort": false,
            dom: 'Bfrtip',
            bPaginate: false,
            bLengthChange: false,
            buttons: [
                {
                    extend: 'print',
                    title: ch,
                    text: 'Imprimmer',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                },
                {
                    extend: 'excelHtml5',
                    title: ch,
                    text: 'Exporter Excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    title: ch,
                    text: 'Exporter PDF',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                }
            ],

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
            }
        });

        // Filter event handler
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