<?php
require 'header.php' ?>
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
                Liste Consommation  - Véhicule : <?php echo $matricule_interne ?>
            </h3>
            <hr>

            <br>
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

            <button class="btn btn-default" onclick="window.print()"> <i class="fa fa-print"></i> Imprimmer</button> <br><br>

            <table class="table table-striped table-bordered" style="width:100%">
                <tr>
                    <td>Matricule</td>
                    <td>Numéro Parc</td>
                    <td>Type</td>
                    <td>Marque</td>
                    <td>Kilo Vidange</td>
                    <td>Type Carburant</td>
                    <td>Agence</td>
                </tr>
                <tr>


                    <td>
                        <?php
                        echo $matricule;
                        ?>
                    </td>

                    <td>
                        <?php
                        echo $matricule_interne;
                        ?>
                    </td>

                    <td>
                        <?php
                        echo $type;
                        ?>
                    </td>

                    <td>
                        <?php
                        echo $marque;
                        ?>
                    </td>

                    <td>
                        <?php
                        echo $kilometrage_vidange;
                        ?>
                    </td>

                    <td>
                        <?php
                        echo $carburant_type;
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
                </tr>
            </table>
            <br>

            <table class="table table-striped table-bordered" style="width:100%" id="tab-data">
                <thead>
                    <tr>
                        <th class="col-xs-2">Date</th>
                        <th class="col-xs-2">Type</th>
                        <th class="col-xs-2">Num</th>
                        <th class="col-xs-2">Index</th>
                        <th class="col-xs-2">Qte GO (L)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $tab_all = array();
                    $id_bus = $id;
                    $params = array(
                        'id_bus' => $id_bus
                    );
                    $select = $db->prepare("SELECT * FROM carburant WHERE id_bus=:id_bus");
                    $select->execute($params);
                    $i = 0;
                    foreach ($select as $item) {
                        $id_doc_carburant = $item["id_doc_carburant"];
                        $query = $db->query(
                                "SELECT * FROM  doc_carburant WHERE id_doc_carburant='$id_doc_carburant'"
                        );
                        foreach ($query as $it) {
                            $num_doc = $it["num_doc_carburant"];
                        }
                        $date = $item["date"];
                        $date = date_create($date);
                        $date = date_format($date, "d/m/Y");
                        $tab_all[$i]["date"] = $date;
                        $tab_all[$i]["qte_go"] = $item["qte_go"];
                        $tab_all[$i]["index_km"] = $item["index_km"];
                        $tab_all[$i]["type"] = $item["type"];
                        $tab_all[$i]["ref"] = $item["ref"];
                        $i++;
                    }
                    ?>

                    <?php
                    $x = 0;
                    foreach ($tab_all as $val) {
                        ?>
                        <tr>
                            <td>
                                <?php
                                echo $val["date"];
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $val["type"];
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $val["ref"];
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $val["index_km"];
                                ?>
                            </td>
                            <td>
                                <?php
                                $x = $x + $val["qte_go"];
                                echo $val["qte_go"];
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>
                            <strong>
                                <?php echo "Totale  : " . $x ?>
                            </strong>
                        </td>
                    </tr>
                </tbody>
            </table>
            <script>
                function sortTable() {
                    var table, rows, switching, i, x, y, shouldSwitch;
                    table = document.getElementById("tab-data");
                    switching = true;
                    /*Make a loop that will continue until
                     no switching has been done:*/
                    while (switching) {
                        //start by saying: no switching is done:
                        switching = false;
                        rows = table.rows;
                        /*Loop through all table rows (except the
                         first, which contains table headers):*/
                        for (i = 1; i < (rows.length - 1); i++) {
                            //start by saying there should be no switching:
                            shouldSwitch = false;
                            /*Get the two elements you want to compare,
                             one from current row and one from the next:*/
                            x = rows[i].getElementsByTagName("TD")[0];
                            y = rows[i + 1].getElementsByTagName("TD")[0];
                            //check if the two rows should switch place:
                            if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                                //if so, mark as a switch and break the loop:
                                shouldSwitch = true;
                                break;
                            }
                        }
                        if (shouldSwitch) {
                            /*If a switch has been marked, make the switch
                             and mark that a switch has been done:*/
                            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                            switching = true;
                        }
                    }
                }

            </script>
        </div>
    </div>
</div>
</body>
</html>