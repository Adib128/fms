<?php
require 'header.php' ;
require_once 'class/class.php';
?>

<div id="page-wrapper">

    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h3>
                    Changer mot de passe
                </h3>
                <hr>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-lg-8">
                <form id="login-form" enctype="multipart/form-data" method="post" action="#">
                    <?php 
                    $passE = $npassE = $rnpassE = "" ; 
                    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                        $verif = true;
                        
                        // Set current user ID
                        if (isset($_SESSION['user_id'])) {
                            $admin->setId($_SESSION['user_id']);
                        }
                        
                        $ancien_pass = $admin->ancien_pass() ; 
                      
                        if ($ctrl->vide($_POST["pass"])) {
                            $passE = " * Champ obligatoire";
                            $verif = false;
                        }else{
                            if($ancien_pass != md5($_POST["pass"])){
                                $passE = " ancien mot de passe incorrecte";
                                $verif = false;
                            }
                        }
                        
                        if ($ctrl->vide($_POST["npass"])) {
                            $npassE = " * Champ obligatoire";
                            $verif = false;
                        }
                        
                        if ($ctrl->vide($_POST["rnpass"])) {
                            $rnpassE = " * Champ obligatoire";
                            $verif = false;
                        }else{
                            if($_POST["rnpass"]!=$_POST["npass"]){
                                $rnpassE = "  Confirmation incorrecte";
                                $verif = false;
                            }
                        }
                        
                        if($verif == true){
                            $npass = $_POST["npass"] ; 
                            $admin->setPass(md5($npass));
                            if($admin->changer()){
                                echo "<script> window.location.replace('suite.php')</script>";
                            }
                        }
                        
                        
                    }
                    ?>
                    <div class="form-group">
                        <label for="Prénom">Ancien mot de passe </label>
                        <input type="password" class="form-control"  name="pass" required>
                        <span class="error"><?php echo $passE; ?></span>
                    </div>

                    <div class="form-group">
                        <label for="NCIN">Nouveau mot de passe </label>
                        <input type="password" class="form-control"  name="npass" required>
                        <span class="error"><?php echo $npassE; ?></span>
                    </div>
                    <div class="form-group">
                        <label for="Nom">Confirmation de mot de passe</label>
                        <input type="password" class="form-control" name="rnpass" required>
                        <span class="error"><?php echo $rnpassE; ?></span>
                    </div>
                   
                   
                    <button type="submit" id="btn-sub" class="btn btn-lg btn-default">Changer</button>
            </div>
            
            </div>

        </div>
    </div>

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