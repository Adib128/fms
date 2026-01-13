<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Enrégistrer kilomètrage
               
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
            <form class="form-horizontal" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
                
                 <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Agence</label>
                    <div class="col-sm-6">
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
                
                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Véhicule</label>
                    <div class="col-sm-6">
                        <select class="form-control selectpicker" name="bus"  data-live-search="true" title="Choisire le bus" required>
                            <?php
                            $liste_station = $db->query("SELECT * FROM bus ORDER BY id_bus ASC");
                            foreach ($liste_station as $row) {
                                echo '<option value="' . $row["id_bus"] . '">';
                                echo $row["matricule_interne"];
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Date de parcours</label>
                    <div class="col-sm-6">
                        <input type="date" class="form-control" name="date_parcours" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Kilomètrage</label>
                    <div class="col-sm-6">
                        <input type="number" class="form-control" name="kilometrage">
                    </div>
                </div>
                
                <br>
                <div class="form-group">
                    <label class="col-sm-7 control-label"></label>
                    <input type="submit" value=" Enrégistrer kilomètrage " class="btn  btn-default btn-primary">
                </div>
                <?php
                if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                    
                    $station = $_POST["station"];
                    $bus = $_POST["bus"];
                    $date_parcours = $_POST["date_parcours"];
                    $kilometrage = $_POST["kilometrage"];
                    $date_saisie = date("Y-m-d") ; 

                    $select = $db->query("SELECT * FROM kilometrage WHERE id_bus='$bus' AND date_kilometrage='$date_parcours'");
                    $e = $select->fetch(PDO::FETCH_NUM);
                    if ($e > 0) {
                        echo "<p class='errorMsg'> --- Ce kilomètrage est deja saisie ---</p>";
                    } else {
                        
                        $select = $db->query("SELECT * FROM total_kilometrage WHERE id_bus='$bus'");
                        $total_kilometrage = 0 ; 
                        foreach ($select as $row) {
                            $total_kilometrage = $row["kilometrage"] ; 
                        }
                        
                        $total_kilometrage = (int)$total_kilometrage ; 
                        $kilometrage = (int)$kilometrage ;
                        $kilometrage_actuel = 0 ; 
                        
                        $kilometrage_actuel = $total_kilometrage + $kilometrage ;
                        
                        /*************************** Calcul Vidange *************************/
                        $index_dernier_vidange = 0 ; 
                        //selection vidange
                        $liste = $db->query(
                            "SELECT * FROM  vidange WHERE id_bus = '$bus'"
                    		);
                    		foreach ($liste as $row) {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
                        	$kilometrage_vidange = $row["kilometrage"] ;  
                        	$index_dernier_vidange = $row["indexe"] ;
                     	}
                     	
                     	// Select dernier index
                     	$dernier_index = 0 ; 
                     	
                        $liste = $db->query(
                            "SELECT * FROM  carburant WHERE id_bus = '$bus' ORDER BY id_carburant DESC LIMIT 1"
                    		);
                    		foreach ($liste as $row) {                 
                        	$dernier_index = $row["index_km"] ;
                     	}
                     	
                     	//selection freq
                        $liste = $db->query(
                            "SELECT * FROM  bus WHERE id_bus = '$bus'"
                    		);
                    		foreach ($liste as $row) {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
                        	$freq_vidange = $row["vidange"] ;  
                        	$matricule_interne = $row["matricule_interne"] ; 
                     	}
                     	
                     	/************************************************************/
                     	
                     	//calcul reste
                     	$reste = (int)$kilometrage_actuel - (int)$kilometrage_vidange ;
                     	
                     	//calcul difference
                     	$diff = (int)$freq_vidange - (int)$reste ; 
                     	
                     	/************************************************************/
                     	
                     	//calcul reste index
                     	$reste_index = $dernier_index - $index_dernier_vidange ;  
                     	
                     	//calcul difference vidange
                     	$diff_index = (int)$freq_vidange - (int)$reste_index ;
                     	
                     		// Insert kilomètrage
                        	$sth = $db->prepare('INSERT INTO kilometrage VALUES(NULL,:date_saisie,:date_parcours,:kilometrage,:kilometrage_actuel,:id_bus)');
                        	$sth->bindParam(':date_saisie', $date_saisie);
                        	$sth->bindParam(':date_parcours', $date_parcours);
                        	$sth->bindParam(':kilometrage', $kilometrage);
                        	$sth->bindParam(':kilometrage_actuel', $kilometrage_actuel);
                        	$sth->bindParam(':id_bus', $bus);
                        	$sth->execute();
                        
                        	//Update Kilomètrage totale
                        	$sth = $db->prepare('UPDATE total_kilometrage SET kilometrage=:total_kilometrage WHERE id_bus=:id_bus');
                        	$sth->bindParam(':total_kilometrage', $kilometrage_actuel);
                        	$sth->bindParam(':id_bus', $bus);
                        	$sth->execute();
                        	
                        	if((int)$diff <= 1000) {
                        	echo "<div class='alert alert-info alert-dismissable'>
                    					<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times; </button>
                    					[Exp] Véhicule : " . $matricule_interne . " |  Kilométrage reste pour le prochain vidange :  " . $diff ." <br> 
                				</div>" ; 
                        	}
                        	
                        	if((int)$diff_index <= 1000) {
                        	echo "<div class='alert alert-info alert-dismissable'>
                    					<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times; </button>
                    					[Index] Véhicule : " . $matricule_interne . " |  Kilométrage reste pour le prochain vidange :  " . $diff_index ." <br> 
                				</div>" ; 
                        	}                        	
                        	                              	
                     	
                        $_SESSION["message"] = "Succées d'ajout de kilomètrage";
                        echo "<script> window.location.replace('liste_kilometrage.php')</script>";
                    }
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