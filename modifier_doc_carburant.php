<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config BEFORE header.php
require_once __DIR__ . '/config.php';

// Get document ID from URL
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

// Handle form submission BEFORE any HTML output
if ($_SERVER["REQUEST_METHOD"] == 'POST' && $id > 0) {
    $num_doc = trim($_POST["num_doc"] ?? '');
    $date = trim($_POST["date"] ?? '');
    $station = isset($_POST["station"]) ? (int)$_POST["station"] : 0;
    $index_debut = isset($_POST["index_debut"]) ? (int)$_POST["index_debut"] : 0;
    $index_fin = isset($_POST["index_fin"]) ? (int)$_POST["index_fin"] : 0;

    try {
        $db->beginTransaction();

        // Fetch old date for comparison
        $old_doc_query = $db->prepare("SELECT date FROM doc_carburant WHERE id_doc_carburant = ?");
        $old_doc_query->execute([$id]);
        $old_doc_data = $old_doc_query->fetch(PDO::FETCH_ASSOC);
        $old_date_db = $old_doc_data['date'] ?? '';

        // Normalize dates for comparison
        $old_date_normalized = $old_date_db ? date('Y-m-d', strtotime($old_date_db)) : '';
        $new_date_normalized = $date ? date('Y-m-d', strtotime($date)) : '';

        $date_changed = ($new_date_normalized !== $old_date_normalized);

        // Update doc_carburant
        $sth = $db->prepare('UPDATE doc_carburant SET num_doc_carburant=:num_doc,date=:date,id_station=:station,index_debut=:index_debut,index_fin=:index_fin WHERE id_doc_carburant=:id');
        $sth->bindParam(':num_doc', $num_doc);
        $sth->bindParam(':date', $date);
        $sth->bindParam(':station', $station);
        $sth->bindParam(':index_debut', $index_debut);
        $sth->bindParam(':index_fin', $index_fin);
        $sth->bindParam(':id', $id);
        $sth->execute();

        // If date changed, update all related carburant rows
        if ($date_changed) {
            $update_carburant_stmt = $db->prepare('UPDATE carburant SET date = :new_date WHERE id_doc_carburant = :id_doc_carburant');
            $update_carburant_stmt->execute([
                ':new_date' => $date,
                ':id_doc_carburant' => $id,
            ]);
        }

        $db->commit();

        $message = "Modification avec succès";
        if ($date_changed) {
            $message .= ". La date de toutes les lignes carburant associées a été mise à jour.";
        }
        $_SESSION["message"] = $message;
        
        // Use ob_start/ob_end_clean for safe redirect
        ob_start();
        echo "<script> window.location.replace('liste_doc_carburant.php?id=". $id ."')</script>";
        ob_end_flush();
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION["message"] = "Erreur lors de la modification : " . $e->getMessage();
    }
}

// Only include header.php if not already routed
if (!defined('ROUTED')) {
    require 'header.php';
} ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Modifier document carburant
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
                    unset($_SESSION["message"]);
                    ?>
                </div>

            <?php } ?>
            
            <?php
            if ($id > 0) {
                $select = $db->prepare("SELECT * FROM doc_carburant WHERE id_doc_carburant = ?");
                $select->execute([$id]);
                $item = $select->fetch(PDO::FETCH_ASSOC);
                
                if ($item) {
                ?>
                <form class="form-horizontal" enctype="multipart/form-data" method="post" action="#">

                    <div class="form-group">
                        <label for="firstname" class="col-sm-3 control-label"> Numéro Doc </label>
                        <div class="col-sm-6">
                            <input type="number" class="form-control" name="num_doc" value="<?php echo htmlspecialchars($item["num_doc_carburant"] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="firstname" class="col-sm-3 control-label">Date </label>
                        <div class="col-sm-6">
                            <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($item["date"] ?? ''); ?>">
                        </div>
                    </div>
                    
                     <div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Index Début </label>
                    <div class="col-sm-6">
                        <input type="number" class="form-control" name="index_debut" value="<?php echo htmlspecialchars($item["index_debut"] ?? ''); ?>">
                    </div>
                		</div>
                
                		<div class="form-group">
                    <label for="firstname" class="col-sm-3 control-label">Index Fin </label>
                    <div class="col-sm-6">
                        <input type="number" class="form-control" name="index_fin" value="<?php echo htmlspecialchars($item["index_fin"] ?? ''); ?>">
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
                                        echo '<option  value="' . htmlspecialchars($row["id_station"]) . '" selected>';
                                        echo htmlspecialchars($row["lib"]);
                                        echo '</option>';
                                    } else {
                                        echo '<option value="' . htmlspecialchars($row["id_station"]) . '">';
                                        echo htmlspecialchars($row["lib"]);
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
                </form>
                <?php
                } else {
                    echo "<div class='alert alert-danger'>Document introuvable.</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>ID de document invalide.</div>";
            }
            ?>
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