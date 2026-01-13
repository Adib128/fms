<?php
require 'header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<script>window.location.href='" . url('liste-immobilisation') . "';</script>";
    exit();
}

$item = $db->prepare("SELECT * FROM immobilisation WHERE id = ?");
$item->execute([$id]);
$item = $item->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo "<script>window.location.href='" . url('liste-immobilisation') . "';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vehicule = $_POST['id_vehicule'];
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $commentaire = $_POST['commentaire'];

    // Overlap validation (excluding current record)
    $query = "SELECT COUNT(*) FROM immobilisation 
              WHERE id_vehicule = ? 
              AND id != ?
              AND start_date <= ? 
              AND (end_date IS NULL OR end_date >= ?)";
    
    $check_end_date = $end_date ?? '9999-12-31';
    $stmt = $db->prepare($query);
    $stmt->execute([$id_vehicule, $id, $check_end_date, $start_date]);
    
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "Ce véhicule est déjà immobilisé durant cette période.";
    } else {
        $stmt = $db->prepare("UPDATE immobilisation SET id_vehicule = ?, start_date = ?, end_date = ?, commentaire = ? WHERE id = ?");
        $stmt->execute([$id_vehicule, $start_date, $end_date, $commentaire, $id]);
        
        // Update vehicle status based on end_date
        if (!empty($end_date)) {
            // If end date is set, mark vehicle as available
            $stmtUpdate = $db->prepare("UPDATE bus SET etat = 'Disponible' WHERE id_bus = ?");
            $stmtUpdate->execute([$id_vehicule]);
        } else {
            // If no end date, keep vehicle as immobilized
            $stmtUpdate = $db->prepare("UPDATE bus SET etat = 'Immobiliser' WHERE id_bus = ?");
            $stmtUpdate->execute([$id_vehicule]);
        }
        
        $_SESSION['message'] = "Immobilisation mise à jour avec succès.";
        echo "<script>window.location.href='" . url('liste-immobilisation') . "';</script>";
        exit();
    }

}
?>

<div id="page-wrapper">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <h1 class="page-title">Modifier Immobilisation</h1>
            <p class="text-sm text-slate-500">Mettre à jour les informations d'immobilisation.</p>
        </div>

        <?php if (isset($_SESSION["error"])) : ?>
            <div class="alert-danger flex items-start gap-3 mb-6">
                <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <div class="flex-1 text-sm">
                    <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-body p-8">
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Véhicule</label>
                            <select name="id_vehicule" id="vehiculeSelect" required class="w-full rounded-lg border-slate-200 text-sm" data-skip-tom-select="true">
                                <option value="">Sélectionner un véhicule</option>
                                <?php
                                $vehicules = $db->query("SELECT id_bus, matricule_interne FROM bus ORDER BY matricule_interne ASC");
                                foreach ($vehicules as $v) {
                                    $selected = ($item['id_vehicule'] == $v['id_bus']) ? 'selected' : '';
                                    echo "<option value='{$v['id_bus']}' $selected>{$v['matricule_interne']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Date Début</label>
                            <input type="date" name="start_date" required value="<?= $item['start_date'] ?>" class="w-full rounded-lg border-slate-200 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Date Fin (Optionnel)</label>
                            <input type="date" name="end_date" value="<?= $item['end_date'] ?>" class="w-full rounded-lg border-slate-200 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Commentaire</label>
                        <textarea name="commentaire" rows="4" class="w-full rounded-lg border-slate-200 text-sm" placeholder="Raison de l'immobilisation..."><?= htmlspecialchars($item['commentaire']) ?></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="<?= url('liste-immobilisation') ?>" class="btn-secondary">Annuler</a>
                        <button type="submit" class="btn-primary">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    $(document).ready(function() {
        new Choices('#vehiculeSelect', {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false
        });
    });
</script>
</body>
</html>
