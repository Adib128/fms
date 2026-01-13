<?php
require 'header.php';

$stations = [];
$buses = [];
$compartiments = [];
$oilTypes = [];
$filterTypes = [];
$errors = [];
$success = false;
$operationsData = [];

try {
    $stationStmt = $db->query('SELECT id_station, lib FROM station ORDER BY id_station ASC');
    $stations = $stationStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Impossible de charger la liste des agences : " . $e->getMessage();
}

try {
    $busStmt = $db->query('SELECT id_bus, matricule_interne, huile_moteur, huile_boite_vitesse, huile_pont FROM bus ORDER BY matricule_interne ASC');
    $buses = $busStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Impossible de charger la liste des véhicules : " . $e->getMessage();
}

try {
    $compartimentStmt = $db->query('SELECT id, name FROM compartiments ORDER BY 
        CASE 
            WHEN name = "Moteur" THEN 1
            WHEN name = "Boite Vitesse" THEN 2
            WHEN name = "Pont" THEN 3
            ELSE 4
        END, name ASC');
    $compartiments = $compartimentStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Impossible de charger la liste des compartiments : " . $e->getMessage();
}

try {
    $oilTypeStmt = $db->query('SELECT id, name, usageOil FROM oil_types ORDER BY name ASC');
    $oilTypes = $oilTypeStmt ? $oilTypeStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    $filterTypeStmt = $db->query('SELECT id, name, usageFilter FROM filter_types ORDER BY name ASC');
    $filterTypes = $filterTypeStmt ? $filterTypeStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    $liquidesStmt = $db->query('SELECT id, name FROM liquides ORDER BY name ASC');
    $liquides = $liquidesStmt ? $liquidesStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    $errors[] = "Impossible de charger la liste des filtres : " . $e->getMessage();
}

try {
    $checklistStmt = $db->query('SELECT id, code, label, parti FROM checklist_items ORDER BY parti, code ASC');
    $checklistItems = $checklistStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If table doesn't exist, we'll handle it gracefully
    $checklistItems = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = trim($_POST['numero'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $stationId = (int) ($_POST['station'] ?? 0);
    $vehiclesRaw = $_POST['vehicles'] ?? [];
    $vehicleChecklists = $_POST['vehicle_checklist'] ?? [];
    $vehiclesData = [];

    if ($numero === '') {
        $errors[] = "Le numéro de fiche est requis.";
    }
    if ($date === '') {
        $errors[] = "La date de fiche est requise.";
    }
    if ($stationId === 0) {
        $errors[] = "La station est requise.";
    }

    // Process vehicles
    if (is_array($vehiclesRaw)) {
        foreach ($vehiclesRaw as $vehicleIndex => $vehicle) {
            $busId = isset($vehicle['bus']) ? (int) $vehicle['bus'] : 0;
            $indexKm = isset($vehicle['index']) ? (int) $vehicle['index'] : 0;
            $selectedCompartments = $vehicle['compartiments'] ?? [];
            $operationsRaw = $vehicle['operations'] ?? [];

            // Ignore completely empty vehicle rows
            if ($busId === 0 && $indexKm === 0 && empty($selectedCompartments)) {
                continue;
            }

            // Validate required vehicle fields
            if ($busId === 0) {
                $errors[] = "Le véhicule est requis pour le véhicule #" . ($vehicleIndex + 1);
                continue;
            }
            if (empty($selectedCompartments)) {
                $errors[] = "Au moins un compartiment doit être sélectionné pour le véhicule #" . ($vehicleIndex + 1);
                continue;
            }

            // Process operations for each selected compartment
            $vehicleOps = [];
            foreach ($selectedCompartments as $compartimentId) {
                $compOperations = $operationsRaw[$compartimentId] ?? [];
                
                if (!empty($compOperations)) {
                    foreach ($compOperations as $operation) {
                        $type = $operation['type'] ?? '';
                        
                        if ($type === 'Huile') {
                            $oilOperation = $operation['oil_operation'] ?? '';
                            $oilTypeId = (int) ($operation['oil_type_id'] ?? 0);
                            $quantity = (float) ($operation['quantity'] ?? 0);
                            
                            // For control operations, quantity can be 0 or null
                            if ($oilOperation && $oilTypeId > 0 && ($quantity > 0 || $oilOperation === 'Controle')) {
                                $vehicleOps[] = [
                                    'compartiment_id' => (int) $compartimentId,
                                    'type' => 'Huile',
                                    'oil_operation' => $oilOperation,
                                    'oil_type_id' => $oilTypeId,
                                    'quantity' => $quantity,
                                    'filter_operation' => null,
                                    'filter_type_id' => null
                                ];
                            }
                        } elseif ($type === 'Filter') {
                            $filterOperation = $operation['filter_operation'] ?? '';
                            $filterTypeId = (int) ($operation['filter_type_id'] ?? 0);
                            
                            if ($filterOperation && $filterTypeId > 0) {
                                $vehicleOps[] = [
                                    'compartiment_id' => (int) $compartimentId,
                                    'type' => 'Filter',
                                    'oil_operation' => null,
                                    'oil_type_id' => null,
                                    'quantity' => null,
                                    'filter_operation' => $filterOperation,
                                    'filter_type_id' => $filterTypeId
                                ];
                            }
                        } elseif ($type === 'Liquide') {
                            $oilOperation = $operation['oil_operation'] ?? '';
                            $liquideTypeId = (int) ($operation['liquide_type_id'] ?? 0);
                            $quantity = (float) ($operation['quantity'] ?? 0);
                            
                            // For control operations, quantity can be 0 or null
                            if ($oilOperation && $liquideTypeId > 0 && ($quantity > 0 || $oilOperation === 'Controle')) {
                                $vehicleOps[] = [
                                    'compartiment_id' => (int) $compartimentId,
                                    'type' => 'Liquide',
                                    'oil_operation' => $oilOperation,
                                    'oil_type_id' => null,
                                    'liquide_type_id' => $liquideTypeId,
                                    'quantity' => $quantity,
                                    'filter_operation' => null,
                                    'filter_type_id' => null
                                ];
                            }
                        }
                    }
                }
            }

            if (empty($vehicleOps)) {
                $errors[] = "Au moins une intervention doit être ajoutée pour le véhicule #" . ($vehicleIndex + 1);
                continue;
            }

            $vehiclesData[] = [
                'bus' => $busId,
                'index_km' => $indexKm,
                'operations' => $vehicleOps,
                'checklist' => $vehicleChecklists[$vehicleIndex] ?? [],
            ];
        }
    }

    if (empty($vehiclesData)) {
        $errors[] = "Vous devez ajouter au moins un véhicule avec ses interventions.";
    }

    if (empty($errors)) {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Insert into fiche_entretien
            $stmt = $db->prepare('INSERT INTO fiche_entretien (numd_doc, date, id_station) VALUES (:numero, :date_fiche, :id_station)');
            $stmt->execute([
                ':numero' => $numero,
                ':date_fiche' => $date,
                ':id_station' => $stationId,
            ]);
            $ficheId = $db->lastInsertId();
            
            // Insert each vehicle and its operations
            foreach ($vehiclesData as $vehicle) {
                // Insert into maintenance_records
                $stmt = $db->prepare('INSERT INTO maintenance_records (fiche_id, id_bus, date, index_km) VALUES (:fiche_id, :id_bus, :date, :index_km)');
                $stmt->execute([
                    ':fiche_id' => $ficheId,
                    ':id_bus' => $vehicle['bus'],
                    ':date' => $date,
                    ':index_km' => $vehicle['index_km'],
                ]);
                $recordId = $db->lastInsertId();
                
                // Insert all operations for this vehicle
                $stmt = $db->prepare('INSERT INTO maintenance_operations (record_id, compartiment_id, type, oil_operation, oil_type_id, liquide_type_id, quantity, filter_operation, filter_type_id) VALUES (:record_id, :compartiment_id, :type, :oil_operation, :oil_type_id, :liquide_type_id, :quantity, :filter_operation, :filter_type_id)');
                
                foreach ($vehicle['operations'] as $operation) {
                    $stmt->execute([
                        ':record_id' => $recordId,
                        ':compartiment_id' => $operation['compartiment_id'],
                        ':type' => $operation['type'],
                        ':oil_operation' => $operation['oil_operation'],
                        ':oil_type_id' => $operation['oil_type_id'],
                        ':liquide_type_id' => $operation['liquide_type_id'] ?? null,
                        ':quantity' => $operation['quantity'],
                        ':filter_operation' => $operation['filter_operation'],
                        ':filter_type_id' => $operation['filter_type_id'],
                    ]);
                }
                
                // Insert checklist items for this vehicle
                if (!empty($vehicle['checklist'])) {
                    $checklistStmt = $db->prepare('INSERT INTO maintenance_checklist_status (id_bus, id_fiche, checklist_item_id, is_checked, checked_at) VALUES (:id_bus, :id_fiche, :checklist_item_id, :is_checked, :checked_at)');
                    
                    foreach ($vehicle['checklist'] as $checklistItemId) {
                        $checklistStmt->execute([
                            ':id_bus' => $vehicle['bus'],
                            ':id_fiche' => $ficheId,
                            ':checklist_item_id' => $checklistItemId,
                            ':is_checked' => 1,
                            ':checked_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
            
            // Commit transaction
            $db->commit();

            $_SESSION['message'] = "Fiche d'entretien ajoutée avec succès.";
            echo "<script>window.location.replace('/details-fiche-entretien?id=" . $ficheId . "');</script>";
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Enregistrer fiche d'entretien</h1>
            </div>
            <div class="flex gap-3">
                <a href="<?= url('liste-fiche-entretien') ?>" class="btn-default">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5" />
                        <path d="M12 19l-7-7 7-7" />
                    </svg>
                    Retour à la liste
                </a>
                <button type="submit" form="main-form" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 13l4 4L19 7" />
                    </svg>
                    Enregistrer
                </button>
            </div>
        </div>

        <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger">
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-heading">
                <span>Informations de la fiche</span>
            </div>
            <div class="panel-body">
                <style>
                    .form-control--enhanced {
                        display: flex;
                        flex-direction: column;
                        gap: 0.5rem;
                    }
                    .form-control--enhanced span {
                        font-size: 0.875rem;
                        font-weight: 500;
                        color: #374151;
                    }
                    .input-with-icon {
                        position: relative;
                        display: flex;
                        align-items: center;
                    }
                    .input-icon {
                        position: absolute;
                        left: 0.75rem;
                        width: 1.25rem;
                        height: 1.25rem;
                        color: #6b7280;
                        pointer-events: none;
                        z-index: 1;
                    }
                    .input--standard {
                        width: 100%;
                        padding: 0.625rem 0.75rem;
                        border: 1px solid #d1d5db;
                        border-radius: 0.5rem;
                        font-size: 0.875rem;
                        line-height: 1.25rem;
                        min-height: 2.5rem;
                        color: #111827;
                        background-color: #ffffff;
                        transition: all 0.15s ease-in-out;
                    }
                    .input--standard:focus {
                        outline: none;
                        border-color: #3b82f6;
                        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                    }
                    .select-wrapper {
                        position: relative;
                        display: flex;
                        align-items: center;
                    }
                    .select--with-icon {
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        background-image: none;
                    }
                    .select-caret {
                        position: absolute;
                        right: 0.85rem;
                        width: 1rem;
                        height: 1rem;
                        color: #94a3b8;
                        pointer-events: none;
                    }
                    .compartment-section {
                        border: 1px solid #e5e7eb;
                        border-radius: 0.75rem;
                        padding: 1.5rem;
                        margin-bottom: 1rem;
                        background: #fafafa;
                        transition: all 0.2s ease;
                    }
                    .compartment-section.active {
                        border-color: #3b82f6;
                        background: #eff6ff;
                    }
                    .vehicle-block {
                        transition: transform 0.2s ease, box-shadow 0.2s ease;
                    }
                    .vehicle-block:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                    }
                    .compartment-header {
                        display: flex;
                        align-items: center;
                        gap: 0.75rem;
                        margin-bottom: 1rem;
                    }
                    .compartment-checkbox {
                        width: 1.25rem;
                        height: 1.25rem;
                        border-radius: 0.375rem;
                        border: 2px solid #d1d5db;
                        transition: all 0.2s ease;
                    }
                    .compartment-checkbox:checked {
                        background-color: #3b82f6;
                        border-color: #3b82f6;
                    }
                    .compartment-title {
                        font-weight: 600;
                        color: #374151;
                        font-size: 1rem;
                    }
                    .operations-container {
                        margin-left: 2rem;
                        display: none;
                    }
                    .operations-container.show {
                        display: block;
                    }
                    .operation-tabs {
                        display: flex;
                        gap: 0.5rem;
                        margin-bottom: 1rem;
                        border-bottom: 1px solid #e5e7eb;
                    }
                    .operation-tab {
                        padding: 0.5rem 1rem;
                        border: none;
                        background: none;
                        color: #6b7280;
                        cursor: pointer;
                        border-bottom: 2px solid transparent;
                        transition: all 0.2s ease;
                    }
                    .operation-tab.active {
                        color: #3b82f6;
                        border-bottom-color: #3b82f6;
                    }
                    .operation-content {
                        display: none;
                    }
                    .operation-content.active {
                        display: block;
                    }
                    .operation-form {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 1rem;
                        align-items: end;
                    }
                    .operation-form .form-group {
                        flex: 1;
                        min-width: 200px;
                    }
                    .add-operation-btn {
                        background: #10b981;
                        color: white;
                        border: none;
                        padding: 0.5rem 1rem;
                        border-radius: 0.5rem;
                        cursor: pointer;
                        font-size: 0.875rem;
                        transition: all 0.2s ease;
                    }
                    .add-operation-btn:hover {
                        background: #059669;
                    }
                    .operations-list {
                        margin-top: 1rem;
                        space-y: 0.5rem;
                    }
                    .operation-item {
                        background: white;
                        border: 1px solid #e5e7eb;
                        border-radius: 0.5rem;
                        padding: 1rem;
                        display: flex;
                        justify-content: between;
                        align-items: center;
                        gap: 1rem;
                    }
                    .operation-info {
                        flex: 1;
                    }
                    .operation-type {
                        font-weight: 600;
                        color: #374151;
                    }
                    .operation-details {
                        color: #6b7280;
                        font-size: 0.875rem;
                    }
                    .remove-operation-btn {
                        background: #ef4444;
                        color: white;
                        border: none;
                        padding: 0.25rem 0.75rem;
                        border-radius: 0.375rem;
                        cursor: pointer;
                        font-size: 0.75rem;
                        transition: all 0.2s ease;
                    }
                    .remove-operation-btn:hover {
                        background: #dc2626;
                    }
                    .floating-add-vehicle {
                        position: fixed;
                        bottom: 2rem;
                        right: 2rem;
                        z-index: 40;
                        opacity: 1;
                        visibility: visible;
                        transform: translateY(0);
                        transition: all 0.3s ease;
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    }
                </style>
                <form method="POST" class="space-y-6" id="main-form">
                    <!-- Basic Information Only -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <label class="form-control form-control--enhanced">
                            <span>Numéro de fiche</span>
                            <input
                                type="number"
                                id="numero"
                                name="numero"
                                class="input input--standard"
                                value="<?= htmlspecialchars($_POST['numero'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >
                        </label>
                        <label class="form-control form-control--enhanced">
                            <span>Date</span>
                            <input
                                type="date"
                                id="date"
                                name="date"
                                class="input input--standard"
                                value="<?= htmlspecialchars($_POST['date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >
                        </label>
                        <label class="form-control form-control--enhanced">
                            <span>Atelier</span>
                            <select
                                name="station"
                                required
                                class=""
                                data-skip-tom-select="true"
                            >
                                <option value="">Choisir un atelier</option>
                                <?php foreach ($stations as $station) : ?>
                                    <option value="<?= (int) $station['id_station']; ?>"><?= htmlspecialchars($station['lib'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <!-- Vehicles Section -->
                    <div class="border border-dashed border-slate-300 rounded-xl p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-700">Véhicules</h3>
                            <button type="button" id="add-vehicle" class="inline-flex items-center gap-2 rounded-lg border border-blue-500 px-3 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 5v14" />
                                    <path d="M5 12h14" />
                                </svg>
                                Ajouter un véhicule
                            </button>
                        </div>

                        <div id="vehicles-container" class="space-y-6">
                            <!-- Dynamic vehicle blocks injected here -->
                        </div>

                        <template id="vehicle-template">
                            <div class="vehicle-block bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-xl p-4 space-y-4 shadow-sm">
                                <!-- Line 1: Vehicle and Index -->
                                <div class="vehicle-info-line flex flex-col md:flex-row md:items-center gap-3">
                                    <label class="form-control flex-1">
                                        <span>Véhicule</span>
                                        <select class="input input--standard" name="vehicles[__INDEX__][bus]" required>
                                            <option value="" disabled hidden selected>Choisir un véhicule</option>
                                            <?php foreach ($buses as $bus) : ?>
                                                <option value="<?= (int) $bus['id_bus']; ?>"><?= htmlspecialchars($bus['matricule_interne'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="form-control flex-1">
                                        <span>Index Klm</span>
                                        <input type="number" step="0.01" class="input input--standard" name="vehicles[__INDEX__][index]" placeholder="Saisir l'index" required>
                                    </label>
                                    <div class="flex gap-2">
                                        <button type="button" class="toggle-operations inline-flex items-center gap-2 rounded-lg border border-green-500 px-3 py-2 text-sm font-medium text-green-600 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 5v14" />
                                                <path d="M5 12h14" />
                                            </svg>
                                            Ajouter intervention
                                        </button>
                                        <button type="button" class="toggle-checklist inline-flex items-center gap-2 rounded-lg border border-blue-500 px-3 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M9 11l3 3L22 4" />
                                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
                                            </svg>
                                            Checklist <span class="checklist-count">(0)</span>
                                        </button>
                                        <button type="button" class="remove-vehicle inline-flex items-center gap-2 rounded-lg border border-red-500 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M18 6 6 18" />
                                                <path d="m6 6 12 12" />
                                            </svg>
                                            Retirer
                                        </button>
                                    </div>
                                </div>

                                <!-- Line 2: Operations (hidden by default) -->
                                <div class="operations-section hidden">
                                    <div class="border-t border-gray-200 pt-4">
                                        <h4 class="text-md font-semibold text-slate-700 mb-4">Interventions pour ce véhicule</h4>
                                        
                                        <!-- Compartments Section for this vehicle -->
                                        <div class="vehicle-compartments space-y-4">
                                            <?php foreach ($compartiments as $compartiment) : ?>
                                                <div class="compartment-section" data-compartment-id="<?= (int) $compartiment['id']; ?>" data-vehicle-index="__VEHICLE_INDEX__">
                                                    <div class="compartment-header">
                                                        <input type="checkbox" 
                                                               class="compartment-checkbox" 
                                                               name="vehicles[__VEHICLE_INDEX__][compartiments][]" 
                                                               value="<?= (int) $compartiment['id']; ?>"
                                                               id="compartment___VEHICLE_INDEX__<?= (int) $compartiment['id']; ?>">
                                                        <label for="compartment___VEHICLE_INDEX__<?= (int) $compartiment['id']; ?>" class="compartment-title">
                                                            Compartiment <?= htmlspecialchars($compartiment['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="operations-container" id="operations___VEHICLE_INDEX__<?= (int) $compartiment['id']; ?>">
                                                        <div class="operation-tabs">
                                                            <button type="button" class="operation-tab active" data-tab="huile">Huile</button>
                                                            <?php if (strtolower($compartiment['name']) !== 'pont'): ?>
                                                            <button type="button" class="operation-tab" data-tab="filter">Filtre</button>
                                                            <?php endif; ?>
                                                            <?php if (strtolower($compartiment['name']) === 'moteur'): ?>
                                                            <button type="button" class="operation-tab" data-tab="liquide">Liquide</button>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <!-- Huile Operations -->
                                                        <div class="operation-content active" data-content="huile">
                                                            <div class="operation-form">
                                                                <div class="form-group">
                                                                    <label>Intervention</label>
                                                                    <select name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][oil_operation]" class="input input--standard" data-skip-tom-select="true">
                                                                        <option value="" disabled selected>Intervention</option>
                                                                        <option value="Apoint">Appoint</option>
                                                                        <option value="Controle">Contrôle</option>
                                                                        <option value="Vidange">Vidange</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Type d'huile</label>
                                                                    <select name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][oil_type_id]" class="input input--standard oil-type-select" data-compartment="<?= htmlspecialchars($compartiment['name'], ENT_QUOTES, 'UTF-8'); ?>" data-skip-tom-select="true">
                                                                        <option value="" disabled selected>Choisir</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Quantité</label>
                                                                    <input type="number" step="0.01" name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][quantity]" class="input input--standard" placeholder="0.00">
                                                                </div>
                                                                <div class="form-group">
                                                                    <input type="hidden" name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][type]" value="Huile">
                                                                    <button type="button" class="add-operation-btn">Ajouter</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if (strtolower($compartiment['name']) !== 'pont'): ?>
                                                        <!-- Filter Operations -->
                                                        <div class="operation-content" data-content="filter">
                                                            <div class="operation-form">
                                                                <div class="form-group">
                                                                    <label>Type de filtre</label>
                                                                    <select name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][filter_type_id]" class="input input--standard filter-type-select" data-compartment="<?= htmlspecialchars($compartiment['name'], ENT_QUOTES, 'UTF-8'); ?>" data-skip-tom-select="true">
                                                                        <option value="" disabled selected>Choisir</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Intervention</label>
                                                                    <select name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][filter_operation]" class="input input--standard" data-skip-tom-select="true">
                                                                        <option value="" disabled selected>Choisir</option>
                                                                        <option value="Nettoyage">Nettoyage</option>
                                                                        <option value="Changement">Changement</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <input type="hidden" name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][type]" value="Filter">
                                                                    <button type="button" class="add-operation-btn">Ajouter</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (strtolower($compartiment['name']) === 'moteur'): ?>
                                                        <!-- Liquide Operations -->
                                                        <div class="operation-content" data-content="liquide">
                                                            <div class="operation-form">
                                                                <div class="form-group">
                                                                    <label>Intervention</label>
                                                                    <select name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][oil_operation]" class="input input--standard" data-skip-tom-select="true">
                                                                        <option value="" disabled selected>Intervention</option>
                                                                        <option value="Apoint">Appoint</option>
                                                                        <option value="Controle">Contrôle</option>
                                                                        <option value="Vidange">Vidange</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Type de liquide</label>
                                                                    <select name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][liquide_type_id]" class="input input--standard" data-skip-tom-select="true">
                                                                        <option value="" disabled selected>Choisir</option>
                                                                        <?php foreach ($liquides as $liquide): ?>
                                                                        <option value="<?= (int) $liquide['id']; ?>"><?= htmlspecialchars($liquide['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Quantité</label>
                                                                    <input type="number" step="0.01" name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][quantity]" class="input input--standard" placeholder="0.00">
                                                                </div>
                                                                <div class="form-group">
                                                                    <input type="hidden" name="vehicles[__VEHICLE_INDEX__][operations][<?= (int) $compartiment['id']; ?>][__INDEX__][type]" value="Liquide">
                                                                    <button type="button" class="add-operation-btn">Ajouter</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="operations-list" id="operations_list___VEHICLE_INDEX__<?= (int) $compartiment['id']; ?>">
                                                            <!-- Operations will be added here dynamically -->
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                        <a href="<?= url('liste-fiche-entretien') ?>" class="btn-default">
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Floating Add Vehicle Button -->
<button type="button" id="floating-add-vehicle" class="floating-add-vehicle inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-3 text-white shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 17l2-2v-6a6 6 0 0112 0v6l-2 2"/>
        <path d="M8 17v4"/>
        <path d="M16 17v4"/>
        <rect x="4" y="9" width="16" height="8" rx="1"/>
        <circle cx="8.5" cy="13.5" r="1.5"/>
        <circle cx="15.5" cy="13.5" r="1.5"/>
    </svg>
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 5v14" />
        <path d="M5 12h14" />
    </svg>
</button>

<!-- Checklist Modal -->
<div id="checklist-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[80vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 id="checklist-modal-title" class="text-lg font-semibold text-gray-900">Checklist d'entretien</h3>
                    <div class="flex items-center gap-3">
                        <button type="button" id="cancel-checklist-top" class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                            Annuler
                        </button>
                        <button type="submit" form="checklist-form" class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                            Enregistrer
                        </button>
                        <button type="button" id="close-checklist-modal" class="text-gray-400 hover:text-gray-600 ml-2">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <form id="checklist-form">
                    <input type="hidden" id="checklist-vehicle-index" name="vehicle_index">
                    
                    <!-- Search input -->
                    <div class="mb-6 flex flex-col sm:flex-row gap-4">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text" 
                                   id="checklist-search" 
                                   placeholder="Rechercher dans la checklist..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div class="flex items-center">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="checklist-filter-checked" class="form-checkbox h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700 font-medium">Afficher cochés uniquement</span>
                            </label>
                        </div>
                    </div>
                    
                    <?php if (!empty($checklistItems)): ?>
                        <div id="checklist-items-container">
                        <?php
                        // Group checklist items by parti
                        $groupedChecklist = [];
                        foreach ($checklistItems as $item) {
                            $groupedChecklist[$item['parti']][] = $item;
                        }
                        
                        $partiColors = [
                            'MOTEUR' => 'blue',
                            'TRANSMISSION' => 'green',
                            'FREINAGE' => 'red',
                            'SUSPENSION ET DIRECTION' => 'yellow',
                            'ELECTRICITE AUTO' => 'purple',
                            'CARROSSERIE' => 'gray'
                        ];
                        ?>
                        
                        <?php foreach ($groupedChecklist as $parti => $items): ?>
                            <div class="checklist-section mb-6" data-parti="<?= htmlspecialchars($parti, ENT_QUOTES, 'UTF-8'); ?>">
                                <h4 class="text-md font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-<?= $partiColors[$parti] ?? 'gray' ?>-100 text-<?= $partiColors[$parti] ?? 'gray' ?>-800">
                                        <?= htmlspecialchars($parti, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </h4>
                                <div class="checklist-items space-y-2">
                                    <?php foreach ($items as $item): ?>
                                        <label class="checklist-item flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer" data-search-text="<?= strtolower(htmlspecialchars($item['code'] . ' ' . $item['label'], ENT_QUOTES, 'UTF-8')); ?>">
                                            <input type="checkbox" 
                                                   name="checklist_items[<?= (int) $item['id']; ?>]" 
                                                   value="<?= (int) $item['id']; ?>"
                                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <div class="flex-1">
                                                <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span class="text-sm text-gray-600 ml-2"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <svg class="h-12 w-12 mx-auto mb-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p>Aucun élément de checklist disponible</p>
                            <p class="text-sm mt-2">Veuillez contacter l'administrateur pour configurer la checklist.</p>
                        </div>
                    <?php endif; ?>
                    

                </form>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery.js"></script>
<script>
    // Make data available to JavaScript
    const oilTypesData = <?= json_encode($oilTypes); ?>;
    const filterTypesData = <?= json_encode($filterTypes); ?>;
    const liquidesData = <?= json_encode($liquides); ?>;
    const busesData = <?= json_encode($buses); ?>;
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const vehicleTemplate = document.getElementById('vehicle-template');
        const vehiclesContainer = document.getElementById('vehicles-container');
        const addVehicleBtn = document.getElementById('add-vehicle');
        let vehicleIndex = 0;

        // Track operation indices for each vehicle and compartment
        const operationIndices = {};

        const borderColors = [
            'border-t-blue-600',
            'border-t-indigo-600',
            'border-t-purple-600',
            'border-t-pink-600',
            'border-t-rose-600',
            'border-t-orange-600',
            'border-t-amber-600',
            'border-t-emerald-600',
            'border-t-teal-600',
            'border-t-cyan-600'
        ];

        function addVehicleBlock(preset) {
            const clone = vehicleTemplate.content.cloneNode(true);
            const blockElement = clone.querySelector('.vehicle-block');
            
            // Remove the default color and add a dynamic one
            blockElement.classList.remove('border-t-blue-600');
            const colorClass = borderColors[vehicleIndex % borderColors.length];
            blockElement.classList.add(colorClass);

            const html = blockElement.outerHTML.replace(/__INDEX__/g, vehicleIndex).replace(/__VEHICLE_INDEX__/g, vehicleIndex);
            vehicleIndex += 1;
            
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            const block = wrapper.firstElementChild;
            
            // Add to DOM first to get correct position
            vehiclesContainer.appendChild(block);
            
            // Get the actual vehicle index based on DOM position
            const actualVehicleIndex = Array.from(vehiclesContainer.children).indexOf(block);
            
            // Initialize operation indices for this vehicle using actual index
            operationIndices[actualVehicleIndex] = {};

            // Initialize operation indices for each compartment in this vehicle
            block.querySelectorAll('.compartment-section').forEach(section => {
                const compartmentId = section.dataset.compartmentId;
                operationIndices[actualVehicleIndex][compartmentId] = 0;
            });

            // Handle vehicle select changes - add gray background when selected
            const vehicleSelect = block.querySelector('select[name*="[bus]"]');
            if (vehicleSelect) {
                // Check initial value
                if (vehicleSelect.value && vehicleSelect.value !== '') {
                    vehicleSelect.style.backgroundColor = '#f3f4f6';
                }
                
                // Handle change event
                vehicleSelect.addEventListener('change', function() {
                    if (this.value && this.value !== '') {
                        this.style.backgroundColor = '#f3f4f6';
                        
                        // Auto-select oil types for active compartments
                        const busId = this.value;
                        const bus = busesData.find(b => b.id_bus == busId);
                        
                        if (bus) {
                            block.querySelectorAll('.compartment-section.active').forEach(section => {
                                const compartmentTitle = section.querySelector('.compartment-title').textContent.trim();
                                const compartmentName = compartmentTitle.replace('Compartiment ', '');
                                const oilSelect = section.querySelector('.oil-type-select');
                                
                                if (oilSelect) {
                                    applyDefaultOil(oilSelect, compartmentName, bus);
                                }
                            });
                        }
                    } else {
                        this.style.backgroundColor = '#ffffff';
                    }
                });
            }

            function applyDefaultOil(select, compartmentName, bus) {
                let defaultOilId = null;
                if (compartmentName === 'Moteur') defaultOilId = bus.huile_moteur;
                else if (compartmentName === 'Boite Vitesse') defaultOilId = bus.huile_boite_vitesse;
                else if (compartmentName === 'Pont') defaultOilId = bus.huile_pont;
                
                if (defaultOilId) {
                    select.value = defaultOilId;
                }
            }

            // Handle compartment checkbox changes for this vehicle
            block.querySelectorAll('.compartment-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const section = this.closest('.compartment-section');
                    const operationsContainer = section.querySelector('.operations-container');
                    
                    if (this.checked) {
                        section.classList.add('active');
                        operationsContainer.classList.add('show');
                        
                        // Extract compartment name properly (remove "Compartiment " prefix)
                        const compartmentTitle = section.querySelector('.compartment-title').textContent.trim();
                        const compartmentName = compartmentTitle.replace('Compartiment ', '');
                        
                        // Filter oil types
                        section.querySelectorAll('.oil-type-select').forEach(select => {
                            filterOilTypes(select, compartmentName);
                            
                            // Auto-select based on vehicle
                            const vehicleSelect = block.querySelector('select[name*="[bus]"]');
                            if (vehicleSelect && vehicleSelect.value) {
                                const bus = busesData.find(b => b.id_bus == vehicleSelect.value);
                                if (bus) {
                                    applyDefaultOil(select, compartmentName, bus);
                                }
                            }
                        });
                        
                        // Filter filter types
                        section.querySelectorAll('.filter-type-select').forEach(select => {
                            filterFilterTypes(select, compartmentName);
                        });
                    } else {
                        section.classList.remove('active');
                        operationsContainer.classList.remove('show');
                    }
                });
            });

            // Handle oil operation changes for this vehicle
            block.querySelectorAll('select[name*="[oil_operation]"]').forEach(select => {
                select.addEventListener('change', function() {
                    const operationForm = this.closest('.operation-form');
                    const quantityInput = operationForm.querySelector('input[name*="[quantity]"]');
                    
                    if (this.value === 'Controle') {
                        quantityInput.value = '0';
                        quantityInput.readOnly = true;
                    } else {
                        quantityInput.readOnly = false;
                    }
                });
            });

            // Handle operation tab switching for this vehicle
            block.querySelectorAll('.operation-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const container = this.closest('.operations-container');
                    const tabName = this.dataset.tab;
                    
                    // Remove active class from all tabs and contents in this container
                    container.querySelectorAll('.operation-tab').forEach(t => t.classList.remove('active'));
                    container.querySelectorAll('.operation-content').forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    container.querySelector(`[data-content="${tabName}"]`).classList.add('active');
                });
            });

            // Handle adding operations for this vehicle
            block.querySelectorAll('.add-operation-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const operationForm = this.closest('.operation-form');
                    const operationsContainer = this.closest('.operations-container');
                    const compartmentSection = operationsContainer.closest('.compartment-section');
                    const compartmentId = compartmentSection.dataset.compartmentId;
                    const vehicleBlock = operationsContainer.closest('.vehicle-block');
                    const vehicleIdx = Array.from(vehicleBlock.parentElement.children).indexOf(vehicleBlock);
                    const operationContent = this.closest('.operation-content');
                    const operationType = operationContent.dataset.content;
                    
                    // Get current operation index
                    const currentIndex = operationIndices[vehicleIdx][compartmentId]++;
                    
                    // Get form values
                    let operationData = {};
                    let operationHtml = '';
                    
                    if (operationType === 'huile') {
                        const oilOperation = operationForm.querySelector('select[name*="[oil_operation]"]').value;
                        const oilTypeId = operationForm.querySelector('select[name*="[oil_type_id]"]').value;
                        const quantity = operationForm.querySelector('input[name*="[quantity]"]').value;
                        
                        // For control operations, quantity is optional
                        if (!oilOperation || !oilTypeId || (!quantity && oilOperation !== 'Controle')) {
                            alert('Veuillez remplir tous les champs pour l\'intervention huile.');
                            return;
                        }
                        
                        const oilType = oilTypesData.find(ot => ot.id == oilTypeId);
                        
                        operationHtml = `
                            <div class="operation-item">
                                <div class="operation-info">
                                    <div class="operation-type">Huile - ${oilOperation}</div>
                                    <div class="operation-details">${oilType ? oilType.name : ''}${oilOperation !== 'Controle' ? ' - ' + quantity + 'L' : ''}</div>
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][type]" value="Huile">
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][oil_operation]" value="${oilOperation}">
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][oil_type_id]" value="${oilTypeId}">
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][quantity]" value="${quantity}">
                                </div>
                                <button type="button" class="remove-operation-btn">Retirer</button>
                            </div>
                        `;
                        
                        // Clear form
                        operationForm.querySelector('select[name*="[oil_operation]"]').value = '';
                        operationForm.querySelector('select[name*="[oil_type_id]"]').value = '';
                        operationForm.querySelector('input[name*="[quantity]"]').value = '';
                        
                    } else if (operationType === 'filter') {
                        const filterTypeId = operationForm.querySelector('select[name*="[filter_type_id]"]').value;
                        const filterOperation = operationForm.querySelector('select[name*="[filter_operation]"]').value;
                        
                        if (!filterTypeId || !filterOperation) {
                            alert('Veuillez remplir tous les champs pour l\'intervention filtre.');
                            return;
                        }
                        
                        const filterType = filterTypesData.find(ft => ft.id == filterTypeId);
                        
                        operationHtml = `
                            <div class="operation-item">
                                <div class="operation-info">
                                    <div class="operation-type">Filtre - ${filterOperation}</div>
                                    <div class="operation-details">${filterType ? filterType.name : ''}</div>
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][type]" value="Filter">
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][filter_type_id]" value="${filterTypeId}">
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][filter_operation]" value="${filterOperation}">
                                </div>
                                <button type="button" class="remove-operation-btn">Retirer</button>
                            </div>
                        `;
                        
                        // Clear form
                        operationForm.querySelector('select[name*="[filter_type_id]"]').value = '';
                        operationForm.querySelector('select[name*="[filter_operation]"]').value = '';
                    } else if (operationType === 'liquide') {
                        const oilOperation = operationForm.querySelector('select[name*="[oil_operation]"]').value;
                        const liquideTypeId = operationForm.querySelector('select[name*="[liquide_type_id]"]').value;
                        const quantity = operationForm.querySelector('input[name*="[quantity]"]').value;
                        
                        // For control operations, quantity is optional
                        if (!oilOperation || !liquideTypeId || (!quantity && oilOperation !== 'Controle')) {
                            alert('Veuillez remplir tous les champs pour l\'intervention liquide.');
                            return;
                        }
                        
                        const liquideType = liquidesData.find(lt => lt.id == liquideTypeId);
                        
                        operationHtml = `
                            <div class="operation-item">
                                <div class="operation-info">
                                    <div class="operation-type">Liquide - ${oilOperation}</div>
                                    <div class="operation-details">${liquideType ? liquideType.name : ''}${oilOperation !== 'Controle' ? ' - ' + quantity + 'L' : ''}</div>
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][type]" value="Liquide">
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][oil_operation]" value="${oilOperation}">
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][liquide_type_id]" value="${liquideTypeId}">
                                    <input type="hidden" name="vehicles[${vehicleIdx}][operations][${compartmentId}][${currentIndex}][quantity]" value="${quantity}">
                                </div>
                                <button type="button" class="remove-operation-btn">Retirer</button>
                            </div>
                        `;
                        
                        // Clear form
                        operationForm.querySelector('select[name*="[oil_operation]"]').value = '';
                        operationForm.querySelector('select[name*="[liquide_type_id]"]').value = '';
                        operationForm.querySelector('input[name*="[quantity]"]').value = '';
                    }
                    
                    // Add operation to the list
                    const operationsList = operationsContainer.querySelector('.operations-list');
                    const opWrapper = document.createElement('div');
                    opWrapper.innerHTML = operationHtml;
                    const operationItem = opWrapper.firstElementChild;
                    
                    // Add remove functionality
                    const removeBtn = operationItem.querySelector('.remove-operation-btn');
                    removeBtn.addEventListener('click', function() {
                        operationItem.remove();
                    });
                    
                    operationsList.appendChild(operationItem);
                });
            });

            // Handle toggle operations button for this vehicle
            const toggleOperationsBtn = block.querySelector('.toggle-operations');
            const operationsSection = block.querySelector('.operations-section');
            
            toggleOperationsBtn.addEventListener('click', function() {
                operationsSection.classList.toggle('hidden');
                
                // Update button text and icon
                if (operationsSection.classList.contains('hidden')) {
                    this.innerHTML = `
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14" />
                            <path d="M5 12h14" />
                        </svg>
                        Ajouter intervention
                    `;
                } else {
                    this.innerHTML = `
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                        Masquer intervention
                    `;
                    
                    // Populate all oil type and filter type dropdowns when operations section is shown
                    operationsSection.querySelectorAll('.oil-type-select').forEach(select => {
                        // Initially show all oil types
                        filterOilTypes(select, null);
                    });
                    
                    operationsSection.querySelectorAll('.filter-type-select').forEach(select => {
                        // Initially show all filter types
                        filterFilterTypes(select, null);
                    });
                }
            });

            // Handle remove vehicle button
            const removeVehicleBtn = block.querySelector('.remove-vehicle');
            removeVehicleBtn.addEventListener('click', function () {
                block.remove();
            });
        }

        // Filter oil types based on compartment
        function filterOilTypes(select, compartmentName) {
            const currentValue = select.value;
            
            // Clear existing options but keep the "Choisir" option
            while (select.options.length > 1) {
                select.remove(1);
            }
            
            // If no compartment name provided, show all oil types
            if (!compartmentName) {
                oilTypesData.forEach(oilType => {
                    const option = document.createElement('option');
                    option.value = oilType.id;
                    option.textContent = oilType.name + ' (' + (oilType.usageOil || 'N/A') + ')';
                    option.dataset.usage = oilType.usageOil;
                    select.add(option);
                });
                return;
            }
            
            let foundMatch = false;
            oilTypesData.forEach(oilType => {
                // Only show oil types that exactly match the compartment usageOil
                if (oilType.usageOil === compartmentName) {
                    const option = document.createElement('option');
                    option.value = oilType.id;
                    option.textContent = oilType.name;
                    option.dataset.usage = oilType.usageOil;
                    select.add(option);
                    foundMatch = true;
                }
            });
            
            // If no matches found, show "No oil types available" message
            if (!foundMatch) {
                const option = document.createElement('option');
                option.value = "";
                option.textContent = "Aucun type d'huile disponible";
                option.disabled = true;
                select.add(option);
            }
            
            // Restore previous selection if still valid
            if (currentValue) {
                select.value = currentValue;
            }
        }

        // Filter filter types based on compartment
        function filterFilterTypes(select, compartmentName) {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Choisir</option>';
            
            // If no compartment name provided, show all filter types
            if (!compartmentName) {
                filterTypesData.forEach(filterType => {
                    const option = document.createElement('option');
                    option.value = filterType.id;
                    option.textContent = filterType.name + ' (' + (filterType.usageFilter || 'N/A') + ')';
                    option.dataset.usage = filterType.usageFilter;
                    select.add(option);
                });
                return;
            }
            
            let foundMatch = false;
            filterTypesData.forEach(filterType => {
                // Only show filter types that exactly match the compartment usageFilter
                if (filterType.usageFilter === compartmentName) {
                    const option = document.createElement('option');
                    option.value = filterType.id;
                    option.textContent = filterType.name;
                    option.dataset.usage = filterType.usageFilter;
                    select.add(option);
                    foundMatch = true;
                }
            });
            
            // If no matches found, show "No filter types available" message
            if (!foundMatch) {
                const option = document.createElement('option');
                option.value = "";
                option.textContent = "Aucun type de filtre disponible";
                option.disabled = true;
                select.add(option);
            }
            
            // Restore previous selection if still valid
            if (currentValue) {
                select.value = currentValue;
            }
        }

        // Handle remove operation buttons (global event delegation)
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-operation-btn')) {
                e.target.closest('.operation-item').remove();
            }
        });

        // Add vehicle button
        addVehicleBtn.addEventListener('click', function () {
            addVehicleBlock();
        });


        // Checklist modal functionality
        const checklistModal = document.getElementById('checklist-modal');
        const checklistForm = document.getElementById('checklist-form');
        const checklistVehicleIndex = document.getElementById('checklist-vehicle-index');
        
        // Store checklist data for each vehicle
        const vehicleChecklists = {};
        
        // Handle checklist button clicks
        document.addEventListener('click', function(e) {
            if (e.target.closest('.toggle-checklist')) {
                const vehicleBlock = e.target.closest('.vehicle-block');
                const vehicleIdx = Array.from(vehicleBlock.parentElement.children).indexOf(vehicleBlock);
                
                // Set the vehicle index in the modal form
                checklistVehicleIndex.value = vehicleIdx;
                
                // Clear search input and filter
                const searchInput = document.getElementById('checklist-search');
                const filterCheckbox = document.getElementById('checklist-filter-checked');
                searchInput.value = '';
                filterCheckbox.checked = false;
                
                // Trigger input event to reset visibility
                searchInput.dispatchEvent(new Event('input'));
                
                // Load existing checklist data for this vehicle if any
                loadChecklistData(vehicleIdx);
                
                // Show the modal
                checklistModal.classList.remove('hidden');
            }
        });
        
        // Handle modal close buttons
        document.getElementById('close-checklist-modal').addEventListener('click', function() {
            checklistModal.classList.add('hidden');
        });
        
        document.getElementById('cancel-checklist-top').addEventListener('click', function() {
            checklistModal.classList.add('hidden');
        });
        
        // Close modal when clicking outside
        checklistModal.addEventListener('click', function(e) {
            if (e.target === checklistModal) {
                checklistModal.classList.add('hidden');
            }
        });
        
        // Checklist search and filter functionality
        function updateChecklistVisibility() {
            const searchTerm = document.getElementById('checklist-search').value.toLowerCase();
            const showCheckedOnly = document.getElementById('checklist-filter-checked').checked;
            const container = document.getElementById('checklist-items-container');
            const sections = container.querySelectorAll('.checklist-section');
            
            sections.forEach(section => {
                const sectionItems = section.querySelectorAll('.checklist-item');
                let sectionHasVisibleItems = false;
                
                sectionItems.forEach(item => {
                    const searchText = item.dataset.searchText;
                    const isChecked = item.querySelector('input[type="checkbox"]').checked;
                    
                    const matchesSearch = searchText.includes(searchTerm);
                    const matchesFilter = !showCheckedOnly || isChecked;
                    
                    if (matchesSearch && matchesFilter) {
                        item.style.display = 'flex';
                        sectionHasVisibleItems = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Hide section header if no items match
                const sectionHeader = section.querySelector('h4');
                if (sectionHasVisibleItems) {
                    sectionHeader.style.display = 'flex';
                    section.style.display = 'block';
                } else {
                    sectionHeader.style.display = 'none';
                    section.style.display = 'none';
                }
            });
            
            // Show "no results" message if no items match
            const hasVisibleItems = Array.from(sections).some(section => 
                section.style.display !== 'none'
            );
            
            let noResultsMsg = container.querySelector('.no-results');
            if (!hasVisibleItems) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'no-results text-center py-8 text-gray-500';
                    noResultsMsg.innerHTML = `
                        <svg class="h-12 w-12 mx-auto mb-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            <path d="M10 11h4"/>
                        </svg>
                        <p>Aucun élément trouvé</p>
                    `;
                    container.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }

        document.getElementById('checklist-search').addEventListener('input', updateChecklistVisibility);
        document.getElementById('checklist-filter-checked').addEventListener('change', updateChecklistVisibility);
        
        // Handle checklist form submission
        checklistForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const vehicleIdx = parseInt(checklistVehicleIndex.value);
            const formData = new FormData(this);
            const checklistData = [];
            
            // Collect checked items
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('checklist_items[')) {
                    const itemId = parseInt(value);
                    checklistData.push(itemId);
                }
            }
            
            // Store checklist data for this vehicle
            vehicleChecklists[vehicleIdx] = checklistData;
            
            // Update the button count display
            updateChecklistButtonCount(vehicleIdx);
            
            // Close modal
            checklistModal.classList.add('hidden');
            
            // Show success message
            showNotification('Checklist enregistrée avec succès', 'success');
        });
        
        // Update checklist button count display
        function updateChecklistButtonCount(vehicleIdx) {
            const checklistData = vehicleChecklists[vehicleIdx] || [];
            const count = checklistData.length;
            
            // Find the checklist button for this vehicle
            const vehicleBlocks = document.querySelectorAll('.vehicle-block');
            const vehicleBlock = vehicleBlocks[vehicleIdx];
            
            if (vehicleBlock) {
                const countSpan = vehicleBlock.querySelector('.checklist-count');
                if (countSpan) {
                    countSpan.textContent = `(${count})`;
                }
            }
        }
        
        // Load checklist data for a vehicle
        function loadChecklistData(vehicleIdx) {
            const checklistData = vehicleChecklists[vehicleIdx] || [];
            
            // Reset all checkboxes
            checklistForm.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check items that were previously selected
            checklistData.forEach(itemId => {
                const checkbox = checklistForm.querySelector(`input[value="${itemId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' : 'bg-blue-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Modify form validation to include checklist data
        document.querySelector('form').addEventListener('submit', function(e) {
            const vehicleBlocks = document.querySelectorAll('.vehicle-block');
            
            if (vehicleBlocks.length === 0) {
                e.preventDefault();
                alert('Veuillez ajouter au moins un véhicule.');
                return false;
            }
            
            let hasValidVehicle = false;
            vehicleBlocks.forEach((block, index) => {
                const busSelect = block.querySelector('select[name*="[bus]"]');
                const indexInput = block.querySelector('input[name*="[index]"]');
                const selectedCompartments = block.querySelectorAll('.compartment-checkbox:checked');
                
                if (busSelect.value && indexInput.value && selectedCompartments.length > 0) {
                    hasValidVehicle = true;
                }
            });
            
            if (!hasValidVehicle) {
                e.preventDefault();
                alert('Chaque véhicule doit avoir un véhicule, un index kélometrique et au moins un compartiment sélectionné.');
                return false;
            }
            
            let allVehiclesHaveOperations = true;
            let missingOpsVehicle = null;
            
            vehicleBlocks.forEach((block, index) => {
                const operations = block.querySelectorAll('.operation-item');
                if (operations.length === 0) {
                    allVehiclesHaveOperations = false;
                    const busSelect = block.querySelector('select[name*="[bus]"]');
                    const busName = busSelect.value ? busSelect.options[busSelect.selectedIndex].text : `n°${index + 1}`;
                    missingOpsVehicle = busName;
                }
            });
            
            if (!allVehiclesHaveOperations) {
                e.preventDefault();
                alert(`Veuillez ajouter au moins une intervention pour le véhicule ${missingOpsVehicle}.`);
                return false;
            }
            
            // Add checklist data to hidden inputs before form submission
            Object.keys(vehicleChecklists).forEach(vehicleIdx => {
                const checklistData = vehicleChecklists[vehicleIdx];
                checklistData.forEach(itemId => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `vehicle_checklist[${vehicleIdx}][${itemId}]`;
                    hiddenInput.value = itemId;
                    this.appendChild(hiddenInput);
                });
            });
        });

        // Add initial vehicle block
        addVehicleBlock();

        // Floating add vehicle button functionality
        const floatingAddVehicleBtn = document.getElementById('floating-add-vehicle');
        const vehiclesSectionContainer = document.querySelector('#vehicles-container');
        
        if (!floatingAddVehicleBtn || !vehiclesSectionContainer) {
            console.error('Floating button or vehicles container not found');
            return;
        }

        // Handle floating button click
        floatingAddVehicleBtn.addEventListener('click', function () {
            addVehicleBlock();
            // Smooth scroll to the new vehicle
            const newVehicle = vehiclesSectionContainer.lastElementChild;
            newVehicle.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        // Handle existing vehicle selects on page load
        document.querySelectorAll('select[name*="[bus]"]').forEach(select => {
            if (select.value && select.value !== '') {
                select.style.backgroundColor = '#f3f4f6';
            }
            select.addEventListener('change', function() {
                if (this.value && this.value !== '') {
                    this.style.backgroundColor = '#f3f4f6';
                } else {
                    this.style.backgroundColor = '#ffffff';
                }
            });
        });
    });
</script>
</body>
</html>
