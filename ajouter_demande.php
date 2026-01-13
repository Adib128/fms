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

// Check if coming from a reclamation
$reclamationData = null;
$fromReclamation = (int) ($_GET['from_reclamation'] ?? 0);
if ($fromReclamation > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM reclamation WHERE id = ?");
        $stmt->execute([$fromReclamation]);
        $reclamationData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Erreur lors du chargement de la réclamation : " . $e->getMessage();
    }
}

// Fetch data for dropdowns
try {
    $vehicules = $db->query("SELECT id_bus, matricule_interne, marque, type FROM bus ORDER BY matricule_interne ASC")->fetchAll(PDO::FETCH_ASSOC);
    $chauffeurs = $db->query("SELECT id_chauffeur, CONCAT(matricule, ' - ', nom_prenom) as nom_prenom FROM chauffeur ORDER BY nom_prenom ASC")->fetchAll(PDO::FETCH_ASSOC);
    $stations = $db->query("SELECT id_station, lib FROM station ORDER BY lib ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur lors du chargement des données : " . $e->getMessage();
}

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vehicule = (int) ($_POST['id_vehicule'] ?? 0);
    $id_chauffeur = (int) ($_POST['id_chauffeur'] ?? 0);
    $id_station = (int) ($_POST['id_station'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $priorite = !empty($_POST['priorite']) ? $_POST['priorite'] : null;
    $num_bdc = trim($_POST['num_bdc'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $immobiliser_vehicule = isset($_POST['immobiliser_vehicule']);
    
    // Validate required fields
    if ($id_vehicule <= 0) $errors[] = "Le véhicule est requis.";
    if ($id_chauffeur <= 0) $errors[] = "Le chauffeur est requis.";
    if ($id_station <= 0) $errors[] = "L'agence est requise.";
    if (empty($date)) $errors[] = "La date est requise.";
    if (empty($description)) $errors[] = "La description est requise.";
    
    // If no errors, insert data
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Generate unique number: YYMMXXXXX
            $numero = generateDemandeNumber($db);
            
            $index_km = !empty($_POST['index_km']) ? (int) $_POST['index_km'] : null;

            $stmt = $db->prepare('INSERT INTO demande (numero, num_bdc, id_vehicule, id_chauffeur, id_station, index_km, date, priorite, description, etat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $numero,
                $num_bdc,
                $id_vehicule,
                $id_chauffeur,
                $id_station,
                $index_km,
                $date,
                $priorite,
                $description,
                'En cours' // Default state for new request
            ]);
            $id_demande = $db->lastInsertId();

            // Handle Immobilization
            if ($immobiliser_vehicule) {
                // 1. Insert into immobilisation table
                $stmtImm = $db->prepare("INSERT INTO immobilisation (id_vehicule, start_date, commentaire) VALUES (?, ?, ?)");
                $stmtImm->execute([
                    $id_vehicule,
                    date('Y-m-d'), // Start date is today
                    "Immobilisation suite à la demande N° " . $numero . " : " . $description
                ]);

                // 2. Update bus state
                $stmtBus = $db->prepare("UPDATE bus SET etat = 'Immobiliser' WHERE id_bus = ?");
                $stmtBus->execute([$id_vehicule]);
            }

            
            $db->commit();
            $_SESSION['message'] = "Demande de réparation ajoutée avec succès.";
            header('Location: ' . url('liste-demande'));
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
                <h1 class="page-title">Nouvelle demande de réparation</h1>
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
                <span>Détails de la demande</span>
            </div>
            <div class="panel-body min-h-[800px]">
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Row 1: Date & Numéro BDC -->
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                            <input type="date" id="date" name="date" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= htmlspecialchars($_POST['date'] ?? ($reclamationData['date'] ?? date('Y-m-d'))) ?>">
                        </div>
                        <div>
                            <label for="num_bdc" class="block text-sm font-medium text-gray-700 mb-1">Numéro BDC</label>
                            <input type="text" id="num_bdc" name="num_bdc"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= htmlspecialchars($_POST['num_bdc'] ?? '') ?>" placeholder="Ex: BDC-12345">
                        </div>

                        <!-- Row 2: Véhicule & Index KM -->
                        <div>
                            <label for="id_vehicule" class="block text-sm font-medium text-gray-700 mb-1">Véhicule *</label>
                            <div class="relative">
                                <select id="id_vehicule" name="id_vehicule" required class="choices-select w-full" data-skip-tom-select="true">
                                    <option value="">Sélectionner un véhicule</option>
                                    <?php foreach ($vehicules as $vehicule): ?>
                                        <option value="<?= $vehicule['id_bus'] ?>" <?= ((isset($_POST['id_vehicule']) && $_POST['id_vehicule'] == $vehicule['id_bus']) || ($reclamationData && $reclamationData['id_vehicule'] == $vehicule['id_bus'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vehicule['matricule_interne'] . ' - ' . $vehicule['marque']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="vidange-info" class="mt-2 hidden">
                                    <div class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
                                        <span class="iconify text-slate-400 h-4 w-4" data-icon="mdi:oil"></span>
                                        <span class="text-xs font-medium text-slate-600">Km restant pour entretien préventif :</span>
                                        <span id="klm-reste-val" class="text-xs font-bold text-slate-900">--</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="index_km" class="block text-sm font-medium text-gray-700 mb-1">Index KM</label>
                            <input type="number" id="index_km" name="index_km" min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= htmlspecialchars($_POST['index_km'] ?? '') ?>" placeholder="Ex: 150000">
                        </div>

                        <!-- Row 3: Agence & Chauffeur -->
                        <div>
                            <label for="id_station" class="block text-sm font-medium text-gray-700 mb-1">Agence *</label>
                            <select id="id_station" name="id_station" required class="choices-select w-full" data-skip-tom-select="true">
                                <option value="">Sélectionner une agence</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?= $station['id_station'] ?>" <?= ((isset($_POST['id_station']) && $_POST['id_station'] == $station['id_station']) || ($reclamationData && $reclamationData['id_station'] == $station['id_station'])) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($station['lib']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="id_chauffeur" class="block text-sm font-medium text-gray-700 mb-1">Chauffeur *</label>
                            <select id="id_chauffeur" name="id_chauffeur" required class="choices-select w-full" data-skip-tom-select="true">
                                <option value="">Sélectionner un chauffeur</option>
                                <?php foreach ($chauffeurs as $chauffeur): ?>
                                    <option value="<?= $chauffeur['id_chauffeur'] ?>" <?= ((isset($_POST['id_chauffeur']) && $_POST['id_chauffeur'] == $chauffeur['id_chauffeur']) || ($reclamationData && $reclamationData['id_chauffeur'] == $chauffeur['id_chauffeur'])) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($chauffeur['nom_prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Row 4: Priorité & Checkbox -->
                        <div>
                            <label for="priorite" class="block text-sm font-medium text-gray-700 mb-1">Priorité</label>
                            <select id="priorite" name="priorite" class="choices-select w-full" data-skip-tom-select="true">
                                <option value="">Sélectionner une priorité</option>
                                <option value="Basse" <?= (isset($_POST['priorite']) && $_POST['priorite'] === 'Basse') ? 'selected' : '' ?>>Basse</option>
                                <option value="Moyenne" <?= (isset($_POST['priorite']) && $_POST['priorite'] === 'Moyenne') ? 'selected' : '' ?>>Moyenne</option>
                                <option value="Haute" <?= (isset($_POST['priorite']) && $_POST['priorite'] === 'Haute') ? 'selected' : '' ?>>Haute</option>
                                <option value="Critique" <?= (isset($_POST['priorite']) && $_POST['priorite'] === 'Critique') ? 'selected' : '' ?>>Critique</option>
                            </select>
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="immobiliser_vehicule" class="form-checkbox h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" <?= isset($_POST['immobiliser_vehicule']) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm font-medium text-gray-700">Immobiliser le véhicule</span>
                            </label>
                        </div>

                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                        <textarea id="description" name="description" rows="6" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Description détaillée de la demande..."><?= htmlspecialchars($_POST['description'] ?? ($reclamationData['description'] ?? '')) ?></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('liste-demande') ?>" class="btn-default">
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
        const choicesInstances = {};

        document.querySelectorAll('.choices-select').forEach((el) => {
            choicesInstances[el.id] = new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                removeItemButton: el.multiple,
                shouldSort: false,
                placeholder: true,
                placeholderValue: el.getAttribute('placeholder') || 'Sélectionner...'
            });
        });


        // Oil Change Info Logic
        const vehiculeSelect = document.getElementById('id_vehicule');
        const vidangeInfo = document.getElementById('vidange-info');
        const klmResteVal = document.getElementById('klm-reste-val');

        function updateVidangeInfo(vehiculeId) {
            if (!vehiculeId) {
                vidangeInfo.classList.add('hidden');
                return;
            }

            fetch(`api/get_vidange_info.php?id_bus=${vehiculeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        klmResteVal.textContent = data.klm_reste_moteur.toLocaleString() + ' km';
                        vidangeInfo.classList.remove('hidden');
                        
                        // Optional: Add color coding based on urgency
                        if (data.klm_reste_moteur <= 0) {
                            klmResteVal.className = 'text-xs font-bold text-red-600';
                        } else if (data.klm_reste_moteur <= 1000) {
                            klmResteVal.className = 'text-xs font-bold text-amber-600';
                        } else {
                            klmResteVal.className = 'text-xs font-bold text-green-600';
                        }
                    } else {
                        vidangeInfo.classList.add('hidden');
                    }
                })
                .catch(() => {
                    vidangeInfo.classList.add('hidden');
                });
        }

        vehiculeSelect.addEventListener('change', function() {
            updateVidangeInfo(this.value);
        });

        // Initial load if vehicle is already selected
        if (vehiculeSelect.value) {
            updateVidangeInfo(vehiculeSelect.value);
        }
    });
</script>
</body>
</html>
