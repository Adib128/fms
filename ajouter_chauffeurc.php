<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Ajouter chauffeur 
            </h3>
            <hr>
            <br>

            <form class="form-horizontal" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Matricule</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="matricule" placeholder="">
                    </div>
                </div>

                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Nom & Prènom</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="nom_prenom" placeholder="">
                    </div>
                </div>

                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Agence</label>
                    <div class="col-sm-6">
                        <select class="form-control" name="station">
                            <?php
                            $liste_station = $db->query("SELECT * FROM station ORDER BY id_station ASC");
                            foreach ($liste_station as $row) {
                                echo '<option value="'. $row["id_station"] .'">';
                                    echo $row["lib"] ;
                                echo '</option>' ; 
                               } 
                               ?>
                            </select>

                        </div>
                    </div>
                    <br><br>
                    <div class="form-group">
                        <label class="col-sm-7 control-label"></label>
                        <input type="submit" value=" Ajouter Chauffeur " class="btn  btn-default btn-primary">
                    </div>
                    <?php
                    if ($_SERVER["REQUEST_METHOD"] == 'POST') {

                        $matricule = $_POST["matricule"];
                        $nom_prenom = $_POST["nom_prenom"];
                        $station = $_POST["station"];

                        $sth = $db->prepare('INSERT INTO chauffeur VALUES(NULL,:matricule,:nom_prenom,:station)');
                        $sth->bindParam(':matricule', $matricule);
                        $sth->bindParam(':nom_prenom', $nom_prenom);
                        $sth->bindParam(':station', $station);
                        $sth->execute();
                        
                        $_SESSION["message"] = "Succées d'ajout de chauffeur";
                        header('location:liste_chauffeur.php');
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
</body>
</html>