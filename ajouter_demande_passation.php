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

// Fetch data for dropdowns
try {
    $vehicules = $db->query("SELECT id_bus, matricule_interne, marque, type FROM bus ORDER BY matricule_interne ASC")->fetchAll(PDO::FETCH_ASSOC);
    $chauffeurs = $db->query("
        SELECT c.id_chauffeur, CONCAT(c.matricule, ' - ', c.nom_prenom, ' (', COALESCE(s.lib, 'Aucune agence'), ')') as nom_prenom 
        FROM chauffeur c 
        LEFT JOIN station s ON c.id_station = s.id_station 
        ORDER BY c.nom_prenom ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur lors du chargement des données : " . $e->getMessage();
}

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vehicule = (int) ($_POST['id_vehicule'] ?? 0);
    $id_chauffeur_cedant = (int) ($_POST['id_chauffeur_cedant'] ?? 0);
    $id_chauffeur_repreneur = (int) ($_POST['id_chauffeur_repreneur'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d H:i');
    
    // Validate required fields
    if ($id_vehicule <= 0) $errors[] = "Le véhicule est requis.";
    if ($id_chauffeur_cedant <= 0) $errors[] = "Le chauffeur cédant est requis.";
    if ($id_chauffeur_repreneur <= 0) $errors[] = "Le chauffeur repreneur est requis.";
    if ($id_chauffeur_cedant === $id_chauffeur_repreneur) $errors[] = "Le cédant et le repreneur doivent être différents.";
    if (empty($date)) $errors[] = "La date est requise.";
    
    // If no errors, insert data
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Generate unique number: PASYYMMXXXXX
            $numero = generatePassationDemandeNumber($db);
            
            $stmt = $db->prepare('INSERT INTO demande_passation (numero, date, id_vehicule, id_chauffeur_cedant, id_chauffeur_repreneur, etat) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $numero,
                $date,
                $id_vehicule,
                $id_chauffeur_cedant,
                $id_chauffeur_repreneur,
                'En cours'
            ]);
            
            $db->commit();
            $_SESSION['message'] = "Demande de passation ajoutée avec succès.";
            header('Location: ' . url('passation-demande'));
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors[] = "Erreur lors de l'ajout de la demande : " . $e->getMessage();
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
                <h1 class="page-title">Nouvelle demande de passation</h1>
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
            <div class="panel-body min-h-[600px]">
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Row 1: Date & Véhicule -->
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date et Heure *</label>
                            <input type="datetime-local" id="date" name="date" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d\TH:i')) ?>">
                        </div>
                        <div>
                            <label for="id_vehicule" class="block text-sm font-medium text-gray-700 mb-1">Véhicule *</label>
                            <select id="id_vehicule" name="id_vehicule" required class="choices-select w-full" data-skip-tom-select="true">
                                <option value="">Sélectionner un véhicule</option>
                                <?php foreach ($vehicules as $vehicule): ?>
                                    <option value="<?= $vehicule['id_bus'] ?>" <?= (isset($_POST['id_vehicule']) && $_POST['id_vehicule'] == $vehicule['id_bus']) ? 'selected' : '' ?>>
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
                                    <option value="<?= $chauffeur['id_chauffeur'] ?>" <?= (isset($_POST['id_chauffeur_cedant']) && $_POST['id_chauffeur_cedant'] == $chauffeur['id_chauffeur']) ? 'selected' : '' ?>>
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
                                    <option value="<?= $chauffeur['id_chauffeur'] ?>" <?= (isset($_POST['id_chauffeur_repreneur']) && $_POST['id_chauffeur_repreneur'] == $chauffeur['id_chauffeur']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($chauffeur['nom_prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('passation-demande') ?>" class="btn-default">
                            Annuler
                        </a>
                        <button type="submit" class="btn-primary">
                            Enregistrer la demande
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
