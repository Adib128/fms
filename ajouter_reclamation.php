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

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = trim($_POST['date'] ?? '');
    $id_vehicule = (int) ($_POST['id_vehicule'] ?? 0);
    $id_chauffeur = (int) ($_POST['id_chauffeur'] ?? 0);
    $id_station = (int) ($_POST['id_station'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $etat = 'en cours';
    
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
    
    // If no errors, insert data
    if (empty($errors)) {
        try {
            $stmt = $db->prepare('INSERT INTO reclamation (date, id_vehicule, id_chauffeur, id_station, description, etat) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$date, $id_vehicule, $id_chauffeur, $id_station, $description, $etat]);
            
            $_SESSION['message'] = "Réclamation ajoutée avec succès.";
            header('Location: ' . url('liste-reclamation'));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'ajout de la réclamation : " . $e->getMessage();
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
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Nouvelle réclamation</h1>
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
                                value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>">
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
                                    <option value="<?= $bus['id_bus'] ?>" <?= (isset($_POST['id_vehicule']) && $_POST['id_vehicule'] == $bus['id_bus']) ? 'selected' : '' ?>>
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
                                    <option value="<?= $chauffeur['id_chauffeur'] ?>" <?= (isset($_POST['id_chauffeur']) && $_POST['id_chauffeur'] == $chauffeur['id_chauffeur']) ? 'selected' : '' ?>>
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
                                data-skip-tom-select="true">
                                <option value="">Choisir la station</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?= $station['id_station'] ?>" <?= (isset($_POST['id_station']) && $_POST['id_station'] == $station['id_station']) ? 'selected' : '' ?>>
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
                                placeholder="Décrivez l'anomalie..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>
                    </div>


                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('liste-reclamation') ?>" class="btn-default">
                            Annuler
                        </a>
                        <button type="submit" class="btn-primary">
                            Enregistrer la réclamation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
