<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and helpers BEFORE header.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/helpers.php';

// Security check
require_once __DIR__ . '/helpers/security.php';

$errors = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . url('passation-demande'));
    exit;
}

// Fetch existing data
try {
    $stmt = $db->prepare("SELECT * FROM demande_passation WHERE id = ?");
    $stmt->execute([$id]);
    $passation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$passation) {
        $_SESSION['message'] = "Demande de passation introuvable.";
        header('Location: ' . url('passation-demande'));
        exit;
    }

    $vehicules = $db->query("SELECT id_bus, matricule_interne, marque, type FROM bus ORDER BY matricule_interne ASC")->fetchAll(PDO::FETCH_ASSOC);
    $chauffeurs = $db->query("SELECT id_chauffeur, CONCAT(matricule, ' - ', nom_prenom) as nom_prenom FROM chauffeur ORDER BY nom_prenom ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur lors du chargement des données : " . $e->getMessage();
}

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vehicule = (int) ($_POST['id_vehicule'] ?? 0);
    $id_chauffeur_cedant = (int) ($_POST['id_chauffeur_cedant'] ?? 0);
    $id_chauffeur_repreneur = (int) ($_POST['id_chauffeur_repreneur'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d H:i');
    $etat = $_POST['etat'] ?? 'En cours';
    
    // Validate required fields
    if ($id_vehicule <= 0) $errors[] = "Le véhicule est requis.";
    if ($id_chauffeur_cedant <= 0) $errors[] = "Le chauffeur cédant est requis.";
    if ($id_chauffeur_repreneur <= 0) $errors[] = "Le chauffeur repreneur est requis.";
    if ($id_chauffeur_cedant === $id_chauffeur_repreneur) $errors[] = "Le cédant et le repreneur doivent être différents.";
    if (empty($date)) $errors[] = "La date est requise.";
    
    // If no errors, update data
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare('UPDATE demande_passation SET date = ?, id_vehicule = ?, id_chauffeur_cedant = ?, id_chauffeur_repreneur = ?, etat = ? WHERE id = ?');
            $stmt->execute([
                $date,
                $id_vehicule,
                $id_chauffeur_cedant,
                $id_chauffeur_repreneur,
                $etat,
                $id
            ]);
            
            $db->commit();
            $_SESSION['message'] = "Demande de passation modifiée avec succès.";
            header('Location: ' . url('passation-demande'));
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors[] = "Erreur lors de la modification de la demande : " . $e->getMessage();
        }
    }
}

// Only include header.php if not already routed and not redirecting
if (!defined('ROUTED')) {
    require 'header.php';
}

// Enforce route access
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Modifier la demande de passation : <?= htmlspecialchars($passation['numero']) ?></h1>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-heading">
                <span>Détails de la passation</span>
            </div>
            <div class="panel-body">
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Row 1: Date & Véhicule -->
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date et Heure *</label>
                            <input type="datetime-local" id="date" name="date" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($passation['date']))) ?>">
                        </div>
                        <div>
                            <label for="id_vehicule" class="block text-sm font-medium text-gray-700 mb-1">Véhicule *</label>
                            <select id="id_vehicule" name="id_vehicule" required class="choices-select w-full" data-skip-tom-select="true">
                                <option value="">Sélectionner un véhicule</option>
                                <?php foreach ($vehicules as $vehicule): ?>
                                    <option value="<?= $vehicule['id_bus'] ?>" <?= ($passation['id_vehicule'] == $vehicule['id_bus']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vehicule['matricule_interne'] . ' - ' . $vehicule['marque']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Row 2: Cédant & Repreneur -->
                        <div>
                            <label for="id_chauffeur_cedant" class="block text-sm font-medium text-gray-700 mb-1">Chauffeur Cédant *</label>
                            <select id="id_chauffeur_cedant" name="id_chauffeur_cedant" required class="choices-select w-full" data-skip-tom-select="true">
                                <option value="">Sélectionner le cédant</option>
                                <?php foreach ($chauffeurs as $chauffeur): ?>
                                    <option value="<?= $chauffeur['id_chauffeur'] ?>" <?= ($passation['id_chauffeur_cedant'] == $chauffeur['id_chauffeur']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($chauffeur['nom_prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="id_chauffeur_repreneur" class="block text-sm font-medium text-gray-700 mb-1">Chauffeur Repreneur *</label>
                            <select id="id_chauffeur_repreneur" name="id_chauffeur_repreneur" required class="choices-select w-full" data-skip-tom-select="true">
                                <option value="">Sélectionner le repreneur</option>
                                <?php foreach ($chauffeurs as $chauffeur): ?>
                                    <option value="<?= $chauffeur['id_chauffeur'] ?>" <?= ($passation['id_chauffeur_repreneur'] == $chauffeur['id_chauffeur']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($chauffeur['nom_prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Row 3: État -->
                        <div>
                            <label for="etat" class="block text-sm font-medium text-gray-700 mb-1">État *</label>
                            <select id="etat" name="etat" required class="choices-select w-full" data-skip-tom-select="true">
                                <option value="En cours" <?= ($passation['etat'] === 'En cours') ? 'selected' : '' ?>>En cours</option>
                                <option value="Accepter" <?= ($passation['etat'] === 'Accepter') ? 'selected' : '' ?>>Accepter</option>
                                <option value="Rejeter" <?= ($passation['etat'] === 'Rejeter') ? 'selected' : '' ?>>Rejeter</option>
                                <option value="Cloturer" <?= ($passation['etat'] === 'Cloturer') ? 'selected' : '' ?>>Cloturer</option>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('passation-demande') ?>" class="btn-default">
                            Annuler
                        </a>
                        <button type="submit" class="btn-primary">
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.choices-select').forEach((el) => {
            new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Sélectionner...'
            });
        });
    });
</script>
</body>
</html>
