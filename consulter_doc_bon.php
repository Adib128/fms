<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                <?php
                $id = $_GET["id"];
                $select = $db->query("SELECT * FROM doc_bon WHERE id_doc_bon = '$id'");
                foreach ($select as $item) {
                    $num_doc_bon = $item["num_doc_bon"];
                    $date = $item["date"];
                    $id_station = $item["id_station"];
                }
                ?>
                Consulter document :  <?php echo $num_doc_bon ?>
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
                    <td>Numéro</td>
                    <td>Date</td>
                    <td>Agence</td>
                </tr>
                <tr>


                    <td>
                        <?php
                        echo $num_doc_bon;
                        ?>
                    </td>
                    
                    <td>
                        <?php
                        echo $date
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

            <table class="table table-striped table-bordered" style="width:100%" id="tab">
                
                <thead>
                    <tr>
                        <th class="col-xs-2 search_select">Véhicule</th>
                        <th class="col-xs-2">Num</th>
                        <th class="col-xs-2">Heure</th>
                        <th class="col-xs-2">Q té GO</th>
                        <th class="col-xs-2">Index Km</th>
                        <th class="col-xs-2 search_select">Chauffeur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    
                    $liste = $db->query(
                            "SELECT * FROM  bon INNER JOIN doc_bon ON  doc_bon.id_doc_bon = bon.id_doc_bon WHERE doc_bon.id_doc_bon = '$id' ORDER BY id_bon DESC"
                    );
                    
                    foreach ($liste as $row) {
                    	
                        $id_doc_carburant = $row["id_doc_bon"] ; 
                        
                        $query = $db->query(
                            "SELECT * FROM  doc_carburant WHERE id_doc_carburant='$id_doc_carburant'"
                        );
                        
                    		foreach ($query as $it){
                        	$num_doc = $it["num_doc_carburant"] ;    
                        	$date = $it["date"] ;  
                    		}
                    		
                        ?>
                        <tr>
                            <td>
                                <?php
                                $id_bus = $row["id_bus"];
                                $item = $db->query("SELECT * FROM bus WHERE id_bus='$id_bus'");
                                foreach ($item as $cr) {
                                    $matricule_interne = $cr["matricule_interne"];
                                }
                                echo $matricule_interne;
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $row["num"];
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $row["heure"];
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $row["qte_go"] . " L";
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $row["index_km"] . " Km";
                                ?>
                            </td>
                            <td>
                                <?php
                                $id_chauffeur = $row["id_chauffeur"];
                                $item = $db->query("SELECT * FROM chauffeur WHERE id_chauffeur='$id_chauffeur'");
                                foreach ($item as $cr) {
                                    $matricule_interne = $cr["nom_prenom"];
                                }
                                echo $matricule_interne;
                                ?>
                            </td> 
                        </tr>
                        <?php
                    }
                    ?>
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
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script>

    $(document).ready(function () {
        var ch = "<?php echo "Liste Consommation  - Véhicule : " . $matricule_interne ?>";
        var table = $('#tab').DataTable({
            bPaginate: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bAutoWidth: false,
            searching: false,
            bSort: false,
            dom: 'Bfrtip',
        });
    });
</script>
</body>
</html>