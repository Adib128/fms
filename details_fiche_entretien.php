<?php
require 'header.php';

// Get fiche ID from URL early so it's available in the header
$id_fiche = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Détails Fiche d'entretien</h1>
                    </div>
                    <div class="flex items-center space-x-3">
                        <?php if ($id_fiche > 0) : ?>
                        <a href="/modifier-document?id=<?= (int)$id_fiche ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z" />
                            </svg>
                            Modifier fiche
                        </a>
                        <?php endif; ?>
                        <button onclick="openAddVehicleModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Ajouter un véhicule
                        </button>
                        <a href="/liste-fiche-entretien" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Retour à la liste
                        </a>
                    </div>
                </div>
            </div>

            <?php
            // Validate fiche ID (already retrieved above)
            if ($id_fiche === 0) {
                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>ID de fiche d'entretien invalide</div>";
                echo "<script>setTimeout(() => window.location.replace('/liste-fiche-entretien'), 2000)</script>";
                exit;
            }
            
            // Get fiche details with station information
            $fiche_query = $db->prepare("SELECT f.*, s.lib AS station_lib FROM fiche_entretien f LEFT JOIN station s ON s.id_station = f.id_station WHERE f.id_fiche = ?");
            $fiche_query->execute([$id_fiche]);
            $fiche = $fiche_query->fetch(PDO::FETCH_ASSOC);
            
            if (!$fiche) {
                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>Fiche d'entretien non trouvée</div>";
                echo "<script>setTimeout(() => window.location.replace('/liste-fiche-entretien'), 2000)</script>";
                exit;
            }
            
            // Get maintenance records for this fiche
            $records_query = $db->prepare("SELECT mr.*, b.matricule_interne, b.type as bus_type FROM maintenance_records mr INNER JOIN bus b ON mr.id_bus = b.id_bus WHERE mr.fiche_id = ? ORDER BY b.matricule_interne");
            $records_query->execute([$id_fiche]);
            $records = $records_query->fetchAll(PDO::FETCH_ASSOC);
            
            // Get operations for all records
            $operations_query = $db->prepare("SELECT mo.*, c.name as compartiment_name, ot.name as oil_type_name, ft.name as filter_type_name, l.name as liquide_type_name FROM maintenance_operations mo LEFT JOIN compartiments c ON mo.compartiment_id = c.id LEFT JOIN oil_types ot ON mo.oil_type_id = ot.id LEFT JOIN filter_types ft ON mo.filter_type_id = ft.id LEFT JOIN liquides l ON mo.liquide_type_id = l.id WHERE mo.record_id IN (SELECT id FROM maintenance_records WHERE fiche_id = ?) ORDER BY mo.record_id, c.name");
            $operations_query->execute([$id_fiche]);
            $operations = $operations_query->fetchAll(PDO::FETCH_ASSOC);
            
            // Alternative calculation - count directly from database
            $count_query = $db->prepare("SELECT COUNT(*) as total_ops FROM maintenance_operations mo WHERE mo.record_id IN (SELECT id FROM maintenance_records WHERE fiche_id = ?)");
            $count_query->execute([$id_fiche]);
            $direct_count = $count_query->fetch(PDO::FETCH_ASSOC)['total_ops'];
            
            // Group operations by record_id
            $operations_by_record = [];
            foreach ($operations as $operation) {
                $operations_by_record[$operation['record_id']][] = $operation;
            }

            // Load reference data for modals
            $buses = [];
            $compartiments = [];
            $oilTypes = [];
            $filterTypes = [];
            $checklistItems = [];

            try {
                $busStmt = $db->query('SELECT id_bus, matricule_interne, huile_moteur, huile_boite_vitesse, huile_pont FROM bus ORDER BY matricule_interne ASC');
                $buses = $busStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $buses = [];
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
                $compartiments = [];
            }

            try {
                $oilTypeStmt = $db->query('SELECT id, name, usageOil FROM oil_types ORDER BY name ASC');
                $oilTypes = $oilTypeStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $oilTypes = [];
            }

            try {
                $filterTypeStmt = $db->query('SELECT id, name, usageFilter FROM filter_types ORDER BY name ASC');
                $filterTypes = $filterTypeStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $filterTypes = [];
            }

            try {
                $liquidesStmt = $db->query('SELECT id, name FROM liquides ORDER BY name ASC');
                $liquides = $liquidesStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $liquides = [];
            }

            // Load checklist items
            try {
                $checklistStmt = $db->query('SELECT id, code, label, parti FROM checklist_items ORDER BY parti, code ASC');
                $checklistItems = $checklistStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $checklistItems = [];
            }

            // Load existing checklist data for this fiche
            $checklistData = [];
            try {
                // Get all checklist items first
                $allChecklistItemsStmt = $db->query('SELECT id, code, label, parti FROM checklist_items ORDER BY parti, code ASC');
                $allChecklistItems = $allChecklistItemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get checked items for this fiche
                $checklistStatusStmt = $db->prepare("
                    SELECT id_bus, checklist_item_id 
                    FROM maintenance_checklist_status 
                    WHERE id_fiche = ? AND is_checked = 1
                ");
                $checklistStatusStmt->execute([$id_fiche]);
                $checkedItems = $checklistStatusStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Debug: Output the retrieved data
                error_log("=== DEBUG INFO ===");
                error_log("Fiche ID: $id_fiche");
                error_log("Records in this fiche: " . print_r($records, true));
                error_log("All checklist items: " . print_r($allChecklistItems, true));
                error_log("Checked items from database: " . print_r($checkedItems, true));
                
                // Create a lookup for checked items by bus_id and item_id
                $checkedLookup = [];
                foreach ($checkedItems as $item) {
                    $busId = $item['id_bus'];
                    $itemId = $item['checklist_item_id'];
                    if (!isset($checkedLookup[$busId])) {
                        $checkedLookup[$busId] = [];
                    }
                    $checkedLookup[$busId][$itemId] = true;
                }
                
                error_log("Checked lookup: " . print_r($checkedLookup, true));
                
                // Initialize data structure for each bus
                foreach ($records as $record) {
                    $busId = $record['id_bus'];
                    $checklistData[$busId] = ['checked' => [], 'unchecked' => []];
                    
                    // Check each checklist item for this bus
                    foreach ($allChecklistItems as $item) {
                        $itemId = $item['id'];
                        
                        // Check if this item exists in maintenance_checklist_status for this bus and fiche
                        if (isset($checkedLookup[$busId]) && isset($checkedLookup[$busId][$itemId])) {
                            $checklistData[$busId]['checked'][] = $itemId;
                            error_log("Item $itemId is CHECKED for bus $busId");
                        } else {
                            $checklistData[$busId]['unchecked'][] = $itemId;
                            error_log("Item $itemId is UNCHECKED for bus $busId");
                        }
                    }
                    
                    error_log("Final data for bus $busId: " . print_r($checklistData[$busId], true));
                }
                
                error_log("Final checklist data: " . print_r($checklistData, true));
                error_log("=== END DEBUG ===");
                
            } catch (PDOException $e) {
                error_log("Error loading checklist data: " . $e->getMessage());
                $checklistData = [];
            }

?>

            <!-- Main Content Card -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <!-- Fiche Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-white">Fiche N° <?= htmlspecialchars($fiche['numd_doc'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="text-blue-100"><?= date('d/m/Y', strtotime($fiche['date'])) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-blue-100 text-sm">Atelier</p>
                            <p class="text-white font-medium"><?= htmlspecialchars($fiche['station_lib'] ?: 'Non spécifiée', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Vehicles and Operations -->
                <div class="p-6">
                    <?php if (!empty($records)): ?>
                        <!-- Search by Vehicle -->
                        <div class="mb-4">
                            <label for="vehicle-search" class="block text-sm font-medium text-gray-700 mb-2">Rechercher par véhicule</label>
                            <input 
                                type="text" 
                                id="vehicle-search" 
                                placeholder="Rechercher un véhicule..." 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>
                    <?php endif; ?>
                    <?php if (empty($records)): ?>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="mt-2 text-gray-500">Aucun véhicule trouvé pour cette fiche</p>
                            <button onclick="openAddVehicleModal()" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                Ajouter un véhicule
                            </button>
                        </div>
                    <?php else: ?>
                        <?php 
                        $borderColors = [
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
                        $colorIndex = 0;
                        ?>
                        <?php foreach ($records as $record): 
                            $borderColor = $borderColors[$colorIndex % count($borderColors)];
                            $colorIndex++;
                        ?>
                            <div class="vehicle-item mb-6 border border-gray-200 border-t-4 <?= $borderColor ?> rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200" data-vehicle="<?= htmlspecialchars(strtolower($record['matricule_interne']), ENT_QUOTES, 'UTF-8') ?>">
                                <!-- Vehicle Header -->
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                            </svg>
                                            <div>
                                                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($record['matricule_interne'], ENT_QUOTES, 'UTF-8') ?></h3>
                                                <p class="text-sm text-gray-500"><?= htmlspecialchars($record['bus_type'], ENT_QUOTES, 'UTF-8') ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-500">Index Kilométrique</p>
                                            <p class="font-medium text-gray-900"><?= $record['index_km'] ?></p>
                                        </div>
                                        <button onclick="openAddOperationModal(<?= (int) $record['id_bus'] ?>, <?= (int) $record['id'] ?>)" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700">
                                            <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 5v14M5 12h14"/>
                                            </svg>
                                            Ajouter intervention
                                        </button>
                                        <button onclick="openUpdateVehicleModal(<?= (int) $record['id'] ?>, <?= (int) $record['id_bus'] ?>, <?= (int) $record['index_km'] ?>)" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700">
                                            <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z" />
                                            </svg>
                                            Modifier véhicule
                                        </button>
                                        <button type="button" class="toggle-checklist inline-flex items-center gap-2 rounded-lg border border-blue-500 px-3 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" data-bus-id="<?= (int) $record['id_bus'] ?>" data-record-id="<?= (int) $record['id'] ?>">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M9 11l3 3L22 4" />
                                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
                                            </svg>
                                            Checklist <span class="checklist-count">(<?= count($checklistData[$record['id_bus']]['checked'] ?? []) ?>)</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Operations -->
                                <div class="p-4">
                                    <?php 
                                    $record_operations = $operations_by_record[$record['id']] ?? [];
                                    if (empty($record_operations)): 
                                    ?>
                                        <div class="text-center py-4 text-gray-500">
                                            Aucune intervention enregistrée pour ce véhicule
                                            <button onclick="openAddOperationModal(<?= (int) $record['id_bus'] ?>, <?= (int) $record['id'] ?>)" class="ml-2 text-blue-600 hover:text-blue-800 underline">
                                                Ajouter une intervention
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <?php 
                                        // Group operations by compartment, then by type
                                        $grouped_operations = [];
                                        foreach ($record_operations as $operation) {
                                            $compartment = $operation['compartiment_name'];
                                            $type = $operation['type'];
                                            if (!isset($grouped_operations[$compartment])) {
                                                $grouped_operations[$compartment] = [];
                                            }
                                            if (!isset($grouped_operations[$compartment][$type])) {
                                                $grouped_operations[$compartment][$type] = [];
                                            }
                                            $grouped_operations[$compartment][$type][] = $operation;
                                        }
                                        ?>
                                        
                                        <div class="space-y-6">
                                            <?php foreach ($grouped_operations as $compartment_name => $compartment_operations): ?>
                                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                                    <!-- Compartment Header -->
                                                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                                        <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($compartment_name, ENT_QUOTES, 'UTF-8') ?></h3>
                                                    </div>
                                                    
                                                    <!-- Operations by Type -->
                                                    <div class="divide-y divide-gray-200">
                                                        <?php foreach ($compartment_operations as $type => $operations): ?>
                                                            <div class="p-4">
                                                                <!-- Type Header -->
                                                                <div class="flex items-center justify-between mb-3">
                                                                    <div class="flex items-center space-x-2">
                                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $type === 'Huile' ? 'bg-blue-100 text-blue-800' : ($type === 'Liquide' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800') ?>">
                                                                            <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                                                                        </span>
                                                                        <span class="text-sm text-gray-500"><?= count($operations) ?> intervention(s)</span>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Operations List -->
                                                                <div class="space-y-3">
                                                                    <?php foreach ($operations as $operation): ?>
                                                                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                                                            <div class="flex items-start justify-between">
                                                                                <div class="flex-1">
                                                                                    <?php if ($type === 'Huile'): ?>
                                                                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                                                                            <div>
                                                                                                <p class="text-gray-500 text-xs">Intervention</p>
                                                                                                <p class="font-medium"><?= htmlspecialchars($operation['oil_operation'] ?: 'Non spécifiée', ENT_QUOTES, 'UTF-8') ?></p>
                                                                                            </div>
                                                                                            <div>
                                                                                                <p class="text-gray-500 text-xs">Type d'huile</p>
                                                                                                <p class="font-medium"><?= htmlspecialchars($operation['oil_type_name'] ?: 'Non spécifié', ENT_QUOTES, 'UTF-8') ?></p>
                                                                                            </div>
                                                                                            <div>
                                                                                                <p class="text-gray-500 text-xs">Quantité</p>
                                                                                                <p class="font-medium"><?= $operation['quantity'] ? number_format($operation['quantity'], 2) . ' L' : 'Non spécifiée' ?></p>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php elseif ($type === 'Liquide'): ?>
                                                                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                                                                            <div>
                                                                                                <p class="text-gray-500 text-xs">Intervention</p>
                                                                                                <p class="font-medium"><?= htmlspecialchars($operation['oil_operation'] ?: 'Non spécifiée', ENT_QUOTES, 'UTF-8') ?></p>
                                                                                            </div>
                                                                                            <div>
                                                                                                <p class="text-gray-500 text-xs">Type de liquide</p>
                                                                                                <p class="font-medium"><?= htmlspecialchars($operation['liquide_type_name'] ?: 'Non spécifié', ENT_QUOTES, 'UTF-8') ?></p>
                                                                                            </div>
                                                                                            <div>
                                                                                                <p class="text-gray-500 text-xs">Quantité</p>
                                                                                                <p class="font-medium"><?= $operation['quantity'] ? number_format($operation['quantity'], 2) . ' L' : 'Non spécifiée' ?></p>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php else: // Filter ?>
                                                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                                                                            <div>
                                                                                                <p class="text-gray-500 text-xs">Intervention</p>
                                                                                                <p class="font-medium"><?= htmlspecialchars($operation['filter_operation'] ?: 'Non spécifiée', ENT_QUOTES, 'UTF-8') ?></p>
                                                                                            </div>
                                                                                            <div>
                                                                                                <p class="text-gray-500 text-xs">Type de filtre</p>
                                                                                                <p class="font-medium"><?= htmlspecialchars($operation['filter_type_name'] ?: 'Non spécifié', ENT_QUOTES, 'UTF-8') ?></p>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                                                    <button onclick="openUpdateOperationModal(<?= (int) $operation['id'] ?>)" class="text-blue-600 hover:text-blue-800" title="Modifier l'intervention">
                                                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                                            <path d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z" />
                                                                                        </svg>
                                                                                    </button>
                                                                                    <button onclick="deleteOperation(<?= (int) $operation['id'] ?>)" class="text-red-600 hover:text-red-800" title="Supprimer l'intervention">
                                                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                                        </svg>
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Véhicules -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-600 uppercase tracking-wide">Total Véhicules</p>
                            <p class="text-3xl font-bold text-blue-900 mt-1"><?= count($records) ?></p>
                            <p class="text-xs text-blue-600 mt-1">véhicules traités</p>
                        </div>
                        <div class="bg-blue-500 rounded-xl p-3 shadow-md">
                            <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12l0 7a2 2 0 01-2 2h-1v-1h-6v1H9a2 2 0 01-2-2V7zm0 0l-2-2m2 2l2-2m-2 2h12m-6 7v-2m0 0h-2m2 2h2m-2-2v2m-4 2h8a2 2 0 002-2v-7a2 2 0 00-2-2H6a2 2 0 00-2 2v7a2 2 0 002 2h4z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Interventions -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-600 uppercase tracking-wide">Total Interventions</p>
                            <p class="text-3xl font-bold text-green-900 mt-1"><?= $direct_count ?></p>
                            <p class="text-xs text-green-600 mt-1">interventions effectuées</p>
                        </div>
                        <div class="bg-green-500 rounded-xl p-3 shadow-md">
                            <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- Add Vehicle Modal -->
<div id="addVehicleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Ajouter un véhicule à la fiche</h3>
            <button onclick="closeAddVehicleModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="addVehicleForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Véhicule</label>
                    <select name="bus_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Choisir un véhicule</option>
                        <?php foreach ($buses as $bus): ?>
                            <option value="<?= (int) $bus['id_bus'] ?>"><?= htmlspecialchars($bus['matricule_interne'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Index kilométrique</label>
                    <input type="number" name="index_km" min="0" step="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="closeAddVehicleModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Ajouter le véhicule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Update Operation Modal -->
<div id="updateOperationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-lg bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Modifier une intervention</h3>
            <button onclick="closeUpdateOperationModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="updateOperationForm" class="space-y-6">
            <input type="hidden" name="operation_id" id="update_operation_id">
            <input type="hidden" name="record_id" id="update_record_id">
            <input type="hidden" name="bus_id" id="update_bus_id">
            <input type="hidden" name="index_km" id="update_index_km">
            
            <!-- Compartiment -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Compartiment *</label>
                <select name="compartiment_id" id="update_compartiment_id" required data-skip-tom-select="true" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choisir le compartiment</option>
                    <?php foreach ($compartiments as $compartiment): ?>
                        <option value="<?= (int) $compartiment['id'] ?>"><?= htmlspecialchars($compartiment['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type d'intervention *</label>
                <select name="type" id="update_type" required data-skip-tom-select="true" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choisir le type</option>
                    <option value="Huile">Huile</option>
                    <option value="Liquide">Liquide</option>
                    <option value="Filter">Filtre</option>
                </select>
            </div>
            
            <!-- Dynamic fields based on type -->
            <div id="updateDynamicFields" class="space-y-4">
                <!-- Fields will be populated by JavaScript -->
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="closeUpdateOperationModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Modifier l'intervention
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Update Vehicle Modal -->
<div id="updateVehicleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Modifier le véhicule</h3>
            <button onclick="closeUpdateVehicleModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="updateVehicleForm" class="space-y-4">
            <input type="hidden" name="record_id" id="update_vehicle_record_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Véhicule *</label>
                    <select name="bus_id" id="update_vehicle_bus_id" required data-skip-tom-select="true" class="w-full">
                        <option value="">Choisir un véhicule</option>
                        <?php foreach ($buses as $bus): ?>
                            <option value="<?= (int) $bus['id_bus'] ?>"><?= htmlspecialchars($bus['matricule_interne'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Index kilométrique *</label>
                    <input type="number" name="index_km" id="update_vehicle_index_km" min="0" step="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="closeUpdateVehicleModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Modifier le véhicule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Operation Modal -->
<div id="addOperationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-lg bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Ajouter une intervention</h3>
            <button onclick="closeAddOperationModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="addOperationForm" class="space-y-6">
            <input type="hidden" name="record_id" id="record_id">
            <input type="hidden" name="bus_id" id="bus_id">
            
            <!-- Compartments Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Compartiments</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <?php foreach ($compartiments as $compartiment): ?>
                        <label class="flex items-center">
                            <input type="checkbox" name="compartments[]" value="<?= (int) $compartiment['id'] ?>" class="mr-2">
                            <span class="text-sm"><?= htmlspecialchars($compartiment['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Operations will be added dynamically based on selected compartments -->
            <div id="operationsContainer" class="space-y-4">
                <!-- Operations forms will be added here -->
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="closeAddOperationModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Ajouter l'intervention
                </button>
            </div>
        </form>
    </div>
</div>

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
                    <input type="hidden" id="checklist-bus-id" name="bus_id">
                    
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

<style>
    .operations-container {
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
    .input--standard {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }
    .input--standard:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* TomSelect styling fixes */
    .ts-wrapper {
        z-index: 1;
    }
    .ts-wrapper.single .ts-control {
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }
    .ts-wrapper.single .ts-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .ts-wrapper.single .ts-control:hover {
        border-color: #9ca3af;
    }
    .ts-dropdown {
        z-index: 9999 !important;
        position: absolute !important;
        background: white !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        max-height: 200px !important;
        overflow-y: auto !important;
    }
    .ts-dropdown .option {
        padding: 0.5rem 1rem !important;
        cursor: pointer !important;
        border-bottom: 1px solid #f3f4f6 !important;
    }
    .ts-dropdown .option:hover {
        background-color: #f3f4f6 !important;
    }
    .ts-dropdown .option.selected {
        background-color: #dbeafe !important;
        color: #1e40af !important;
    }
    .ts-dropdown .create {
        padding: 0.5rem 1rem !important;
        background-color: #f9fafb !important;
        color: #6b7280 !important;
        border-top: 1px solid #e5e7eb !important;
    }
    .ts-dropdown .no-results {
        padding: 0.5rem 1rem !important;
        color: #6b7280 !important;
        text-align: center !important;
    }
</style>

<script>
// Data from PHP
const busesData = <?= json_encode($buses); ?>;
const compartimentsData = <?= json_encode($compartiments); ?>;
const oilTypesData = <?= json_encode($oilTypes); ?>;
const filterTypesData = <?= json_encode($filterTypes); ?>;
const liquidesData = <?= json_encode($liquides); ?>;
const currentFicheId = <?= (int) $id_fiche ?>;
const checklistItemsData = <?= json_encode($checklistItems); ?>;
const checklistDataByBus = <?= json_encode($checklistData) ?>;

// Debug: Show checklist data
console.log('=== PHP DATA DEBUG ===');
console.log('Current Fiche ID:', currentFicheId);
console.log('Checklist data by bus:', checklistDataByBus);
console.log('Checklist items available:', checklistItemsData);

// Test: Check if we have any checked items at all
let totalCheckedItems = 0;
Object.keys(checklistDataByBus).forEach(busId => {
    const busData = checklistDataByBus[busId];
    if (busData && busData.checked) {
        totalCheckedItems += busData.checked.length;
        console.log(`Bus ${busId} has ${busData.checked.length} checked items:`, busData.checked);
    }
});
console.log(`Total checked items across all buses: ${totalCheckedItems}`);
console.log('=== END PHP DATA DEBUG ===');

// Modal functions
let vehicleTomSelect = null;
let updateVehicleChoices = null;

function openAddVehicleModal() {
    document.getElementById('addVehicleModal').classList.remove('hidden');
    
    // Initialize TomSelect for vehicle selection
    setTimeout(() => {
        if (!vehicleTomSelect) {
            vehicleTomSelect = new TomSelect('select[name="bus_id"]', {
                create: false,
                maxOptions: 50,
                placeholder: 'Choisir un véhicule',
                searchPlaceholder: 'Rechercher un véhicule...',
                allowEmptyOption: false,
                plugins: ['dropdown_input']
            });
        }
    }, 100);
}

function closeAddVehicleModal() {
    document.getElementById('addVehicleModal').classList.add('hidden');
    document.getElementById('addVehicleForm').reset();
    
    // Destroy TomSelect instance
    if (vehicleTomSelect) {
        vehicleTomSelect.destroy();
        vehicleTomSelect = null;
    }
}

function openAddOperationModal(busId, recordId) {
    document.getElementById('record_id').value = recordId;
    document.getElementById('bus_id').value = busId;
    document.getElementById('addOperationModal').classList.remove('hidden');
}

function closeAddOperationModal() {
    document.getElementById('addOperationModal').classList.add('hidden');
    document.getElementById('addOperationForm').reset();
    document.getElementById('operationsContainer').innerHTML = '';
}

function openUpdateVehicleModal(recordId, busId, indexKm) {
    // Set hidden and input values FIRST
    document.getElementById('update_vehicle_record_id').value = recordId;
    
    const indexKmInput = document.getElementById('update_vehicle_index_km');
    if (indexKmInput) {
        indexKmInput.value = indexKm || '';
    }
    
    // Set the select value FIRST before showing modal
    const selectElement = document.getElementById('update_vehicle_bus_id');
    if (selectElement && busId) {
        const busValue = String(busId);
        selectElement.value = busValue;
        // Ensure the option is selected
        const option = selectElement.querySelector(`option[value="${busValue}"]`);
        if (option) {
            option.selected = true;
        }
    }
    
    document.getElementById('updateVehicleModal').classList.remove('hidden');
    
    // Ensure index_km value is set after modal is visible
    setTimeout(() => {
        const indexInput = document.getElementById('update_vehicle_index_km');
        if (indexInput) {
            indexInput.value = indexKm || '';
        }
    }, 50);
    
    // Initialize Choices.js AFTER value is set
    setTimeout(() => {
        const selectEl = document.getElementById('update_vehicle_bus_id');
        if (selectEl) {
            // Destroy existing instance if any
            if (updateVehicleChoices) {
                try {
                    updateVehicleChoices.destroy();
                } catch (e) {
                    console.warn('Error destroying Choices:', e);
                }
                updateVehicleChoices = null;
            }
            
            // Initialize Choices.js with search
            updateVehicleChoices = new Choices('#update_vehicle_bus_id', {
                searchEnabled: true,
                searchChoices: true,
                searchFields: ['label', 'value'],
                placeholder: true,
                placeholderValue: 'Choisir un véhicule',
                searchPlaceholderValue: 'Rechercher un véhicule...',
                removeItemButton: false,
                shouldSort: false,
                itemSelectText: ''
            });
            
            // Explicitly set the value after Choices is initialized
            if (busId) {
                setTimeout(() => {
                    if (updateVehicleChoices) {
                        updateVehicleChoices.setChoiceByValue(String(busId));
                    }
                }, 50);
            }
        }
    }, 150);
}


function closeUpdateVehicleModal() {
    document.getElementById('updateVehicleModal').classList.add('hidden');
    document.getElementById('updateVehicleForm').reset();
    
    // Destroy Choices.js instance
    if (updateVehicleChoices) {
        try {
            updateVehicleChoices.destroy();
        } catch (e) {
            console.warn('Error destroying Choices:', e);
        }
        updateVehicleChoices = null;
    }
}

function openUpdateOperationModal(operationId) {
    // Fetch operation data
    fetch(`/api/get_operation.php?id=${operationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.operation) {
                const op = data.operation;
                
                // Show modal first
                document.getElementById('updateOperationModal').classList.remove('hidden');
                
                // Wait for modal to be visible, then set values
                setTimeout(() => {
                    // Set hidden fields
                    document.getElementById('update_operation_id').value = op.id;
                    document.getElementById('update_record_id').value = op.record_id;
                    document.getElementById('update_bus_id').value = op.id_bus;
                    document.getElementById('update_index_km').value = op.index_km || ''; // Keep original index_km as hidden
                    
                    // Set Compartiment with current value
                    const compartimentSelect = document.getElementById('update_compartiment_id');
                    if (compartimentSelect && op.compartiment_id) {
                        const compValue = String(op.compartiment_id);
                        compartimentSelect.value = compValue;
                        const compOption = compartimentSelect.querySelector(`option[value="${compValue}"]`);
                        if (compOption) {
                            compOption.selected = true;
                        }
                    }
                    
                    // Set Type d'intervention by default with current value
                    const typeSelect = document.getElementById('update_type');
                    if (typeSelect && op.type) {
                        const typeValue = op.type;
                        typeSelect.value = typeValue;
                        
                        // Ensure option is selected
                        const typeOption = typeSelect.querySelector(`option[value="${typeValue}"]`);
                        if (typeOption) {
                            typeOption.selected = true;
                        }
                        
                        // Update dynamic fields based on type
                        updateOperationDynamicFields(typeValue, op);
                        
                        // Add event listener for type change after a short delay
                        setTimeout(() => {
                            // Get the select again in case it was recreated
                            const currentTypeSelect = document.getElementById('update_type');
                            if (currentTypeSelect) {
                                // Ensure value is still set after any DOM updates
                                currentTypeSelect.value = typeValue;
                                
                                // Remove existing listener if any by cloning
                                const newTypeSelect = currentTypeSelect.cloneNode(true);
                                currentTypeSelect.parentNode.replaceChild(newTypeSelect, currentTypeSelect);
                                
                                // Set the value again on the cloned element
                                newTypeSelect.value = typeValue;
                                
                                newTypeSelect.addEventListener('change', function() {
                                    const operationId = document.getElementById('update_operation_id').value;
                                    if (operationId) {
                                        // Fetch operation again to get current values
                                        fetch(`/api/get_operation.php?id=${operationId}`)
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success && data.operation) {
                                                    const op = data.operation;
                                                    op.type = this.value; // Update type
                                                    updateOperationDynamicFields(this.value, op);
                                                }
                                            });
                                    } else {
                                        updateOperationDynamicFields(this.value, {});
                                    }
                                });
                            }
                        }, 50);
                    }
                }, 150);
            } else {
                alert('Erreur: ' + (data.message || 'Impossible de charger l\'intervention'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors du chargement de l\'intervention');
        });
}

function closeUpdateOperationModal() {
    document.getElementById('updateOperationModal').classList.add('hidden');
    document.getElementById('updateOperationForm').reset();
    document.getElementById('updateDynamicFields').innerHTML = '';
}

function updateOperationDynamicFields(type, operation) {
    const container = document.getElementById('updateDynamicFields');
    let html = '';
    
    if (type === 'Huile') {
        html = `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Intervention *</label>
                <select name="oil_operation" id="update_oil_operation" required data-skip-tom-select="true" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choisir l'intervention</option>
                    <option value="Vidange" ${operation.oil_operation === 'Vidange' ? 'selected' : ''}>Vidange</option>
                    <option value="Apoint" ${operation.oil_operation === 'Apoint' ? 'selected' : ''}>Appoint</option>
                    <option value="Controle" ${operation.oil_operation === 'Controle' ? 'selected' : ''}>Contrôle</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type d'huile *</label>
                <select name="oil_type_id" id="update_oil_type_id" required data-skip-tom-select="true" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choisir le type d'huile</option>
                    ${oilTypesData.map(oil => `<option value="${oil.id}" ${operation.oil_type_id == oil.id ? 'selected' : ''}>${oil.name}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quantité (L)</label>
                <input type="number" step="0.01" name="quantity" id="update_quantity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00" value="${operation.quantity || ''}">
            </div>
        `;
    } else if (type === 'Liquide') {
        html = `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Intervention *</label>
                <select name="oil_operation" id="update_oil_operation" required data-skip-tom-select="true" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choisir l'intervention</option>
                    <option value="Apoint" ${operation.oil_operation === 'Apoint' ? 'selected' : ''}>Appoint</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type de liquide *</label>
                <select name="liquide_type_id" id="update_liquide_type_id" required data-skip-tom-select="true" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choisir le type de liquide</option>
                    ${liquidesData.map(liq => `<option value="${liq.id}" ${operation.liquide_type_id == liq.id ? 'selected' : ''}>${liq.name}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quantité (L) *</label>
                <input type="number" step="0.01" name="quantity" id="update_quantity" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00" value="${operation.quantity || ''}">
            </div>
        `;
    } else if (type === 'Filter') {
        html = `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type de filtre *</label>
                <select name="filter_type_id" id="update_filter_type_id" required data-skip-tom-select="true" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choisir le type de filtre</option>
                    ${filterTypesData.map(filter => `<option value="${filter.id}" ${operation.filter_type_id == filter.id ? 'selected' : ''}>${filter.name}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Intervention *</label>
                <select name="filter_operation" id="update_filter_operation" required data-skip-tom-select="true" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choisir l'intervention</option>
                    <option value="Nettoyage" ${operation.filter_operation === 'Nettoyage' ? 'selected' : ''}>Nettoyage</option>
                    <option value="Changement" ${operation.filter_operation === 'Changement' ? 'selected' : ''}>Changement</option>
                </select>
            </div>
        `;
    }
    
    container.innerHTML = html;
}


// Handle update operation form submission
document.getElementById('updateOperationForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        operation_id: formData.get('operation_id'),
        id_bus: formData.get('bus_id'), // Use hidden bus_id field instead of select
        index_km: formData.get('index_km'),
        compartiment_id: formData.get('compartiment_id'),
        type: formData.get('type'),
        oil_operation: formData.get('oil_operation'),
        oil_type_id: formData.get('oil_type_id'),
        liquide_type_id: formData.get('liquide_type_id'),
        quantity: formData.get('quantity'),
        filter_operation: formData.get('filter_operation'),
        filter_type_id: formData.get('filter_type_id')
    };
    
    fetch('/api/update_operation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeUpdateOperationModal();
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue lors de la modification de l\'intervention');
    });
});

// Checklist modal functions
function openChecklistModal(busId, recordId) {
    currentBusId = busId;
    currentRecordId = recordId;
    
    // Set the bus_id in the form - THIS WAS MISSING!
    document.getElementById('checklist-bus-id').value = busId;
    
    // Clear search input and filter
    const searchInput = document.getElementById('checklist-search');
    const filterCheckbox = document.getElementById('checklist-filter-checked');
    searchInput.value = '';
    filterCheckbox.checked = false;
    
    // Trigger input event to reset visibility
    searchInput.dispatchEvent(new Event('input'));
    
    // Load checklist data for this bus
    loadChecklistData(busId);
    
    // Update modal title
    const busInfo = busesData.find(bus => bus.id_bus === busId);
    if (busInfo) {
        document.getElementById('checklist-modal-title').textContent = `Checklist - ${busInfo.matricule_interne}`;
    }
    
    // Show modal
    document.getElementById('checklist-modal').classList.remove('hidden');
    console.log(`=== END OPENING CHECKLIST MODAL ===`);
    console.log('Set bus_id in form to:', busId);
}

function closeChecklistModal() {
    console.log('closeChecklistModal called');
    document.getElementById('checklist-modal').classList.add('hidden');
    console.log('Modal hidden');
}

// Test function to manually check checkbox selection
function testCheckboxSelection() {
    console.log('=== CHECKBOX TEST ===');
    
    // Wait for modal to be visible
    setTimeout(() => {
        const allCheckboxes = document.querySelectorAll('#checklist-form input[type="checkbox"]');
        console.log(`Found ${allCheckboxes.length} checkboxes in modal`);
        
        if (allCheckboxes.length > 0) {
            // Test checking the first checkbox
            const firstCheckbox = allCheckboxes[0];
            console.log(`Testing with first checkbox: value=${firstCheckbox.value}`);
            
            // Try to check it
            firstCheckbox.checked = true;
            console.log(`First checkbox checked state: ${firstCheckbox.checked}`);
            
            // Verify it's actually checked
            const checkedCount = document.querySelectorAll('#checklist-form input[type="checkbox"]:checked').length;
            console.log(`Actually checked checkboxes: ${checkedCount}`);
            
            // Uncheck it
            firstCheckbox.checked = false;
            console.log(`First checkbox unchecked state: ${firstCheckbox.checked}`);
        } else {
            console.log('No checkboxes found in modal!');
        }
        
        console.log('=== END CHECKBOX TEST ===');
    }, 500);
}

// Load checklist data for a bus - SIMPLE DIRECT APPROACH
function loadChecklistData(busId) {
    console.log(`=== SIMPLE CHECKLIST LOADING ===`);
    console.log(`Bus ID: ${busId}`);
    
    const busData = checklistDataByBus[busId] || { checked: [], unchecked: [] };
    const checkedItems = busData.checked || [];
    
    console.log(`Checked items:`, checkedItems);
    
    // Wait for modal to be visible, then check checkboxes
    setTimeout(() => {
        console.log('Setting checkboxes now...');
        
        // Get all checkboxes
        const checkboxes = document.querySelectorAll('#checklist-form input[type="checkbox"]');
        console.log(`Found ${checkboxes.length} checkboxes`);
        
        // Show all checkbox values for debugging
        checkboxes.forEach((cb, index) => {
            console.log(`Checkbox ${index}: value="${cb.value}", checked=${cb.checked}`);
        });
        
        // Set checkbox states directly
        checkboxes.forEach(checkbox => {
            const isChecked = checkedItems.includes(parseInt(checkbox.value));
            checkbox.checked = isChecked;
            console.log(`Set checkbox ${checkbox.value} to ${isChecked}`);
        });
        
        // Verify final state
        setTimeout(() => {
            const finalChecked = document.querySelectorAll('#checklist-form input[type="checkbox"]:checked');
            const finalValues = Array.from(finalChecked).map(cb => cb.value);
            console.log(`Final state: ${finalChecked.length} checked:`, finalValues);
            
            if (finalChecked.length === checkedItems.length) {
                console.log('✅ SUCCESS: Checkboxes are correctly set!');
            } else {
                console.log(`❌ FAILED: Expected ${checkedItems.length}, got ${finalChecked.length}`);
                
                // Try one more time with force
                console.log('Attempting force reset...');
                checkboxes.forEach(checkbox => {
                    const shouldBeChecked = checkedItems.includes(parseInt(checkbox.value));
                    checkbox.checked = shouldBeChecked;
                    // Force a change event
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                });
                
                // Final verification
                setTimeout(() => {
                    const forceChecked = document.querySelectorAll('#checklist-form input[type="checkbox"]:checked');
                    console.log(`After force: ${forceChecked.length} checked`);
                }, 50);
            }
        }, 100);
    }, 200);
}

// Update checklist button count
function updateChecklistButtonCount(busId) {
    const busData = checklistDataByBus[busId] || { checked: [], unchecked: [] };
    const count = busData.checked ? busData.checked.length : 0;
    
    // Find the checklist button for this bus
    const button = document.querySelector(`.toggle-checklist[data-bus-id="${busId}"]`);
    if (button) {
        const countSpan = button.querySelector('.checklist-count');
        if (countSpan) {
            countSpan.textContent = `(${count})`;
        }
    }
}

// Handle compartment selection in operation modal
document.addEventListener('change', function(e) {
    if (e.target.name === 'compartments[]') {
        updateOperationsContainer();
    }
});

function updateOperationsContainer() {
    const container = document.getElementById('operationsContainer');
    const selectedCompartments = Array.from(document.querySelectorAll('input[name="compartments[]"]:checked')).map(cb => parseInt(cb.value));
    
    console.log('=== UPDATE OPERATIONS CONTAINER DEBUG ===');
    console.log('Selected compartments:', selectedCompartments);
    console.log('Oil types data:', oilTypesData);
    console.log('Compartiments data:', compartimentsData);
    
    container.innerHTML = '';
    
    selectedCompartments.forEach(compId => {
        const compartiment = compartimentsData.find(c => c.id === compId);
        if (!compartiment) {
            console.log(`Compartment ${compId} not found in data`);
            return;
        }
        
        console.log(`Processing compartment: ${compartiment.name} (ID: ${compId})`);
        
        const matchingOils = oilTypesData.filter(oil => oil.usageOil === compartiment.name);
        console.log(`Matching oils for ${compartiment.name}:`, matchingOils);
        
        const operationDiv = document.createElement('div');
        operationDiv.className = 'border border-gray-200 rounded-lg p-4';
        operationDiv.innerHTML = `
            <h4 class="font-medium text-gray-900 mb-3">${compartiment.name}</h4>
            
            <div class="operation-tabs">
                <button type="button" class="operation-tab active" onclick="switchTab(this, 'huile', ${compId})">Huile</button>
                ${compartiment.name.toLowerCase() !== 'pont' ? '<button type="button" class="operation-tab" onclick="switchTab(this, \'filter\', ' + compId + ')">Filtre</button>' : ''}
                ${compartiment.name.toLowerCase() === 'moteur' ? '<button type="button" class="operation-tab" onclick="switchTab(this, \'liquide\', ' + compId + ')">Liquide</button>' : ''}
            </div>
            
            <!-- Huile Operations -->
            <div class="operation-content active" id="huile-content-${compId}">
                <div class="operation-form">
                    <div class="form-group">
                        <label>Intervention</label>
                        <select name="operations[${compId}][oil_operation]" data-skip-tom-select="true" class="input--standard">
                            <option value="">Choisir</option>
                            <option value="Apoint">Appoint</option>
                            <option value="Controle">Contrôle</option>
                            <option value="Vidange">Vidange</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Type d'huile</label>
                        <select name="operations[${compId}][oil_type_id]" data-skip-tom-select="true" class="input--standard">
                            <option value="">Choisir</option>
                            ${matchingOils.map(oil => {
                                let isSelected = '';
                                // Auto-select based on vehicle preferences
                                const busId = document.getElementById('bus_id').value;
                                const bus = busesData.find(b => b.id_bus == busId);
                                if (bus) {
                                    if (compartiment.name === 'Moteur' && bus.huile_moteur == oil.id) isSelected = 'selected';
                                    else if (compartiment.name === 'Boite Vitesse' && bus.huile_boite_vitesse == oil.id) isSelected = 'selected';
                                    else if (compartiment.name === 'Pont' && bus.huile_pont == oil.id) isSelected = 'selected';
                                }
                                return `<option value="${oil.id}" ${isSelected}>${oil.name}</option>`;
                            }).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantité (L)</label>
                        <input type="number" step="0.01" name="operations[${compId}][quantity]" class="input--standard" placeholder="0.00">
                    </div>
                </div>
            </div>
            
            ${compartiment.name.toLowerCase() !== 'pont' ? `
            <!-- Filter Operations -->
            <div class="operation-content" id="filter-content-${compId}">
                <div class="operation-form">
                    <div class="form-group">
                        <label>Type de filtre</label>
                        <select name="operations[${compId}][filter_type_id]" data-skip-tom-select="true" class="input--standard">
                            <option value="">Choisir</option>
                            ${filterTypesData.filter(filter => filter.usageFilter === compartiment.name).map(filter => `<option value="${filter.id}">${filter.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Intervention</label>
                        <select name="operations[${compId}][filter_operation]" data-skip-tom-select="true" class="input--standard">
                            <option value="">Choisir</option>
                            <option value="Nettoyage">Nettoyage</option>
                            <option value="Changement">Changement</option>
                        </select>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${compartiment.name.toLowerCase() === 'moteur' ? `
            <!-- Liquide Operations -->
            <div class="operation-content" id="liquide-content-${compId}">
                <div class="operation-form">
                    <div class="form-group">
                        <label>Intervention</label>
                        <select name="operations[${compId}][oil_operation]" data-skip-tom-select="true" class="input--standard">
                            <option value="">Choisir</option>
                            <option value="Apoint">Appoint</option>
                            <option value="Controle">Contrôle</option>
                            <option value="Vidange">Vidange</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Type de liquide</label>
                        <select name="operations[${compId}][liquide_type_id]" data-skip-tom-select="true" class="input--standard">
                            <option value="">Choisir</option>
                            ${liquidesData.map(liquide => `<option value="${liquide.id}">${liquide.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantité (L)</label>
                        <input type="number" step="0.01" name="operations[${compId}][quantity]" class="input--standard" placeholder="0.00">
                    </div>
                </div>
            </div>
            ` : ''}
        `;
        
        container.appendChild(operationDiv);
    });
}

function switchTab(tab, type, compartmentId) {
    const container = tab.closest('.border');
    
    // Remove active class from all tabs and contents in this container
    container.querySelectorAll('.operation-tab').forEach(t => t.classList.remove('active'));
    container.querySelectorAll('.operation-content').forEach(c => c.classList.remove('active'));
    
    // Add active class to clicked tab and corresponding content
    tab.classList.add('active');
    container.querySelector(`#${type}-content-${compartmentId}`).classList.add('active');
}

// Handle checklist button clicks
document.addEventListener('click', function(e) {
    if (e.target.closest('.toggle-checklist')) {
        const button = e.target.closest('.toggle-checklist');
        const busId = button.getAttribute('data-bus-id');
        
        openChecklistModal(parseInt(busId));
    }
});

// Handle checklist modal close buttons
document.getElementById('close-checklist-modal').addEventListener('click', closeChecklistModal);
document.getElementById('cancel-checklist-top').addEventListener('click', closeChecklistModal);

// Close checklist modal when clicking outside
document.getElementById('checklist-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeChecklistModal();
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
document.getElementById('checklist-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const busId = parseInt(formData.get('bus_id'));
    const checklistData = [];
    
    // Debug: Log all form data
    console.log('=== FORM SUBMISSION DEBUG ===');
    console.log('Bus ID:', busId);
    console.log('Fiche ID:', currentFicheId);
    
    // Collect checked items
    for (let [key, value] of formData.entries()) {
        console.log('Form entry:', key, '=', value);
        if (key.startsWith('checklist_items[')) {
            const itemId = parseInt(value);
            checklistData.push(itemId);
        }
    }
    
    // Alternative method: get all checked checkboxes directly
    const checkedBoxes = document.querySelectorAll('#checklist-form input[type="checkbox"]:checked');
    console.log('Checked checkboxes found:', checkedBoxes.length);
    checkedBoxes.forEach((checkbox, index) => {
        console.log(`Checkbox ${index}:`, checkbox.name, checkbox.value);
        if (!checklistData.includes(parseInt(checkbox.value))) {
            checklistData.push(parseInt(checkbox.value));
        }
    });
    
    console.log('Final checklist data:', checklistData);
    console.log('=== END FORM DEBUG ===');
    
    // Update local data with new structure
    if (!checklistDataByBus[busId]) {
        checklistDataByBus[busId] = { checked: [], unchecked: [] };
    }
    checklistDataByBus[busId].checked = checklistData;
    
    // Update button count
    updateChecklistButtonCount(busId);
    
    // Send data to server
    fetch('/api/save_checklist_fiche.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            bus_id: busId,
            fiche_id: currentFicheId,
            checklist_items: checklistData
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            closeChecklistModal();
            showNotification('Checklist enregistrée avec succès', 'success');
        } else {
            showNotification('Erreur: ' + (data.message || 'Une erreur est survenue'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Une erreur est survenue lors de l\'enregistrement de la checklist', 'error');
    });
});

// Show notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Handle form submissions
document.getElementById('addVehicleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        bus_id: formData.get('bus_id'),
        index_km: formData.get('index_km'),
        fiche_id: currentFicheId
    };
    
    fetch('/api/add_vehicle_to_fiche.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddVehicleModal();
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue lors de l\'ajout du véhicule');
    });
});

// Handle update vehicle form submission
document.getElementById('updateVehicleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    // Get bus_id from Choices.js if it exists, otherwise from form
    const busId = updateVehicleChoices ? updateVehicleChoices.getValue(true) : formData.get('bus_id');
    
    const data = {
        record_id: formData.get('record_id'),
        bus_id: busId,
        index_km: formData.get('index_km')
    };
    
    fetch('/api/update_vehicle_record.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeUpdateVehicleModal();
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue lors de la modification du véhicule');
    });
});

document.getElementById('addOperationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const operations = {};
    
    // Debug: Log all form data
    console.log('=== FORM DATA DEBUG ===');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    console.log('=== END FORM DATA DEBUG ===');
    
    // Debug: Check all input elements in the form
    console.log('=== ALL INPUT ELEMENTS DEBUG ===');
    const allInputs = this.querySelectorAll('input, select');
    allInputs.forEach(input => {
        console.log(`Input: name="${input.name}", type="${input.type}", value="${input.value}"`);
    });
    console.log('=== END INPUT ELEMENTS DEBUG ===');
    
    // Collect operations data - only process active tab fields
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('operations[')) {
            const match = key.match(/operations\[(\d+)\]\[(.+)\]$/);
            if (match) {
                const compId = match[1];
                const field = match[2];
                
                // Only process non-empty values to prevent overwriting
                if (value !== '') {
                    if (!operations[compId]) operations[compId] = {};
                    operations[compId][field] = value;
                    console.log(`Added: operations[${compId}][${field}] = ${value}`);
                } else {
                    console.log(`Skipped empty: operations[${compId}][${field}]`);
                }
            }
        }
    }
    
    console.log('Final operations object:', operations);
    
    // Filter out empty operations with proper validation
    const validOperations = Object.entries(operations).filter(([compId, op]) => {
        console.log(`Validating compartment ${compId}:`, op);
        
        // Check if it's a complete oil operation
        const isCompleteOil = op.oil_operation && op.oil_operation !== '' && 
                             op.oil_type_id && op.oil_type_id !== '' && 
                             (op.quantity && op.quantity !== '' || op.oil_operation === 'Controle');
        
        // Check if it's a complete liquide operation  
        const isCompleteLiquide = op.oil_operation && op.oil_operation !== '' && 
                                op.liquide_type_id && op.liquide_type_id !== '' && 
                                (op.quantity && op.quantity !== '' || op.oil_operation === 'Controle');
        
        // Check if it's a complete filter operation  
        const isCompleteFilter = op.filter_operation && op.filter_operation !== '' && 
                                op.filter_type_id && op.filter_type_id !== '';
        
        console.log(`  - Oil operation: "${op.oil_operation}" (empty: ${!op.oil_operation || op.oil_operation === ''})`);
        console.log(`  - Oil type ID: "${op.oil_type_id}" (empty: ${!op.oil_type_id || op.oil_type_id === ''})`);
        console.log(`  - Quantity: "${op.quantity}" (empty: ${!op.quantity || op.quantity === ''})`);
        console.log(`  - Oil complete: ${isCompleteOil}`);
        console.log(`  - Liquide complete: ${isCompleteLiquide}`);
        console.log(`  - Filter complete: ${isCompleteFilter}`);
        console.log(`  - Valid: ${isCompleteOil || isCompleteLiquide || isCompleteFilter}`);
        console.log('---');
        
        // Return true if either oil OR liquide OR filter operation is complete
        return isCompleteOil || isCompleteLiquide || isCompleteFilter;
    });
    
    // Debug logging
    console.log('Collected operations:', operations);
    console.log('Valid operations:', validOperations);
    
    if (validOperations.length === 0) {
        alert('Veuillez ajouter au moins une intervention complète');
        return;
    }
    
    const data = {
        record_id: formData.get('record_id'),
        bus_id: formData.get('bus_id'),
        operations: Object.fromEntries(validOperations)
    };
    
    fetch('/api/add_operations_to_fiche.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddOperationModal();
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue lors de l\'ajout des interventions');
    });
});

function deleteOperation(operationId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette intervention ?')) {
        fetch('/api/delete_operation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ operation_id: operationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la suppression de l\'intervention');
        });
    }
}

// Vehicle search functionality
document.addEventListener('DOMContentLoaded', function() {
    const vehicleSearchInput = document.getElementById('vehicle-search');
    
    if (vehicleSearchInput) {
        vehicleSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const vehicleItems = document.querySelectorAll('.vehicle-item');
            
            vehicleItems.forEach(function(item) {
                const vehicleName = item.getAttribute('data-vehicle') || '';
                
                if (vehicleName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>
</body>
</html>
