<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Enrégistrer Carte Bon

            </h3>
            <hr>
            <br>
            
             <?php
            if (isset($_SESSION["message"])) {
                ?>
                <br>
                <div class='alert alert-success alert-dismissable'>
                    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times; </button>
                    <?php
                    echo $_SESSION["message"];
                    session_destroy();
                    ?>
                </div>

            <?php } ?>
            

            <form class="form-horizontal" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
           
                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Numéro</label>
                    <div class="col-sm-6">
                        <input type="number" class="form-control" name="num">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Véhicule</label>
                    <div class="col-sm-6">
                        <select class="form-control selectpicker" name="bus"  data-live-search="true" title="Choisire le bus">
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
                </div>

                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Chauffeur</label>
                    <div class="col-sm-6">
                        <select class="form-control selectpicker" name="chauffeur"  data-live-search="true" title="Choisire le chauffeur">
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
                
                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Date </label>
                    <div class="col-sm-6">
                        <input type="date" class="form-control" name="date">
                    </div>
                </div>

                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Heure </label>
                    <div class="col-sm-6">
                        <input type="time" class="form-control" name="heure">
                    </div>
                </div>

                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Q té Go </label>
                    <div class="col-sm-6">
                        <input type="number" class="form-control" name="qte_go" step="0.01">
                    </div>
                </div>

                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Index Km </label>
                    <div class="col-sm-6">
                        <input type="number" class="form-control" name="index_km">
                    </div>
                </div>
                
                

                <br>
                <div class="form-group">
                    <label class="col-sm-7 control-label"></label>
                    <input type="submit" value=" Enrégistrer Carburant " class="btn  btn-default btn-primary">
                </div>
                
                <?php
                if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                  
                    $num = $_POST["num"];
                    $bus = $_POST["bus"];
                    $chauffeur = $_POST["chauffeur"];
                    $date = $_POST["date"];
                    $heure = $_POST["heure"];
                    $date_saisie = date('Y-m-d H:i:s');
                    $qte_go = $_POST["qte_go"];
                    $index_km = $_POST["index_km"];

                    $sth = $db->prepare('INSERT INTO bon VALUES(NULL,:num,:date,:heure,:qte_go,:index_km,:id_chauffeur,:id_bus)');
                    $sth->bindParam(':num', $num);
                    $sth->bindParam(':date', $date);
                    $sth->bindParam(':heure', $heure);
                    $sth->bindParam(':qte_go', $qte_go);
                    $sth->bindParam(':index_km', $index_km);
                    $sth->bindParam(':id_chauffeur', $chauffeur);
                    $sth->bindParam(':id_bus', $bus);
                    $sth->execute();
                    
                    $_SESSION["message"] = "Enrégistrement avec succées";
                    echo "<script> window.location.replace('liste_bon.php')</script>";
                }
                ?>

            </form>
        </div>


    </div>

</div>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/i18n/defaults-*.min.js"></script>
</body>
</html>