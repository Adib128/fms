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
    // Demandes (only those without an open order, or all for now)
    $demandes = $db->query("SELECT id, numero FROM demande WHERE etat != 'Cloturer' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Ateliers
    $ateliers = $db->query("SELECT id, nom FROM atelier ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Interventions (Initial load - though mostly fetched via AJAX now)
    $interventions = $db->query("SELECT id, libelle, id_anomalie FROM intervention ORDER BY libelle ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Technicians
    $techniciens = $db->query("SELECT id, nom, matricule FROM maintenance ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    $errors[] = "Erreur lors du chargement des données : " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = trim($_POST['numero'] ?? '');
    
    // If numero is empty or still the default placeholder-like format, we can regenerate it here 
    // but usually we trust the POST if it's there. However, to be safe:
    if (empty($numero)) {
        $numero = generateOrderNumber($db);
    }
    $date = trim($_POST['date'] ?? '');
    $id_demande = (int) ($_POST['id_demande'] ?? 0);
    $id_atelier = (int) ($_POST['id_atelier'] ?? 0);
    $id_system = (int) ($_POST['id_system'] ?? 0);
    $selected_anomalies = $_POST['anomalies'] ?? []; // Array of IDs
    $selected_interventions = $_POST['interventions'] ?? []; // Array of IDs

    // Validation
    if (empty($numero)) $errors[] = "Le numéro est requis.";
    if (empty($date)) $errors[] = "La date est requise.";
    if ($id_demande <= 0) $errors[] = "La demande est requise.";
    if ($id_atelier <= 0) $errors[] = "L'atelier est requis.";
    if ($id_system <= 0) $errors[] = "Le système est requis.";
    
    // Filter out empty intervention IDs
    $selected_interventions = array_filter($selected_interventions, function($val) {
        return !empty($val);
    });

    if (empty($selected_interventions)) {
        $errors[] = "Au moins une intervention est requise.";
    }
    
    // Check duplicate number
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM ordre WHERE numero = ?");
            $stmt->execute([$numero]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Ce numéro d'ordre existe déjà.";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de vérification : " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert Ordre
            $stmt = $db->prepare("INSERT INTO ordre (numero, id_demande, date, id_atelier, id_system, etat) VALUES (?, ?, ?, ?, ?, 'Ouvert')");
            $stmt->execute([$numero, $id_demande, $date, $id_atelier, $id_system]);
            $id_ordre = $db->lastInsertId();

            // Insert Interventions
            if (!empty($selected_interventions)) {
                $stmtInt = $db->prepare("INSERT INTO ordre_intervention (id_ordre, id_intervention) VALUES (?, ?)");
                $stmtTech = $db->prepare("INSERT INTO ordre_intervention_technicien (id_ordre_intervention, id_technicien, type) VALUES (?, ?, 'prévu')");
                
                // Get technicians from POST
                $all_techniciens_prevu = $_POST['techniciens_prevu'] ?? [];

                foreach ($selected_interventions as $index => $id_int) {
                    if (!empty($id_int)) {
                        $stmtInt->execute([$id_ordre, $id_int]);
                        $id_oi = $db->lastInsertId();

                        // Save planned technicians for this intervention
                        if (isset($all_techniciens_prevu[$index]) && is_array($all_techniciens_prevu[$index])) {
                            foreach ($all_techniciens_prevu[$index] as $id_tech) {
                                if (!empty($id_tech)) {
                                    $stmtTech->execute([$id_oi, $id_tech]);
                                }
                            }
                        }
                    }
                }
            }


            // Insert Anomalies
            if (!empty($selected_anomalies)) {
                $stmtAn = $db->prepare("INSERT INTO ordre_anomalie (id_ordre, id_anomalie) VALUES (?, ?)");
                foreach ($selected_anomalies as $id_an) {
                    if (!empty($id_an)) {
                        $stmtAn->execute([$id_ordre, $id_an]);
                    }
                }
            }

            // Update Demande Status to Valider
            $stmtUpdateDemande = $db->prepare("UPDATE demande SET etat = 'Valider' WHERE id = ?");
            $stmtUpdateDemande->execute([$id_demande]);

            // Update Vehicle Status to En réparation
            $stmtUpdateVehicule = $db->prepare("UPDATE bus SET etat = 'En réparation' WHERE id_bus = (SELECT id_vehicule FROM demande WHERE id = ?)");
            $stmtUpdateVehicule->execute([$id_demande]);

            $db->commit();
            $_SESSION['message'] = "Ordre de travail créé avec succès.";
            header('Location: ' . url('liste-ordre'));
            exit;

        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Erreur lors de la création de l'ordre : " . $e->getMessage();
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
                <h1 class="page-title">Nouvel ordre de travail</h1>
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

        <div class="panel min-h-[1000px] flex flex-col">
            <div class="panel-heading">
                <span>Informations de l'ordre</span>
            </div>
            <div class="panel-body flex-grow min-h-[950px]">
                <form method="POST" class="space-y-6" id="ordreForm" onsubmit="return validateForm()">
                    <!-- Numéro & Date -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="numero" class="block text-sm font-medium text-gray-700 mb-1">Numéro d'ordre *</label>
                            <input type="text" id="numero" name="numero" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                value="<?= htmlspecialchars($_POST['numero'] ?? generateOrderNumber($db)) ?>">
                        </div>
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                            <input type="date" id="date" name="date" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>">
                        </div>
                    </div>

                    <!-- Demande Selection -->
                    <div>
                        <label for="id_demande" class="block text-sm font-medium text-gray-700 mb-1">Demande de réparation *</label>
                        <select id="id_demande" name="id_demande" required class="w-full" data-skip-tom-select="true">
                            <option value="" placeholder>Sélectionner une demande...</option>
                            <?php foreach ($demandes as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ((isset($_POST['id_demande']) && $_POST['id_demande'] == $d['id']) || (isset($_GET['id_demande']) && $_GET['id_demande'] == $d['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['numero']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Demande Info (Disabled Inputs) -->
                    <div id="demande-info-panel" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Véhicule</label>
                            <input type="text" id="info_vehicule" disabled 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Index KM</label>
                            <input type="text" id="info_index_km" disabled 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Station</label>
                            <input type="text" id="info_station" disabled 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chauffeur</label>
                            <input type="text" id="info_chauffeur" disabled 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900">
                        </div>
                    </div>

                    <!-- Atelier Selection -->
                    <div>
                        <label for="id_atelier" class="block text-sm font-medium text-gray-700 mb-1">Atelier (Affectation) *</label>
                        <select id="id_atelier" name="id_atelier" required class="w-full" data-skip-tom-select="true">
                            <option value="" placeholder>Sélectionner un atelier...</option>
                            <?php foreach ($ateliers as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= (isset($_POST['id_atelier']) && $_POST['id_atelier'] == $a['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- System Selection -->
                    <div>
                        <label for="id_system" class="block text-sm font-medium text-gray-700 mb-1">Système *</label>
                        <select id="id_system" name="id_system" required class="w-full" data-skip-tom-select="true">
                            <option value="" placeholder>Sélectionner un système...</option>
                        </select>
                    </div>

                    <!-- Anomalies Selection -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="anomalies" class="block text-sm font-medium text-gray-700">Anomalies</label>
                            <button type="button" id="add-anomalie-modal-btn" class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors">
                                <span class="iconify mr-1 h-4 w-4" data-icon="mdi:plus-circle-outline"></span> Nouvelle anomalie
                            </button>
                        </div>
                        <select id="anomalies" name="anomalies[]" multiple class="w-full" data-skip-tom-select="true">
                            <!-- Options will be populated via JS -->
                        </select>
                    </div>

                    <!-- Interventions Dynamic List -->
                    <div id="interventions-wrapper" class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-semibold text-gray-700">Interventions à réaliser *</label>
                            <button type="button" id="add-intervention-btn" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all duration-200">
                                <span class="iconify mr-1.5 h-4 w-4" data-icon="mdi:plus"></span> Ajouter une intervention
                            </button>
                        </div>
                        <div id="interventions-container" class="space-y-3">
                            <!-- Rows will be added here -->
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('liste-ordre') ?>" class="btn-default">Annuler</a>
                        <button type="submit" id="submitOrderBtn" disabled class="btn-primary opacity-50 cursor-not-allowed">Créer l'ordre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- New Intervention Modal -->
<div id="newInterventionModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
            <div class="bg-white px-6 pt-6 pb-8">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-blue-50 text-blue-600">
                        <span class="iconify h-6 w-6" data-icon="mdi:plus-circle"></span>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-xl font-bold text-gray-900 mb-4" id="modal-title">Nouvelle Intervention</h3>
                        <div class="space-y-5 min-h-[250px]">
                            <div>
                                <label for="new_intervention_libelle" class="block text-sm font-medium text-gray-700 mb-1.5">Libellé de l'intervention *</label>
                                <input type="text" id="new_intervention_libelle" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" 
                                    placeholder="Ex: Vidange moteur, Remplacement filtre...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Lier à une anomalie (Optionnel)</label>
                                <div class="relative">
                                    <select id="new_intervention_anomalie" class="w-full" data-skip-tom-select="true">
                                        <option value="">Générale (Aucune anomalie)</option>
                                    </select>
                                </div>
                            </div>
                            <p id="modal-error" class="text-sm text-red-600 font-medium bg-red-50 p-3 rounded-lg hidden"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50/50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-gray-100">
                <button type="button" id="saveInterventionBtn" class="btn-primary px-6">
                    Enregistrer
                </button>
                <button type="button" id="closeModalBtn" class="btn-default px-6">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>

<!-- New Anomaly Modal -->
<div id="newAnomalieModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
            <div class="bg-white px-6 pt-6 pb-8">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-blue-50 text-blue-600">
                        <span class="iconify h-6 w-6" data-icon="mdi:alert-circle-outline"></span>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-xl font-bold text-gray-900 mb-4" id="modal-title">Nouvelle Anomalie</h3>
                        <div class="space-y-5 min-h-[150px]">
                            <div>
                                <label for="new_anomalie_designation" class="block text-sm font-medium text-gray-700 mb-1.5">Désignation de l'anomalie *</label>
                                <input type="text" id="new_anomalie_designation" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" 
                                    placeholder="Ex: Fuite d'huile, Bruit anormal...">
                            </div>
                            <p id="anomalie-modal-error" class="text-sm text-red-600 font-medium bg-red-50 p-3 rounded-lg hidden"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50/50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-gray-100">
                <button type="button" id="saveAnomalieBtn" class="btn-primary px-6">
                    Enregistrer
                </button>
                <button type="button" id="closeAnomalieModalBtn" class="btn-default px-6">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data for interventions (mutable)
    let interventionsData = <?= json_encode($interventions) ?>;
    let techniciensData = <?= json_encode($techniciens) ?>;
    
    // Store Choices instances for interventions to update them later
    const interventionChoicesInstances = [];
    const technicianChoicesInstances = [];


    // Initialize Choices.js
    const demandeSelect = new Choices('#id_demande', {
        searchEnabled: true,
        itemSelectText: '',
        placeholder: true,
        placeholderValue: 'Rechercher une demande...'
    });

    const atelierSelect = new Choices('#id_atelier', {
        searchEnabled: true,
        itemSelectText: '',
        placeholder: true,
        placeholderValue: 'Sélectionner un atelier...'
    });

    const systemSelect = new Choices('#id_system', {
        searchEnabled: true,
        itemSelectText: '',
        placeholder: true,
        placeholderValue: 'Sélectionner un système...'
    });

    const anomaliesSelect = new Choices('#anomalies', {
        searchEnabled: true,
        itemSelectText: '',
        placeholder: true,
        placeholderValue: 'Sélectionner les anomalies...',
        removeItemButton: true
    });

    const newInterventionAnomalieSelect = new Choices('#new_intervention_anomalie', {
        searchEnabled: true,
        itemSelectText: '',
        placeholder: true,
        placeholderValue: 'Générale (Aucune anomalie)',
        shouldSort: false
    });

    // --- Cascading Logic ---

    // 1. Atelier -> System
    const atelierSelectElement = document.getElementById('id_atelier');

    function fetchSystems(idAtelier) {
        if (!idAtelier) {
            systemSelect.clearStore();
            systemSelect.setChoices([], 'value', 'label', true);
            anomaliesSelect.clearStore(); // Also clear children
            anomaliesSelect.setChoices([], 'value', 'label', true);
            return;
        }

        fetch(`api/get_systems_by_atelier.php?id_atelier=${idAtelier}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                } else {
                    const choices = data.map(item => ({
                        value: item.id,
                        label: item.designation
                    }));
                    systemSelect.clearStore();
                    systemSelect.setChoices(choices, 'value', 'label', true);
                    
                    // Clear downstream
                    anomaliesSelect.clearStore();
                    anomaliesSelect.setChoices([], 'value', 'label', true);
                }
            })
            .catch(err => console.error(err));
    }

    atelierSelectElement.addEventListener('addItem', function(event) {
        fetchSystems(event.detail.value);
    });

    // 2. System -> Anomalies
    const systemSelectElement = document.getElementById('id_system');

    function fetchAnomalies(idSystem) {
        if (!idSystem) {
            anomaliesSelect.clearStore();
            anomaliesSelect.setChoices([], 'value', 'label', true);
            return;
        }

        fetch(`api/get_anomalies_by_system.php?id_system=${idSystem}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                } else {
                    const choices = data.map(item => ({
                        value: item.id,
                        label: item.designation
                    }));
                    anomaliesSelect.clearStore();
                    anomaliesSelect.setChoices(choices, 'value', 'label', true);
                }
            })
            .catch(err => console.error(err));
    }

    systemSelectElement.addEventListener('addItem', function(event) {
        fetchAnomalies(event.detail.value);
    });

    // 3. Anomalies -> Interventions
    const anomaliesSelectElement = document.getElementById('anomalies');

    function fetchInterventions() {
        const selectedAnomalies = anomaliesSelect.getValue(true); // Array of IDs
        
        if (selectedAnomalies.length === 0) {
            // If no anomalies, maybe show generic interventions or nothing?
            // User requirement: "if choose anomalies show select of intervention based on anomalies"
            // Let's clear if empty, or show all if that was the previous behavior (but requirement says based on anomalies)
            // Let's fetch based on anomalies.
            updateInterventionOptions([]);
            return;
        }

        fetch('api/get_interventions_by_anomalies.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ anomalie_ids: selectedAnomalies })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
            } else {
                updateInterventionOptions(data);
            }
        })
        .catch(err => console.error(err));
    }

    function updateInterventionOptions(interventions) {
        // Update local data source
        interventionsData = interventions;

        // Update all existing intervention selects
        interventionChoicesInstances.forEach(instance => {
            const currentValue = instance.getValue(true);
            instance.clearStore();
            
            const choices = interventions.map(item => ({
                value: item.id,
                label: item.libelle,
                selected: currentValue && currentValue.includes(item.id)
            }));
            
            instance.setChoices(choices, 'value', 'label', true);
        });
    }

    anomaliesSelect.passedElement.element.addEventListener('change', function() {
        fetchInterventions();
    });


    // --- End Cascading Logic ---

    // Fetch Demande Details
    const demandeSelectElement = document.getElementById('id_demande');
    
    // Info fields
    const infoVehicule = document.getElementById('info_vehicule');
    const infoIndexKm = document.getElementById('info_index_km');
    const infoStation = document.getElementById('info_station');
    const infoChauffeur = document.getElementById('info_chauffeur');

    function fetchDemandeDetails(id) {
        if (!id) {
            // Clear fields if no demand selected
            infoVehicule.value = '';
            infoIndexKm.value = '';
            infoStation.value = '';
            infoChauffeur.value = '';
            return;
        }

        // Fetch API
        fetch(`api/get_demande_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                } else {
                    // Populate fields
                    infoVehicule.value = data.bus_code ? `${data.bus_code} - ${data.bus_immatriculation}` : 'N/A';
                    infoIndexKm.value = data.index_km ? `${data.index_km} km` : 'N/A';
                    infoStation.value = data.station_nom || 'N/A';
                    infoChauffeur.value = data.chauffeur_nom || 'N/A';

                    // Pre-fill Atelier, System, and Anomalies
                    if (data.id_atelier) {
                        atelierSelect.setChoiceByValue(data.id_atelier.toString());
                        
                        // Fetch systems and then pre-fill system and anomalies
                        fetch(`api/get_systems_by_atelier.php?id_atelier=${data.id_atelier}`)
                            .then(response => response.json())
                            .then(systems => {
                                const choices = systems.map(item => ({
                                    value: item.id,
                                    label: item.designation,
                                    selected: item.id == data.id_system
                                }));
                                systemSelect.clearStore();
                                systemSelect.setChoices(choices, 'value', 'label', true);

                                if (data.id_system) {
                                    // Fetch anomalies and pre-fill
                                    fetch(`api/get_anomalies_by_system.php?id_system=${data.id_system}`)
                                        .then(response => response.json())
                                        .then(anomalies => {
                                            const anChoices = anomalies.map(item => ({
                                                value: item.id,
                                                label: item.designation,
                                                selected: data.anomalies && (data.anomalies.includes(item.id.toString()) || data.anomalies.includes(parseInt(item.id)))
                                            }));
                                            anomaliesSelect.clearStore();
                                            anomaliesSelect.setChoices(anChoices, 'value', 'label', true);
                                            
                                            // Trigger intervention fetch
                                            fetchInterventions();
                                        });
                                }
                            });
                    }
                }
            })
            .catch(err => {
                console.error(err);
            });
    }

    demandeSelectElement.addEventListener('addItem', function(event) {
        fetchDemandeDetails(event.detail.value);
    });
    
    // Also keep change for safety
    demandeSelectElement.addEventListener('change', function() {
        if (this.value) fetchDemandeDetails(this.value);
    });

    // Auto-load demand details if a demand is pre-selected (from URL parameter or POST)
    if (demandeSelectElement.value) {
        fetchDemandeDetails(demandeSelectElement.value);
    }

    // Dynamic Interventions
    const container = document.getElementById('interventions-container');
    const addBtn = document.getElementById('add-intervention-btn');

    // Modal Logic
    const modal = document.getElementById('newInterventionModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const saveInterventionBtn = document.getElementById('saveInterventionBtn');
    const newInterventionLibelle = document.getElementById('new_intervention_libelle');
    const modalError = document.getElementById('modal-error');
    let currentSelectToUpdate = null; // To track which select triggered the modal (if we want specific targeting, but here we update all)

    function openModal(triggerSelect = null) {
        modal.classList.remove('hidden');
        newInterventionLibelle.value = '';
        modalError.classList.add('hidden');
        
        // Populate Anomaly Select
        const selectedAnomalies = anomaliesSelect.getValue(); // Get objects {value, label}
        
        // Reset choices
        newInterventionAnomalieSelect.clearStore();
        
        const choices = [
            { value: '', label: 'Générale (Aucune anomalie)', selected: true }
        ];

        if (selectedAnomalies && selectedAnomalies.length > 0) {
            selectedAnomalies.forEach(an => {
                choices.push({ value: an.value, label: an.label });
            });
        }
        
        newInterventionAnomalieSelect.setChoices(choices, 'value', 'label', true);

        // If only one anomaly is selected (besides general), select it automatically
        if (selectedAnomalies && selectedAnomalies.length === 1) {
            newInterventionAnomalieSelect.setChoiceByValue(selectedAnomalies[0].value);
        }

        newInterventionLibelle.focus();
        currentSelectToUpdate = triggerSelect;
    }

    function closeModal() {
        modal.classList.add('hidden');
        currentSelectToUpdate = null;
    }

    closeModalBtn.addEventListener('click', closeModal);

    // --- Anomaly Modal Logic ---
    const anomalieModal = document.getElementById('newAnomalieModal');
    const closeAnomalieModalBtn = document.getElementById('closeAnomalieModalBtn');
    const saveAnomalieBtn = document.getElementById('saveAnomalieBtn');
    const addAnomalieModalBtn = document.getElementById('add-anomalie-modal-btn');
    const newAnomalieDesignation = document.getElementById('new_anomalie_designation');
    const anomalieModalError = document.getElementById('anomalie-modal-error');

    function openAnomalieModal() {
        let idSystem = systemSelect.getValue(true);
        
        // Fallback to underlying element if Choices returns nothing
        if (!idSystem) {
            idSystem = systemSelectElement.value;
        }

        if (!idSystem) {
            alert("Veuillez d'abord sélectionner un système.");
            return;
        }
        anomalieModal.classList.remove('hidden');
        newAnomalieDesignation.value = '';
        anomalieModalError.classList.add('hidden');
        newAnomalieDesignation.focus();
    }

    function closeAnomalieModal() {
        anomalieModal.classList.add('hidden');
    }

    addAnomalieModalBtn.addEventListener('click', openAnomalieModal);
    closeAnomalieModalBtn.addEventListener('click', closeAnomalieModal);

    saveAnomalieBtn.addEventListener('click', function() {
        const designation = newAnomalieDesignation.value.trim();
        let idSystem = systemSelect.getValue(true);
        if (!idSystem) {
            idSystem = systemSelectElement.value;
        }

        if (!designation) {
            anomalieModalError.textContent = "La désignation est requise.";
            anomalieModalError.classList.remove('hidden');
            return;
        }


        fetch('api/ajouter_anomalie_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ designation: designation, id_system: idSystem })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                anomalieModalError.textContent = data.error;
                anomalieModalError.classList.remove('hidden');
            } else {
                // Add to anomalies select and select it
                anomaliesSelect.setChoices([{
                    value: data.id.toString(),
                    label: data.designation,
                    selected: true
                }], 'value', 'label', false);
                
                closeAnomalieModal();
                // Trigger intervention fetch since anomalies changed
                fetchInterventions();
            }
        })
        .catch(err => {
            console.error(err);
            anomalieModalError.textContent = "Erreur lors de l'enregistrement.";
            anomalieModalError.classList.remove('hidden');
        });
    });
    // --- End Anomaly Modal Logic ---

    saveInterventionBtn.addEventListener('click', function() {
        const libelle = newInterventionLibelle.value.trim();
        if (!libelle) {
            modalError.textContent = "Le libellé est requis.";
            modalError.classList.remove('hidden');
            return;
        }

        const id_anomalie = newInterventionAnomalieSelect.getValue(true);

        // AJAX Call
        fetch('<?= url('api/ajouter_intervention_ajax.php') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ libelle: libelle, id_anomalie: id_anomalie })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                modalError.textContent = data.error;
                modalError.classList.remove('hidden');
            } else {
                // Success
                const newOption = { value: data.id, label: data.libelle };
                interventionsData.push({ id: data.id, libelle: data.libelle, id_anomalie: data.id_anomalie }); // Update local data

                // Update all existing Choices instances
                interventionChoicesInstances.forEach(instance => {
                    instance.setChoices([newOption], 'value', 'label', false);
                });

                // If a specific select triggered this (e.g. the one next to the button), select the new value
                if (currentSelectToUpdate) {
                    currentSelectToUpdate.setChoiceByValue(data.id);
                } else {
                    // Fallback: Select it in the last added select if available
                    if (interventionChoicesInstances.length > 0) {
                        interventionChoicesInstances[interventionChoicesInstances.length - 1].setChoiceByValue(data.id);
                    }
                }

                closeModal();
            }
        })
        .catch(err => {
            console.error(err);
            modalError.textContent = "Erreur lors de l'enregistrement.";
            modalError.classList.remove('hidden');
        });
    });

    function addInterventionRow() {
        const index = container.children.length;
        const row = document.createElement('div');
        row.className = 'intervention-row flex flex-col gap-3 bg-white p-4 rounded-xl border border-gray-200 shadow-sm transition-all duration-200 hover:border-brand-300';
        
        // Create select options for interventions
        let intOptionsHtml = '<option value="" placeholder>Sélectionner une intervention...</option>';
        interventionsData.forEach(item => {
            intOptionsHtml += `<option value="${item.id}">${item.libelle}</option>`;
        });

        // Create select options for technicians
        let techOptionsHtml = '';
        techniciensData.forEach(item => {
            const displayText = item.matricule ? `${item.matricule} - ${item.nom}` : item.nom;
            techOptionsHtml += `<option value="${item.id}">${displayText}</option>`;
        });


        row.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="flex-grow">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Intervention</label>
                    <select name="interventions[]" class="w-full intervention-select" data-skip-tom-select="true">
                        ${intOptionsHtml}
                    </select>
                </div>
                <div class="flex-shrink-0 pt-5">
                    <button type="button" class="bg-emerald-50 text-emerald-600 p-2 rounded-lg hover:bg-emerald-100 transition-colors add-new-intervention-btn" title="Nouvelle intervention">
                        <span class="iconify h-5 w-5" data-icon="mdi:plus-circle-outline"></span>
                    </button>
                </div>
                <div class="flex-shrink-0 pt-5">
                    <button type="button" class="text-red-400 hover:text-red-600 p-2 transition-colors remove-row-btn" title="Supprimer">
                        <span class="iconify h-5 w-5" data-icon="mdi:trash-can-outline"></span>
                    </button>
                </div>
            </div>
            <div class="w-full">
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Techniciens prévus</label>
                <select name="techniciens_prevu[${index}][]" multiple class="w-full technician-select" data-skip-tom-select="true">
                    ${techOptionsHtml}
                </select>
            </div>
        `;
        
        container.appendChild(row);
        
        // Initialize Choices.js on the new selects
        const newIntSelect = row.querySelector('.intervention-select');
        const intChoices = new Choices(newIntSelect, {
            searchEnabled: true,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: 'Sélectionner une intervention...'
        });
        interventionChoicesInstances.push(intChoices);

        const newTechSelect = row.querySelector('.technician-select');
        const techChoices = new Choices(newTechSelect, {
            searchEnabled: true,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: 'Sélectionner les techniciens...',
            removeItemButton: true
        });
        technicianChoicesInstances.push(techChoices);

        // Listen to Choices.js change event
        newIntSelect.addEventListener('change', function() {
            updateSubmitButtonState();
        });

        // Handle removal
        row.querySelector('.remove-row-btn').addEventListener('click', function() {
            const intIdx = interventionChoicesInstances.indexOf(intChoices);
            if (intIdx > -1) interventionChoicesInstances.splice(intIdx, 1);
            
            const techIdx = technicianChoicesInstances.indexOf(techChoices);
            if (techIdx > -1) technicianChoicesInstances.splice(techIdx, 1);
            
            row.remove();
            reindexRows();
            updateSubmitButtonState();
        });

        // Handle "Add New" button click
        row.querySelector('.add-new-intervention-btn').addEventListener('click', function() {
            openModal(intChoices);
        });

        updateSubmitButtonState();
    }


    function reindexRows() {
        const rows = container.querySelectorAll('.intervention-row');
        rows.forEach((row, idx) => {
            const techSelect = row.querySelector('.technician-select');
            if (techSelect) {
                techSelect.setAttribute('name', `techniciens_prevu[${idx}][]`);
            }
        });
    }

    const submitBtn = document.getElementById('submitOrderBtn');

    function updateSubmitButtonState() {
        const selects = document.querySelectorAll('select[name="interventions[]"]');
        let hasIntervention = false;
        selects.forEach(select => {
            if (select.value) hasIntervention = true;
        });

        if (hasIntervention) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }


    window.validateForm = function() {
        const selects = document.querySelectorAll('select[name="interventions[]"]');
        let hasIntervention = false;
        selects.forEach(select => {
            if (select.value) hasIntervention = true;
        });

        if (!hasIntervention) {
            alert("Veuillez ajouter au moins une intervention.");
            return false;
        }
        return true;
    };

    // Listen for changes in interventions
    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('intervention-select')) {
            updateSubmitButtonState();
        }
    });

    // Also update when a row is removed
    const observer = new MutationObserver(updateSubmitButtonState);
    observer.observe(container, { childList: true });

    // Initialize
    addBtn.addEventListener('click', addInterventionRow);

    // Add one row by default if empty (optional, but good UX)
    if (container.children.length === 0) {
        addInterventionRow();
    }
});

</script>
