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

if ($id === 0) {
    $_SESSION['message'] = "ID d'article PDR invalide.";
    header('Location: ' . url('liste-article'));
    exit;
}

// Fetch vehicles for dropdown
$vehicules = [];
try {
    $stmt = $db->query("SELECT id_bus, matricule_interne FROM bus ORDER BY matricule_interne ASC");
    $vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur lors du chargement des véhicules : " . $e->getMessage();
}

// Fetch existing article
$article = null;
try {
    $stmt = $db->prepare('SELECT * FROM article WHERE id = ?');
    $stmt->execute([$id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$article) {
        $_SESSION['message'] = "Article PDR introuvable.";
        header('Location: ' . url('liste-article'));
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Erreur lors de la récupération de l'article PDR.";
    header('Location: ' . url('liste-article'));
    exit;
}

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $designiation = trim($_POST['designiation'] ?? '');
    $famille = trim($_POST['famille'] ?? '');
    $etat = $_POST['etat'] ?? 'Neuf';
    $etat_pourcentage = (int) ($_POST['etat_pourcentage'] ?? 100);
    $valeur = (float) ($_POST['valeur'] ?? 0.00);
    $id_vehicule = !empty($_POST['id_vehicule']) ? (int) $_POST['id_vehicule'] : null;
    
    // Validate required fields
    if (empty($code)) $errors[] = "Le code est requis.";
    if (empty($designiation)) $errors[] = "La désignation est requise.";
    if (empty($famille)) $errors[] = "La famille est requise.";
    
    // Check for duplicate code (excluding current article)
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM article WHERE code = ? AND id != ?");
            $stmt->execute([$code, $id]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Ce code d'article PDR existe déjà.";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la vérification du code : " . $e->getMessage();
        }
    }
    
    // If no errors, update data
    if (empty($errors)) {
        try {
            $stmt = $db->prepare('UPDATE article SET code = ?, designiation = ?, famille = ?, etat = ?, etat_pourcentage = ?, valeur = ?, id_vehicule = ? WHERE id = ?');
            $stmt->execute([$code, $designiation, $famille, $etat, $etat_pourcentage, $valeur, $id_vehicule, $id]);
            
            $_SESSION['message'] = "Article PDR modifié avec succès.";
            header('Location: ' . url('liste-article'));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la modification de l'article PDR : " . $e->getMessage();
        }
    }
}

// Only include header.php if not already routed and not redirecting
if (!defined('ROUTED')) {
    require 'header.php';
}

// Enforce route access
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

// Use POST data if available, otherwise use existing data
$formData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $article;
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Modifier l'article PDR</h1>
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
                <span>Informations de l'article PDR</span>
            </div>
            <div class="panel-body">
                <form method="POST" class="space-y-6">
                    <!-- Code & Désignation -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                            <input type="text" id="code" name="code" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Code de l'article PDR"
                                value="<?= htmlspecialchars($formData['code'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="designiation" class="block text-sm font-medium text-gray-700 mb-1">Désignation *</label>
                            <input type="text" id="designiation" name="designiation" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Désignation de l'article PDR"
                                value="<?= htmlspecialchars($formData['designiation'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Famille & Valeur -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="famille" class="block text-sm font-medium text-gray-700 mb-1">Famille *</label>
                            <input type="text" id="famille" name="famille" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Famille de l'article PDR"
                                value="<?= htmlspecialchars($formData['famille'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="valeur" class="block text-sm font-medium text-gray-700 mb-1">Valeur (Prix) *</label>
                            <input type="number" id="valeur" name="valeur" step="0.01" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="0.00"
                                value="<?= htmlspecialchars($formData['valeur'] ?? '0.00') ?>">
                        </div>
                    </div>

                    <!-- État & Pourcentage -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="etat" class="block text-sm font-medium text-gray-700 mb-1">État *</label>
                            <select id="etat" name="etat" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="Neuf" <?= (isset($formData['etat']) && $formData['etat'] === 'Neuf') ? 'selected' : '' ?>>Neuf</option>
                                <option value="Récupérer" <?= (isset($formData['etat']) && $formData['etat'] === 'Récupérer') ? 'selected' : '' ?>>Récupérer</option>
                                <option value="Rénover" <?= (isset($formData['etat']) && $formData['etat'] === 'Rénover') ? 'selected' : '' ?>>Rénover</option>
                            </select>
                        </div>
                        <div>
                            <label for="etat_pourcentage" class="block text-sm font-medium text-gray-700 mb-1">État (%) *</label>
                            <input type="number" id="etat_pourcentage" name="etat_pourcentage" min="0" max="100" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="100"
                                value="<?= htmlspecialchars($formData['etat_pourcentage'] ?? '100') ?>">
                        </div>
                    </div>

                    <!-- Véhicule -->
                    <div>
                        <label for="id_vehicule" class="block text-sm font-medium text-gray-700 mb-1">Véhicule associé</label>
                        <select id="id_vehicule" name="id_vehicule" class="tom-select w-full">
                            <option value="">Aucun véhicule</option>
                            <?php foreach ($vehicules as $vehicule): ?>
                                <option value="<?= $vehicule['id_bus'] ?>" <?= (isset($formData['id_vehicule']) && $formData['id_vehicule'] == $vehicule['id_bus']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vehicule['matricule_interne']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('liste-article') ?>" class="btn-default">
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
</body>
</html>
