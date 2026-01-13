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
    $_SESSION['message'] = "ID de demande invalide.";
    header('Location: ' . url('liste-demande'));
    exit;
}

// Fetch existing demande
$demande = null;
try {
    $stmt = $db->prepare('SELECT * FROM demande WHERE id = ?');
    $stmt->execute([$id]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$demande) {
        $_SESSION['message'] = "Demande introuvable.";
        header('Location: ' . url('liste-demande'));
        exit;
    }


} catch (PDOException $e) {
    $_SESSION['message'] = "Erreur lors de la récupération de la demande.";
    header('Location: ' . url('liste-demande'));
    exit;
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
    $date = $_POST['date'] ?? '';
    $priorite = !empty($_POST['priorite']) ? $_POST['priorite'] : null;
    $etat = $_POST['etat'] ?? '';
    
    // Handle status update buttons
    if (isset($_POST['action_valider'])) {
        $etat = 'Valider';
    } elseif (isset($_POST['action_cloturer'])) {
        $etat = 'Cloturer';
    }
    
    $num_bdc = trim($_POST['num_bdc'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate required fields
    if ($id_vehicule <= 0) $errors[] = "Le véhicule est requis.";
    if ($id_chauffeur <= 0) $errors[] = "Le chauffeur est requis.";
    if ($id_station <= 0) $errors[] = "L'agence est requise.";
    if (empty($date)) $errors[] = "La date est requise.";
    if (empty($etat)) $errors[] = "L'état est requis.";
    if (empty($description)) $errors[] = "La description est requise.";
    
    // If no errors, update data
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $index_km = !empty($_POST['index_km']) ? (int) $_POST['index_km'] : null;

            $stmt = $db->prepare('UPDATE demande SET id_vehicule = ?, id_chauffeur = ?, id_station = ?, index_km = ?, date = ?, priorite = ?, etat = ?, num_bdc = ?, description = ? WHERE id = ?');
            $stmt->execute([
                $id_vehicule,
                $id_chauffeur,
                $id_station,
                $index_km,
                $date,
                $priorite,
                $etat,
                $num_bdc,
                $description,
                $id
            ]);
            if ($etat === 'Cloturer') {
                $stmtBus = $db->prepare('UPDATE bus SET etat = "Disponible" WHERE id_bus = ?');
                $stmtBus->execute([$id_vehicule]);
            }

            $db->commit();
            $_SESSION['message'] = "Demande de réparation modifiée avec succès.";
            header('Location: ' . url('modifier-demande') . '?id=' . $id);
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

// Use POST data if available, otherwise use existing data
$formData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $demande;
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Modifier la demande de réparation</h1>
                <div class="flex items-center gap-3 mt-1">
                    <p class="text-sm text-slate-500">Numéro : <?= htmlspecialchars($demande['numero']) ?></p>
                    <?php
                    $etatClass = match ($demande['etat']) {
                        'En cours' => 'bg-blue-100 text-blue-800 border-blue-200',
                        'Valider' => 'bg-green-100 text-green-800 border-green-200',
                        'Cloturer' => 'bg-slate-900 text-white border-slate-900',
                        default => 'bg-slate-900 text-white border-slate-900'
                    };
                    ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $etatClass ?>">
                        <?= strtoupper(htmlspecialchars($demande['etat'])) ?>
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($demande['etat'] === 'En cours'): ?>
                    <button type="submit" name="action_valider" form="demandeForm" class="btn-primary bg-green-600 hover:bg-green-700 border-green-600">
                        <span class="iconify h-5 w-5 mr-2" data-icon="mdi:check-all"></span>
                        Valider
                    </button>
                <?php elseif ($demande['etat'] === 'Valider'): ?>
                    <button type="submit" name="action_cloturer" form="demandeForm" class="btn-primary bg-slate-800 hover:bg-slate-900 border-slate-800">
                        <span class="iconify h-5 w-5 mr-2" data-icon="mdi:lock"></span>
                        Clôturer
                    </button>
                <?php endif; ?>

                <a href="<?= url('ajouter-ordre') ?>?id_demande=<?= $demande['id'] ?>" class="btn-primary bg-purple-600 hover:bg-purple-700 border-purple-600">
                    <span class="iconify h-5 w-5 mr-2" data-icon="mdi:file-document-arrow-right"></span>
                    Créer Ordre de Travail
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
                <span>Détails de la demande | <?= htmlspecialchars($demande['etat']) ?></span>
            </div>
            <div class="panel-body min-h-[800px]">
                <form id="demandeForm" method="POST" class="space-y-6">
                    <input type="hidden" name="etat" value="<?= htmlspecialchars($formData['etat']) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Row 1: Date & Numéro BDC -->
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                            <input type="date" id="date" name="date" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= htmlspecialchars($formData['date']) ?>">
                        </div>
                        <div>
                            <label for="num_bdc" class="block text-sm font-medium text-gray-700 mb-1">Numéro BDC</label>
                            <input type="text" id="num_bdc" name="num_bdc"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= htmlspecialchars($formData['num_bdc'] ?? '') ?>" placeholder="Ex: BDC-12345">
                        </div>

                        <!-- Row 2: Véhicule & Index KM -->
                        <div>
                            <label for="id_vehicule" class="block text-sm font-medium text-gray-700 mb-1">Véhicule *</label>
                            <select id="id_vehicule" name="id_vehicule" required class="choices-select w-full" data-skip-tom-select="true">
                                <option value="">Sélectionner un véhicule</option>
                                <?php foreach ($vehicules as $vehicule): ?>
                                    <option value="<?= $vehicule['id_bus'] ?>" <?= ($formData['id_vehicule'] == $vehicule['id_bus']) ? 'selected' : '' ?>>
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
                        <div>
                            <label for="index_km" class="block text-sm font-medium text-gray-700 mb-1">Index KM</label>
                            <input type="number" id="index_km" name="index_km" min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= htmlspecialchars($formData['index_km'] ?? '') ?>" placeholder="Ex: 150000">
                        </div>

                        <!-- Row 3: Agence & Chauffeur -->
                        <div>
                            <label for="id_station" class="block text-sm font-medium text-gray-700 mb-1">Agence *</label>
                            <select id="id_station" name="id_station" required class="choices-select w-full" data-skip-tom-select="true">
                                <option value="">Sélectionner une agence</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?= $station['id_station'] ?>" <?= ($formData['id_station'] == $station['id_station']) ? 'selected' : '' ?>>
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
                                    <option value="<?= $chauffeur['id_chauffeur'] ?>" <?= ($formData['id_chauffeur'] == $chauffeur['id_chauffeur']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($chauffeur['nom_prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Row 4: Atelier & Système -->
                        <!-- Row 4: Priorité & État -->
                        <div>
                            <label for="priorite" class="block text-sm font-medium text-gray-700 mb-1">Priorité</label>
                            <select id="priorite" name="priorite" class="choices-select w-full" data-skip-tom-select="true">
                                <option value="">Sélectionner une priorité</option>
                                <option value="Basse" <?= ($formData['priorite'] === 'Basse') ? 'selected' : '' ?>>Basse</option>
                                <option value="Moyenne" <?= ($formData['priorite'] === 'Moyenne') ? 'selected' : '' ?>>Moyenne</option>
                                <option value="Haute" <?= ($formData['priorite'] === 'Haute') ? 'selected' : '' ?>>Haute</option>
                                <option value="Critique" <?= ($formData['priorite'] === 'Critique') ? 'selected' : '' ?>>Critique</option>
                            </select>
                        </div>

                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                        <textarea id="description" name="description" rows="6" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Description détaillée de la demande..."><?= htmlspecialchars($formData['description']) ?></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('liste-demande') ?>" class="btn-default">
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
