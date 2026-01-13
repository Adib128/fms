<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Modifier document carte bon
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

            $select = $db->query("SELECT * FROM doc_carburant WHERE id_doc_carburant = '$id'");
            foreach ($select as $item) {
                ?>
                <form class="form-horizontal" enctype="multipart/form-data" method="post" action="#">

                    <div class="form-group">
                        <label for="firstname" class="col-sm-3 control-label"> Numéro Doc </label>
                        <div class="col-sm-6">
                            <input type="number" class="form-control" name="num_doc" value="<?php echo $item["num_doc_carburant"]; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="firstname" class="col-sm-3 control-label">Date </label>
                        <div class="col-sm-6">
                            <input type="date" class="form-control" name="date" value="<?php echo $item["date"]; ?>">
                        </div>
                    </div>


                    <div class="form-group">
                        <label  class="col-sm-3 control-label">Agence</label>
                        <div class="col-sm-6">
                            <select class="form-control" name="station">
                                <?php
                                $liste_station = $db->query("SELECT * FROM station ORDER BY id_station ASC");
                                foreach ($liste_station as $row) {
                                    if ($item["id_station"] == $row["id_station"]) {
                                        echo '<option  value="' . $row["id_station"] . '" selected>';
                                        echo $row["lib"];
                                        echo '</option>';
                                    } else {
                                        echo '<option value="' . $row["id_station"] . '">';
                                        echo $row["lib"];
                                        echo '</option>';
                                    }
                                }
                                ?>
                            </select>

                        </div>
                    </div>

                    <br><br>
                    <div class="form-group">
                        <label class="col-sm-6 control-label"></label>
                        <input type="submit" value="Enrégistrer les modifications" class="btn  btn-default btn-primary">
                    </div>
                    <?php
                    if ($_SERVER["REQUEST_METHOD"] == 'POST') {

                        $num_doc = $_POST["num_doc"];
                        $date = $_POST["date"];
                        $station = $_POST["station"];

                        $sth = $db->prepare('UPDATE doc_carburant SET num_doc_carburant=:num_doc,date=:date,id_station=:station WHERE id_doc_carburant=:id');
                        $sth->bindParam(':num_doc', $num_doc);
                        $sth->bindParam(':date', $date);
                        $sth->bindParam(':station', $station);
                        $sth->bindParam(':id', $id);
                        $sth->execute();

                        $_SESSION["message"] = "Modification avec succées";
                          echo "<script> window.location.replace('liste_doc_bon.php?id=". $id ."')</script>"; 
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