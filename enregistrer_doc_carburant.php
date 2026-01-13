<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Enrégistrer document carburant
            </h3>
            <hr>
            <br>
            
             <?php
            if (isset($_SESSION["message"])) {
                ?>
                <div class="alert alert-success">
                    <button type="button" class="float-right text-gray-400 hover:text-gray-600" onclick="this.parentElement.style.display='none'">&times;</button>
                    <?php
                    echo $_SESSION["message"];
                    session_destroy();
                    ?>
                </div>
            <?php } ?>
            
            <form class="space-y-4" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                    <label for="num_doc" class="text-sm font-medium text-gray-700 text-right"> Numéro Doc </label>
                    <div class="md:col-span-3">
                        <input type="number" class="form-control" name="num_doc">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                    <label for="date_doc" class="text-sm font-medium text-gray-700 text-right">Date </label>
                    <div class="md:col-span-3">
                        <input type="date" class="form-control" name="date_doc" value="">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                    <label for="index_debut" class="text-sm font-medium text-gray-700 text-right">Index Début </label>
                    <div class="md:col-span-3">
                        <input type="number" class="form-control" name="index_debut">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                    <label for="index_fin" class="text-sm font-medium text-gray-700 text-right">Index Fin </label>
                    <div class="md:col-span-3">
                        <input type="number" class="form-control" name="index_fin">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                    <label for="station" class="text-sm font-medium text-gray-700 text-right">Agence</label>
                    <div class="md:col-span-3">
                        <select class="form-control" name="station">
                            <?php
                            $liste_station = $db->query("SELECT * FROM station ORDER BY id_station ASC");
                            foreach ($liste_station as $row) {
                                echo '<option value="' . $row["id_station"] . '">';
                                echo $row["lib"];
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <input type="submit" value=" Enrégistrer " class="btn btn-primary">
                </div>
                
                <?php
                if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                    
                    $num_doc = $_POST["num_doc"];
                    $date = $_POST["date_doc"];
                    $index_debut = $_POST["index_debut"];
                    $index_fin = $_POST["index_fin"];
                    $station = $_POST["station"];
                    
                    $sth = $db->prepare('INSERT INTO doc_carburant VALUES(NULL,:num_doc,:date,:index_debut,:index_fin,:station)');
                    $sth->bindParam(':num_doc', $num_doc);
                    $sth->bindParam(':date', $date);
                    $sth->bindParam(':index_debut', $index_debut);
                    $sth->bindParam(':index_fin', $index_fin);
                    $sth->bindParam(':station', $station);
                    $sth->execute();
                    
                    $_SESSION["message"] = "Enrégistrement avec succées";
                    echo "<script> window.location.replace('liste_doc_carburant.php')</script>";
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