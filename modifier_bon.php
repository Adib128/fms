<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Modifier Carte Bon 
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
            
            <?php
            $id = $_GET["id"];

            $select = $db->query("SELECT * FROM bon WHERE id_bon = '$id'");
            foreach ($select as $item) {
                ?>
                <form class="form-horizontal" enctype="multipart/form-data" method="post" action="#">
                    
                    <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Numéro</label>
                    <div class="col-sm-6">
                        <input type="number" class="form-control" name="num" value="<?php echo $item["num"] ?>">
                    </div>
                    </div>
                    
                    
                    <div class="form-group">
                        <label for="firstname" class="col-sm-3 control-label">Véhicule</label>
                        <div class="col-sm-6">
                            <select class="form-control selectpicker" name="bus" data-live-search="true">
                                <?php
                                $liste_bus = $db->query("SELECT * FROM bus ORDER BY id_bus DESC");
                                foreach ($liste_bus as $row) {
                                    if ($row["id_bus"] == $item["id_bus"]) {
                                        echo '<option  value="' . $row["id_bus"] . '" selected>';
                                        echo $row["matricule_interne"] . " : " . $row["type"];
                                        echo '</option>';
                                    } else {
                                        echo '<option value="' . $row["id_bus"] . '">';
                                        echo $row["matricule_interne"] . " : " . $row["type"];
                                        echo '</option>';
                                        
                                    }
                                }
                                ?>
                            </select>

                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Chauffeur</label>
                        <div class="col-sm-6">
                            <select class="form-control selectpicker" name="chauffeur" data-live-search="true">
                                <?php
                                $liste_station = $db->query("SELECT * FROM chauffeur ORDER BY id_chauffeur DESC");
                                foreach ($liste_station as $row) {
                                    if ($row["id_chauffeur"] == $item["id_chauffeur"]) {
                                        echo '<option  value="' . $row["id_chauffeur"] . '" selected>';
                                        echo $row["matricule"] . " : " . $row["nom_prenom"];
                                        echo '</option>';
                                    } else {
                                        echo '<option value="' . $row["id_chauffeur"] . '">';
                                        echo $row["matricule"] . " : " . $row["nom_prenom"];
                                        echo '</option>';
                                    }
                                }
                                ?>
                            </select>

                        </div>
                    </div>

                   
                    <div class="form-group">
                        <label for="firstname" class="col-sm-3 control-label">Heure </label>
                        <div class="col-sm-6">
                            <input type="time" class="form-control" name="heure" value="<?php echo $item["heure"] ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="firstname" class="col-sm-3 control-label">Q té Go </label>
                        <div class="col-sm-6">
                            <input type="number" class="form-control" name="qte_go" step="0.01" value="<?php echo (float)$item["qte_go"] ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="firstname" class="col-sm-3 control-label">Index Km </label>
                        <div class="col-sm-6">
                            <input type="number" class="form-control" name="index_km" value="<?php echo $item["index_km"] ?>">
                        </div>
                    </div>


                    <br>
                    <div class="form-group">
                        <label class="col-sm-8 control-label"></label>
                        <input type="submit" value=" Modifier " class="btn  btn-default btn-primary">
                    </div>


                    <?php
                    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                        $bus = $_POST["bus"];
                        $chauffeur = $_POST["chauffeur"];
                        $heure = $_POST["heure"];
                        $qte_go = $_POST["qte_go"];
                        $index_km = $_POST["index_km"];
                        $num = $_POST["num"];

                        $sth = $db->prepare('UPDATE bon SET num=:num , id_bus=:id_bus ,id_chauffeur=:id_chauffeur , heure=:heure ,  qte_go=:qte_go , index_km=:index_km  WHERE id_bon=:id_bon');
                        $sth->bindParam(':id_bus', $bus);
                        $sth->bindParam(':id_chauffeur', $chauffeur);
                        $sth->bindParam(':heure', $heure);
                        $sth->bindParam(':num', $num);
                        $sth->bindParam(':qte_go', $qte_go);
                        $sth->bindParam(':index_km', $index_km);
                        $sth->bindParam(':id_bon', $id);
                        $sth->execute();

                        $_SESSION["message"] = "Modification avec succées";
                        echo "<script> window.location.replace('liste_bon.php?id=". $id ."')</script>";
                    }
                    ?>

                <?php } ?>

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