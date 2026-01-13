<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Enrégistrer document carte bon

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
                    <label for="firstname" class="col-sm-3 control-label"> Numéro Doc </label>
                    <div class="col-sm-6">
                        <input type="number" class="form-control" name="num_doc" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Date </label>
                    <div class="col-sm-6">
                        <input type="date" class="form-control" name="date_doc" value="" required>
                    </div>
                </div>
                
             
                
                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Agence</label>
                    <div class="col-sm-6">
                        <select class="form-control" name="station" required>
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


                <br>
                <div class="form-group">
                    <label class="col-sm-7 control-label"></label>
                    <input type="submit" value=" Enrégistrer " class="btn  btn-default btn-primary">
                </div>
                
                <?php
                if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                    
                    $num_doc = $_POST["num_doc"];
                    $date = $_POST["date_doc"];
                    $station = $_POST["station"];
                    
                    $sth = $db->prepare('INSERT INTO doc_bon VALUES(NULL,:num_doc,:date,:station)');
                    $sth->bindParam(':num_doc', $num_doc);
                    $sth->bindParam(':date', $date);
                    $sth->bindParam(':station', $station);
                    $sth->execute();
                    
                    $_SESSION["message"] = "Enrégistrement avec succées";
                    echo "<script> window.location.replace('liste_doc_bon.php')</script>";
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