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
    $_SESSION['message'] = "ID de réclamation invalide.";
    header('Location: ' . url('liste-reclamation'));
    exit;
}

// Fetch existing réclamation
$reclamation = null;
try {
    $stmt = $db->prepare('SELECT * FROM reclamation WHERE id = ?');
    $stmt->execute([$id]);
    $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reclamation) {
        $_SESSION['message'] = "Réclamation introuvable.";
        header('Location: ' . url('liste-reclamation'));
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Erreur lors de la récupération de la réclamation.";
    header('Location: ' . url('liste-reclamation'));
    exit;
}

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = trim($_POST['date'] ?? '');
    $id_vehicule = (int) ($_POST['id_vehicule'] ?? 0);
    $id_chauffeur = (int) ($_POST['id_chauffeur'] ?? 0);
    $id_station = (int) ($_POST['id_station'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $etat = trim($_POST['etat'] ?? 'en cours');
    
    // Validate required fields
    if (empty($date)) {
        $errors[] = "La date est requise.";
    }
    if ($id_vehicule === 0) {
        $errors[] = "Le véhicule est requis.";
    }
    if ($id_chauffeur === 0) {
        $errors[] = "Le chauffeur est requis.";
    }
    if ($id_station === 0) {
        $errors[] = "La station est requise.";
    }
    if (empty($description)) {
        $errors[] = "La description est requise.";
    }
    
    // If no errors, update data
    if (empty($errors)) {
        try {
            $stmt = $db->prepare('UPDATE reclamation SET date = ?, id_vehicule = ?, id_chauffeur = ?, id_station = ?, description = ?, etat = ? WHERE id = ?');
            $stmt->execute([$date, $id_vehicule, $id_chauffeur, $id_station, $description, $etat, $id]);
            
            $_SESSION['message'] = "Réclamation modifiée avec succès.";
            header('Location: ' . url('liste-reclamation'));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la modification de la réclamation : " . $e->getMessage();
        }
    }
}

// Only include header.php if not already routed and not redirecting
if (!defined('ROUTED')) {
    require 'header.php';
}

// Enforce route access
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

// Fetch data for dropdowns
$buses = [];
$chauffeurs = [];
$stations = [];

try {
    $busStmt = $db->query('SELECT id_bus, matricule_interne FROM bus ORDER BY matricule_interne ASC');
    $buses = $busStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Impossible de charger la liste des véhicules : " . $e->getMessage();
}

try {
    $chauffeurStmt = $db->query('SELECT id_chauffeur, nom_prenom FROM chauffeur ORDER BY nom_prenom ASC');
    $chauffeurs = $chauffeurStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Impossible de charger la liste des chauffeurs : " . $e->getMessage();
}

try {
    $stationStmt = $db->query('SELECT id_station, lib FROM station ORDER BY lib ASC');
    $stations = $stationStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Impossible de charger la liste des stations : " . $e->getMessage();
}

// Use POST data if available, otherwise use existing data
$formData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $reclamation;
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Modifier la réclamation</h1>
            </div>
            <div>
                <a href="<?= url('ajouter-demande') ?>?from_reclamation=<?= $reclamation['id'] ?>" 
                   class="btn-primary bg-green-600 hover:bg-green-700 border-green-600">
                    <span class="iconify h-5 w-5 mr-2" data-icon="mdi:file-document-arrow-right"></span>
                    Transférer en Demande
                </a>
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
                <span>Informations de la réclamation</span>
            </div>
            <div class="panel-body">
                <form method="POST" class="space-y-6">
                    <!-- Date -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="date" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Date *</label>
                        <div class="md:col-span-8">
                            <input type="date" id="date" name="date" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="<?= htmlspecialchars($formData['date'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Véhicule -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="id_vehicule" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Véhicule *</label>
                        <div class="md:col-span-8">
                            <select id="id_vehicule" name="id_vehicule" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                data-placeholder="Choisir le véhicule" data-search-placeholder="Rechercher un véhicule">
                                <option value="">Choisir le véhicule</option>
                                <?php foreach ($buses as $bus): ?>
                                    <option value="<?= $bus['id_bus'] ?>" <?= ($formData['id_vehicule'] == $bus['id_bus']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($bus['matricule_interne']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Chauffeur -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="id_chauffeur" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Chauffeur *</label>
                        <div class="md:col-span-8">
                            <select id="id_chauffeur" name="id_chauffeur" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                data-placeholder="Choisir le chauffeur" data-search-placeholder="Rechercher un chauffeur">
                                <option value="">Choisir le chauffeur</option>
                                <?php foreach ($chauffeurs as $chauffeur): ?>
                                    <option value="<?= $chauffeur['id_chauffeur'] ?>" <?= ($formData['id_chauffeur'] == $chauffeur['id_chauffeur']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($chauffeur['nom_prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Station -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="id_station" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Station *</label>
                        <div class="md:col-span-8">
                            <select id="id_station" name="id_station" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                data-placeholder="Choisir la station" data-search-placeholder="Rechercher une station">
                                <option value="">Choisir la station</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?= $station['id_station'] ?>" <?= ($formData['id_station'] == $station['id_station']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($station['lib']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-start">
                        <label for="description" class="text-sm font-medium text-gray-700 text-right md:col-span-2 mt-3">Description *</label>
                        <div class="md:col-span-8">
                            <textarea id="description" name="description" required rows="5"
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Décrivez l'anomalie..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- État -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="etat" class="text-sm font-medium text-gray-700 text-right md:col-span-2">État *</label>
                        <div class="md:col-span-8">
                            <select id="etat" name="etat" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="en cours" <?= ($formData['etat'] === 'en cours') ? 'selected' : '' ?>>En cours</option>
                                <option value="traitée" <?= ($formData['etat'] === 'traitée') ? 'selected' : '' ?>>Traitée</option>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('liste-reclamation') ?>" class="btn-default">
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
