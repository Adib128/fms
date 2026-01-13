<?php
require 'header.php' ?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Modifier Vidange</h1>
                <p class="mt-2 text-sm text-gray-600">Modifier une vidange existante dans le système</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <?php
                // Get vidange ID from URL
                $id_vidange = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                
                if ($id_vidange === 0) {
                    echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>ID de vidange invalide</div>";
                    echo "<script>setTimeout(() => window.location.replace('/liste-vidange'), 2000)</script>";
                    exit;
                }
                
                // Get existing vidange data
                $vidange_query = $db->prepare("SELECT v.*, b.matricule_interne, b.type as bus_type FROM vidange v INNER JOIN bus b ON v.id_bus = b.id_bus WHERE v.id_vidange = ?");
                $vidange_query->execute([$id_vidange]);
                $vidange = $vidange_query->fetch(PDO::FETCH_ASSOC);
                
                if (!$vidange) {
                    echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>Vidange non trouvée</div>";
                    echo "<script>setTimeout(() => window.location.replace('/liste-vidange'), 2000)</script>";
                    exit;
                }
                
                // Get existing operations
                $operations_query = $db->prepare("SELECT ov.*, h.libelle as huile_libelle FROM operations_vidange ov LEFT JOIN huile h ON ov.id_huile = h.id_huile WHERE ov.id_vidange = ? ORDER BY ov.id_operation");
                $operations_query->execute([$id_vidange]);
                $existing_operations = $operations_query->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <form class="space-y-8" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?id=<?php echo $id_vidange; ?>">
                    
                    <!-- Véhicule Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="bus" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Véhicule</label>
                        <div class="md:col-span-8">
                            <select id="bus" name="bus" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 tom-select"
                                data-placeholder="Choisir le véhicule" data-search-placeholder="Rechercher un véhicule" data-icon="bus">
                                <option value=""></option>
                                <?php
                                $liste_station = $db->query("SELECT * FROM bus ORDER BY id_bus ASC");
                                foreach ($liste_station as $row) {
                                    $selected = $row["id_bus"] == $vidange['id_bus'] ? 'selected' : '';
                                    echo '<option value="' . $row["id_bus"] . '" ' . $selected . '>';
                                    echo htmlspecialchars($row["matricule_interne"] . " : " . $row["type"], ENT_QUOTES, 'UTF-8');
                                    echo '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Date de vidange Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="date_vidange" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Date de vidange</label>
                        <div class="md:col-span-8">
                            <input type="date" id="date_vidange" name="date_vidange" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                value="<?php echo $vidange['date_vidange']; ?>">
                        </div>
                    </div>
                    
                    <!-- Index Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="indexe" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Index</label>
                        <div class="md:col-span-8">
                            <input type="number" id="indexe" name="indexe" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="Entrez l'index de vidange" value="<?php echo htmlspecialchars($vidange['indexe']); ?>">
                        </div>
                    </div>
                    
                    <!-- Ref Doc Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="ref_doc" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Référence Document</label>
                        <div class="md:col-span-8">
                            <input type="text" id="ref_doc" name="ref_doc"
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="Entrez la référence du document (optionnel)" value="<?php echo htmlspecialchars($vidange['ref_doc'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Operations Section -->
                    <div class="border-t border-gray-200 pt-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Opérations de Vidange</h2>
                        
                        <div id="operations-container" class="space-y-6">
                            <!-- Existing operations will be loaded here -->
                        </div>
                        
                        <div class="mt-6">
                            <button type="button" id="add-operation-btn" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                Ajouter une Opération
                            </button>
                        </div>
                    </div>

                    <?php
                    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                        // Simple test to see if form is submitted
                        echo "<div class='bg-purple-50 border border-purple-200 text-purple-700 px-4 py-3 rounded-lg'>";
                        echo "<strong>FORM SUBMITTED!</strong><br>";
                        echo "Time: " . date('Y-m-d H:i:s') . "<br>";
                        echo "</div>";
                        
                        // Debug: Print POST data
                        echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg'>";
                        echo "<strong>Debug - POST Data:</strong><br>";
                        echo "Bus: " . ($_POST["bus"] ?? 'NULL') . "<br>";
                        echo "Date: " . ($_POST["date_vidange"] ?? 'NULL') . "<br>";
                        echo "Index: " . ($_POST["indexe"] ?? 'NULL') . "<br>";
                        echo "Operations count: " . (isset($_POST["operations"]) ? count($_POST["operations"]) : 0) . "<br>";
                        if (isset($_POST["operations"])) {
                            foreach ($_POST["operations"] as $i => $op) {
                                echo "Operation $i: " . json_encode($op) . "<br>";
                            }
                        }
                        echo "</div>";
                        
                        $bus = $_POST["bus"];
                        $date_vidange = $_POST["date_vidange"];
                        $date_saisie = date("Y-m-d");
                        $indexe = $_POST["indexe"];
                        $ref_doc = isset($_POST["ref_doc"]) ? $_POST["ref_doc"] : null;
                        $operations = isset($_POST["operations"]) ? $_POST["operations"] : [];
                        
                        // Warning if no operations
                        if (empty($operations)) {
                            echo "<div class='bg-orange-50 border border-orange-200 text-orange-700 px-4 py-3 rounded-lg'>";
                            echo "<strong>WARNING: No operations submitted!</strong><br>";
                            echo "You must add at least one operation before submitting.<br>";
                            echo "Click 'Ajouter une Opération' and fill in all required fields.<br>";
                            echo "</div>";
                        }

                        // Check for duplicate vidange (excluding current one)
                        $select = $db->prepare("SELECT COUNT(*) FROM vidange WHERE id_bus=? AND date_vidange=? AND id_vidange != ?");
                        $select->execute([$bus, $date_vidange, $id_vidange]);
                        $count = $select->fetchColumn();
                        
                        if ($count > 0) {
                            echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>Ce vidange existe déjà pour cette date et ce véhicule</div>";
                        } else {
                            // Get kilometrage
                            $select = $db->prepare("SELECT kilometrage FROM total_kilometrage WHERE id_bus=?");
                            $select->execute([$bus]);
                            $kilometrage = $select->fetchColumn();
                            
                            // Update vidange
                            $sth = $db->prepare('UPDATE vidange SET date_vidange=?, kilometrage=?, indexe=?, id_bus=?, ref_doc=? WHERE id_vidange=?');
                            $sth->execute([$date_vidange, $kilometrage, $indexe, $bus, $ref_doc, $id_vidange]);
                            
                            // Delete existing operations
                            $sth = $db->prepare('DELETE FROM operations_vidange WHERE id_vidange = ?');
                            $sth->execute([$id_vidange]);
                            
                            // Insert operations if any
                            $validation_errors = [];
                            if (!empty($operations)) {
                                echo "<div class='bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg'>";
                                echo "<strong>Processing " . count($operations) . " operations...</strong><br>";
                                echo "</div>";
                                
                                foreach ($operations as $operation) {
                                    $categorie = $operation['categorie'] ?? '';
                                    
                                    echo "<div class='bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg'>";
                                    echo "Processing operation: " . json_encode($operation) . "<br>";
                                    echo "</div>";
                                    
                                    if ($categorie === 'Huiles') {
                                        $compartiment = $operation['compartiment'] ?? '';
                                        $id_huile = $operation['id_huile'] ?? '';
                                        $nature_operation = $operation['nature_operation'] ?? '';
                                        $quantite = $operation['quantite'] ?? '';
                                        
                                        // Validate required fields for huiles
                                        if (empty($compartiment) || empty($id_huile) || empty($nature_operation) || empty($quantite)) {
                                            $validation_errors[] = "Tous les champs sont requis pour les huiles. Missing: " . 
                                                (empty($compartiment) ? 'compartiment ' : '') . 
                                                (empty($id_huile) ? 'id_huile ' : '') . 
                                                (empty($nature_operation) ? 'nature_operation ' : '') . 
                                                (empty($quantite) ? 'quantite ' : '');
                                            continue;
                                        }
                                        
                                        // Get huile type from huile table
                                        $stmt = $db->prepare("SELECT type FROM huile WHERE id_huile = ?");
                                        $stmt->execute([$id_huile]);
                                        $huile = $stmt->fetch(PDO::FETCH_ASSOC);
                                        $type_huile = $huile ? $huile['type'] : '';
                                        
                                        // Validate type_huile is not empty and is valid
                                        if (empty($type_huile)) {
                                            $validation_errors[] = "Type d'huile non trouvé pour l'huile sélectionnée (ID: $id_huile)";
                                            continue;
                                        }
                                        
                                        echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg'>";
                                        echo "Inserting huile operation: compartiment=$compartiment, type_huile=$type_huile, nature=$nature_operation<br>";
                                        echo "</div>";
                                        
                                        $sth = $db->prepare('INSERT INTO operations_vidange (id_vidange, id_bus, categorie, compartiment, type_huile, nature_operation, id_huile, quantite) VALUES (:id_vidange, :id_bus, :categorie, :compartiment, :type_huile, :nature_operation, :id_huile, :quantite)');
                                        $sth->bindParam(':id_vidange', $id_vidange);
                                        $sth->bindParam(':id_bus', $bus);
                                        $sth->bindParam(':categorie', $categorie);
                                        $sth->bindParam(':compartiment', $compartiment);
                                        $sth->bindParam(':type_huile', $type_huile);
                                        $sth->bindParam(':nature_operation', $nature_operation);
                                        $sth->bindParam(':id_huile', $id_huile);
                                        $sth->bindParam(':quantite', $quantite);
                                        $sth->execute();
                                    } elseif ($categorie === 'Filtres') {
                                        $compartiment = $operation['compartiment'] ?? '';
                                        $type_filtre = $operation['type_filtre'] ?? '';
                                        $action_filtre = $operation['action_filtre'] ?? '';
                                        
                                        // Validate required fields for filtres
                                        if (empty($compartiment) || empty($type_filtre) || empty($action_filtre)) {
                                            $validation_errors[] = "Tous les champs sont requis pour les filtres. Missing: " . 
                                                (empty($compartiment) ? 'compartiment ' : '') . 
                                                (empty($type_filtre) ? 'type_filtre ' : '') . 
                                                (empty($action_filtre) ? 'action_filtre ' : '');
                                            continue;
                                        }
                                        
                                        echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg'>";
                                        echo "Inserting filtre operation: compartiment=$compartiment, type_filtre=$type_filtre, action=$action_filtre<br>";
                                        echo "</div>";
                                        
                                        $sth = $db->prepare('INSERT INTO operations_vidange (id_vidange, id_bus, categorie, compartiment, type_filtre, action_filtre) VALUES (:id_vidange, :id_bus, :categorie, :compartiment, :type_filtre, :action_filtre)');
                                        $sth->bindParam(':id_vidange', $id_vidange);
                                        $sth->bindParam(':id_bus', $bus);
                                        $sth->bindParam(':categorie', $categorie);
                                        $sth->bindParam(':compartiment', $compartiment);
                                        $sth->bindParam(':type_filtre', $type_filtre);
                                        $sth->bindParam(':action_filtre', $action_filtre);
                                        $sth->execute();
                                    }
                                }
                            }
                            
                            // Check for validation errors
                            if (!empty($validation_errors)) {
                                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>";
                                foreach ($validation_errors as $error) {
                                    echo htmlspecialchars($error) . "<br>";
                                }
                                echo "</div>";
                            } else {
                                $_SESSION["message"] = "Vidange modifiée avec succès";
                                echo "<script> window.location.replace('/liste-vidange')</script>";
                            }
                        }
                    }
                    ?>
                    
                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="/liste-vidange" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 12H5M12 19l-7-7 7-7"/>
                            </svg>
                            Annuler
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 13l4 4L19 7"/>
                            </svg>
                            Mettre à Jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let operationIndex = <?php echo count($existing_operations); ?>;
    
    // Get huiles data for dropdown
    const huilesOptions = <?php
        $huiles = $db->query("SELECT * FROM huile ORDER BY libelle ASC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($huiles);
    ?>;
    
    // Load existing operations
    loadExistingOperations();
    
    // Add operation button click handler
    document.getElementById('add-operation-btn').addEventListener('click', function() {
        addOperationForm();
    });
    
    function loadExistingOperations() {
        const container = document.getElementById('operations-container');
        const existingOps = <?php echo json_encode($existing_operations); ?>;
        
        console.log('Loading existing operations:', existingOps);
        console.log('Huiles options available:', huilesOptions);
        
        existingOps.forEach((op, index) => {
            console.log('Processing operation:', op);
            const operationDiv = document.createElement('div');
            operationDiv.className = 'operation-item bg-gray-50 p-6 rounded-lg border border-gray-200';
            operationDiv.dataset.index = index;
            
            // Set operation data for form population
            operationDiv.dataset.operation = JSON.stringify(op);
            
            container.appendChild(operationDiv);
            
            // Initialize with existing compartiment
            updateOperationFields(operationDiv, op.compartiment || '', op);
            
            // Add remove handler
            operationDiv.querySelector('.remove-operation-btn').addEventListener('click', function() {
                operationDiv.remove();
            });
        });
    }
    
    function addOperationForm() {
        const container = document.getElementById('operations-container');
        const operationDiv = document.createElement('div');
        operationDiv.className = 'operation-item bg-gray-50 p-6 rounded-lg border border-gray-200';
        operationDiv.dataset.index = operationIndex;
        
        container.appendChild(operationDiv);
        
        console.log('Adding new operation with index:', operationIndex);
        
        // Initialize with empty category to show the dropdown
        updateOperationFields(operationDiv, '');
        
        // Add remove handler
        operationDiv.querySelector('.remove-operation-btn').addEventListener('click', function() {
            operationDiv.remove();
        });
        
        operationIndex++;
    }
    
    function updateOperationFields(operationDiv, compartiment, existingData = null) {
        const fieldsContainer = operationDiv.querySelector('.operation-fields') || document.createElement('div');
        fieldsContainer.className = 'operation-fields';
        if (!operationDiv.contains(fieldsContainer)) {
            operationDiv.appendChild(fieldsContainer);
        }
        
        const index = operationDiv.dataset.index;
        const opData = existingData || (operationDiv.dataset.operation ? JSON.parse(operationDiv.dataset.operation) : {});
        
        console.log('Updating operation fields - Index:', index, 'Compartiment:', compartiment, 'OpData:', opData);
        
        fieldsContainer.innerHTML = `
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Opération ${parseInt(index) + 1}</h3>
                <button type="button" class="remove-operation-btn text-red-600 hover:text-red-800">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center">
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-2">Compartiment</label>
                    <select name="operations[${index}][compartiment]" required class="compartiment-select w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Choisir le compartiment</option>
                        <option value="Compartiment moteur" ${opData.compartiment === 'Compartiment moteur' ? 'selected' : ''}>Compartiment moteur</option>
                        <option value="Compartiment boite vitesse" ${opData.compartiment === 'Compartiment boite vitesse' ? 'selected' : ''}>Compartiment boite vitesse</option>
                        <option value="Compartiment Pont" ${opData.compartiment === 'Compartiment Pont' ? 'selected' : ''}>Compartiment Pont</option>
                    </select>
                </div>
                
                ${getCategoryField(index, compartiment, opData)}
                ${getCategorySpecificFields(index, compartiment, opData)}
            </div>
        `;
        
        console.log('Fields HTML generated, initializing event handlers...');
        
        // Add compartiment change handler
        const compartimentSelect = operationDiv.querySelector('.compartiment-select');
        if (compartimentSelect) {
            console.log('Adding compartiment change handler');
            compartimentSelect.addEventListener('change', function() {
                console.log('Compartiment changed to:', this.value);
                // Store the selected value before rebuilding
                const selectedCompartiment = this.value;
                updateOperationFields(operationDiv, selectedCompartiment, opData);
                
                // Re-initialize TomSelect for the compartiment dropdown with the selected value
                setTimeout(() => {
                    const newCompartimentSelect = operationDiv.querySelector('.compartiment-select');
                    if (newCompartimentSelect && newCompartimentSelect.tomselect) {
                        newCompartimentSelect.tomselect.setValue(selectedCompartiment);
                    }
                }, 10);
            });
        }
        
        // Add category change handler for dynamic field visibility
        const categorySelect = operationDiv.querySelector('.categorie-select');
        if (categorySelect) {
            console.log('Adding category change handler for:', categorySelect.name);
            console.log('Category select element:', categorySelect);
            console.log('Category select disabled?', categorySelect.disabled);
            
            // Force enable the category dropdown
            categorySelect.disabled = false;
            categorySelect.removeAttribute('disabled');
            categorySelect.removeAttribute('aria-disabled');
            
            categorySelect.addEventListener('change', function() {
                console.log('Category changed to:', this.value);
                updateFieldsVisibility(operationDiv, this.value, compartiment);
            });
            
            // Trigger initial visibility update
            if (opData.categorie) {
                updateFieldsVisibility(operationDiv, opData.categorie, compartiment);
            }
        } else {
            console.error('Category select not found in operation div!');
        }
        
        // Add nature operation change handler for quantity control
        const natureSelect = operationDiv.querySelector('.nature-operation-select');
        if (natureSelect) {
            console.log('Adding nature operation change handler');
            natureSelect.addEventListener('change', function() {
                console.log('Nature operation changed to:', this.value);
                handleNatureOperationChange(operationDiv, this.value);
            });
        }
        
        // Initialize TomSelect for huile dropdowns
        initializeTomSelect(operationDiv);
        
        // Load huiles based on compartiment
        if (compartiment) {
            console.log('Loading huiles for compartment:', compartiment);
            loadHuilesForCompartiment(operationDiv, compartiment);
        }
    }
    
    function getCategoryField(index, compartiment, opData = {}) {
        if (compartiment === 'Compartiment moteur' || compartiment === 'Compartiment boite vitesse') {
            return `
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-2">Catégorie</label>
                    <select name="operations[${index}][categorie]" required class="categorie-select w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Choisir une catégorie</option>
                        <option value="Huiles" ${opData.categorie === 'Huiles' ? 'selected' : ''}>Huiles</option>
                        <option value="Filtres" ${opData.categorie === 'Filtres' ? 'selected' : ''}>Filtres</option>
                    </select>
                </div>
            `;
        } else if (compartiment === 'Compartiment Pont') {
            return `
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-2">Catégorie</label>
                    <select name="operations[${index}][categorie]" required class="categorie-select w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Choisir une catégorie</option>
                        <option value="Huiles" ${opData.categorie === 'Huiles' ? 'selected' : ''}>Huiles</option>
                    </select>
                </div>
            `;
        } else {
            return `<div></div>`;
        }
    }
    
    function getCategorySpecificFields(index, compartiment, opData = {}) {
        if (compartiment === 'Compartiment moteur' || compartiment === 'Compartiment boite vitesse') {
            return `
                <div id="huile-fields-${index}" style="display: ${opData.categorie === 'Huiles' ? 'block' : 'none'};">
                    <label class="text-sm font-medium text-gray-700 block mb-2">Huile</label>
                    <select name="operations[${index}][id_huile]" class="huile-select w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent tom-select" data-placeholder="Choisir l'huile" data-search-placeholder="Rechercher une huile" data-icon="huile">
                        <option value="">Choisir l'huile</option>
                    </select>
                </div>
                
                <div id="filtre-fields-${index}" style="display: ${opData.categorie === 'Filtres' ? 'block' : 'none'};">
                    <label class="text-sm font-medium text-gray-700 block mb-2">Type Filtre</label>
                    <select name="operations[${index}][type_filtre]" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Choisir le type</option>
                        <option value="Filtre a air" ${opData.type_filtre === 'Filtre a air' ? 'selected' : ''}>Filtre a air</option>
                        <option value="Filtre carburant" ${opData.type_filtre === 'Filtre carburant' ? 'selected' : ''}>Filtre carburant</option>
                        <option value="Filtre a huile" ${opData.type_filtre === 'Filtre a huile' ? 'selected' : ''}>Filtre a huile</option>
                        <option value="Filtre boite vitesse" ${opData.type_filtre === 'Filtre boite vitesse' ? 'selected' : ''}>Filtre boite vitesse</option>
                    </select>
                </div>
                
                <div id="nature-fields-${index}" style="display: ${opData.categorie === 'Huiles' ? 'block' : 'none'};">
                    <label class="text-sm font-medium text-gray-700 block mb-2">Nature Opération</label>
                    <select name="operations[${index}][nature_operation]" required class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent tom-select nature-operation-select"
                            data-placeholder="Choisir la nature" data-search-placeholder="Rechercher une nature" data-index="${index}">
                        <option value=""></option>
                        <option value="apoint" ${opData.nature_operation === 'apoint' ? 'selected' : ''}>Appoint</option>
                        <option value="vidange" ${opData.nature_operation === 'vidange' ? 'selected' : ''}>Vidange</option>
                        <option value="controle" ${opData.nature_operation === 'controle' ? 'selected' : ''}>Controle</option>
                    </select>
                </div>
                
                <div id="action-fields-${index}" style="display: ${opData.categorie === 'Filtres' ? 'block' : 'none'};">
                    <label class="text-sm font-medium text-gray-700 block mb-2">Action</label>
                    <select name="operations[${index}][action_filtre]" 
                            class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            tabindex="0" autocomplete="off">
                        <option value="">Choisir l'action</option>
                        <option value="Nettoyage" ${opData.action_filtre === 'Nettoyage' ? 'selected' : ''}>Nettoyage</option>
                        <option value="Changement" ${opData.action_filtre === 'Changement' ? 'selected' : ''}>Changement</option>
                    </select>
                </div>
                
                <div id="quantite-fields-${index}" style="display: ${opData.categorie === 'Huiles' ? 'block' : 'none'};">
                    <label class="text-sm font-medium text-gray-700 block mb-2">Quantité</label>
                    <input type="number" step="0.01" name="operations[${index}][quantite]" required
                           class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Quantité" value="${opData.quantite || ''}">
                </div>
            `;
        } else if (compartiment === 'Compartiment Pont') {
            return `
                <div id="huile-fields-${index}" style="display: ${opData.categorie === 'Huiles' ? 'block' : 'none'};">
                    <label class="text-sm font-medium text-gray-700 block mb-2">Huile</label>
                    <select name="operations[${index}][id_huile]" class="huile-select w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent tom-select" data-placeholder="Choisir l'huile" data-search-placeholder="Rechercher une huile" data-icon="huile">
                        <option value="">Choisir l'huile</option>
                    </select>
                </div>
                
                <div id="nature-fields-${index}" style="display: ${opData.categorie === 'Huiles' ? 'block' : 'none'};">
                    <label class="text-sm font-medium text-gray-700 block mb-2">Nature Opération</label>
                    <select name="operations[${index}][nature_operation]" required class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent tom-select nature-operation-select"
                            data-placeholder="Choisir la nature" data-search-placeholder="Rechercher une nature" data-index="${index}">
                        <option value=""></option>
                        <option value="apoint" ${opData.nature_operation === 'apoint' ? 'selected' : ''}>Appoint</option>
                        <option value="vidange" ${opData.nature_operation === 'vidange' ? 'selected' : ''}>Vidange</option>
                        <option value="controle" ${opData.nature_operation === 'controle' ? 'selected' : ''}>Controle</option>
                    </select>
                </div>
                
                <div id="quantite-fields-${index}" style="display: ${opData.categorie === 'Huiles' ? 'block' : 'none'};">
                    <label class="text-sm font-medium text-gray-700 block mb-2">Quantité</label>
                    <input type="number" step="0.01" name="operations[${index}][quantite]" required
                           class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Quantité" value="${opData.quantite || ''}">
                </div>
                
                <div></div>
                <div></div>
            `;
        } else {
            return `
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            `;
        }
    }
    
    function updateFieldsVisibility(operationDiv, categorie, compartiment) {
        const index = operationDiv.dataset.index;
        
        // Helper function to safely get element
        function safeGetElement(id) {
            const element = document.getElementById(id);
            if (!element) {
                return null;
            }
            return element;
        }
        
        // Hide all fields first (check if elements exist)
        const huileFields = safeGetElement(`huile-fields-${index}`);
        const filtreFields = safeGetElement(`filtre-fields-${index}`);
        const natureFields = safeGetElement(`nature-fields-${index}`);
        const actionFields = safeGetElement(`action-fields-${index}`);
        const quantiteFields = safeGetElement(`quantite-fields-${index}`);
        
        if (huileFields) huileFields.style.display = 'none';
        if (filtreFields) filtreFields.style.display = 'none';
        if (natureFields) natureFields.style.display = 'none';
        if (actionFields) actionFields.style.display = 'none';
        if (quantiteFields) quantiteFields.style.display = 'none';
        
        if (categorie === 'Huiles') {
            if (huileFields) huileFields.style.display = 'block';
            if (natureFields) natureFields.style.display = 'block';
            if (quantiteFields) quantiteFields.style.display = 'block';
        } else if (categorie === 'Filtres' && (compartiment === 'Compartiment moteur' || compartiment === 'Compartiment boite vitesse')) {
            if (filtreFields) filtreFields.style.display = 'block';
            if (actionFields) {
                actionFields.style.display = 'block';
                // Force focus to work properly by ensuring the select is properly initialized
                setTimeout(() => {
                    const actionSelect = actionFields.querySelector('select');
                    if (actionSelect) {
                        actionSelect.disabled = false;
                        actionSelect.tabIndex = 0;
                        // Remove any disabled attributes that might interfere
                        actionSelect.removeAttribute('disabled');
                        actionSelect.removeAttribute('aria-disabled');
                    }
                }, 50);
            }
        }
    }
    
    function handleNatureOperationChange(operationDiv, natureValue) {
        const index = operationDiv.dataset.index;
        const quantiteInput = operationDiv.querySelector(`input[name="operations[${index}][quantite]"]`);
        
        if (!quantiteInput) return;
        
        if (natureValue === 'controle') {
            // Set quantity to 0 and disable input
            quantiteInput.value = '0';
            quantiteInput.disabled = true;
            quantiteInput.classList.add('bg-gray-100', 'cursor-not-allowed');
        } else {
            // Enable input and clear value if it was 0
            quantiteInput.disabled = false;
            quantiteInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
            if (quantiteInput.value === '0') {
                quantiteInput.value = '';
            }
        }
    }
    
    function loadHuilesForCompartiment(operationDiv, compartiment) {
        const index = operationDiv.dataset.index;
        const huileSelect = operationDiv.querySelector('.huile-select');
        
        if (!huileSelect) return;
        
        // Get the operation data to find the selected huile
        const opData = operationDiv.dataset.operation ? JSON.parse(operationDiv.dataset.operation) : {};
        const selectedHuileId = opData.id_huile || '';
        
        console.log('Loading huiles for compartment:', compartiment);
        console.log('Selected huile ID:', selectedHuileId);
        console.log('Operation data:', opData);
        
        // Map compartiment to huile type
        const huileTypeMap = {
            'Compartiment moteur': 'Moteur',
            'Compartiment boite vitesse': 'Boite vitesse',
            'Compartiment Pont': 'Pont'
        };
        
        const huileType = huileTypeMap[compartiment];
        if (!huileType) return;
        
        // Filter huiles by type
        const filteredHuiles = huilesOptions.filter(huile => huile.type === huileType);
        
        console.log('Filtered huiles:', filteredHuiles);
        
        // Update huile dropdown
        huileSelect.innerHTML = '<option value="">Choisir l\'huile</option>';
        filteredHuiles.forEach(huile => {
            const option = document.createElement('option');
            option.value = huile.id_huile;
            option.textContent = huile.libelle;
            if (huile.id_huile == selectedHuileId) {
                option.selected = true;
                console.log('Set selected option:', huile.libelle);
            }
            huileSelect.appendChild(option);
        });
        
        // Update TomSelect if exists
        if (huileSelect.tomselect) {
            huileSelect.tomselect.clear();
            huileSelect.tomselect.clearOptions();
            filteredHuiles.forEach(huile => {
                huileSelect.tomselect.addOption({
                    value: huile.id_huile,
                    text: huile.libelle
                });
            });
            huileSelect.tomselect.refreshOptions();
            
            // Set the selected value
            if (selectedHuileId) {
                huileSelect.tomselect.setValue(selectedHuileId);
            }
        }
    }
    
    function initializeTomSelect(operationDiv) {
        // Initialize huile dropdowns
        const huileSelect = operationDiv.querySelector('.huile-select.tom-select');
        if (huileSelect && !huileSelect.tomselect) {
            new TomSelect(huileSelect, {
                placeholder: huileSelect.dataset.placeholder || 'Choisir...',
                searchPlaceholder: huileSelect.dataset.searchPlaceholder || 'Rechercher...',
                allowEmptyOption: false,
                items: [],
                create: false,
                maxItems: 1,
                render: {
                    option: function(data, escape) {
                        return '<div class="option">' + escape(data.text) + '</div>';
                    },
                    item: function(data, escape) {
                        return '<div class="item">' + escape(data.text) + '</div>';
                    }
                }
            });
        }
        
        // Initialize nature operation dropdowns
        const natureSelect = operationDiv.querySelector('select[name*="nature_operation"].tom-select');
        if (natureSelect && !natureSelect.tomselect) {
            const tomSelect = new TomSelect(natureSelect, {
                placeholder: natureSelect.dataset.placeholder || 'Choisir...',
                searchPlaceholder: natureSelect.dataset.searchPlaceholder || 'Rechercher...',
                allowEmptyOption: false,
                items: [],
                create: false,
                maxItems: 1,
                render: {
                    option: function(data, escape) {
                        return '<div class="option">' + escape(data.text) + '</div>';
                    },
                    item: function(data, escape) {
                        return '<div class="item">' + escape(data.text) + '</div>';
                    }
                }
            });
            
            // Add change event listener for TomSelect
            tomSelect.on('change', function(value) {
                handleNatureOperationChange(operationDiv, value);
            });
        }
    }
    
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const bus = document.querySelector('select[name="bus"]').value;
        const date_vidange = document.querySelector('input[name="date_vidange"]').value;
        const indexe = document.querySelector('input[name="indexe"]').value.trim();
        const operations = document.querySelectorAll('.operation-item');

        if (!bus || !date_vidange || !indexe) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires.');
            return false;
        }

        if (isNaN(indexe) || parseInt(indexe) < 0) {
            e.preventDefault();
            alert('L\'index doit être un nombre positif.');
            return false;
        }

        // Validate at least one operation is added
        if (operations.length === 0) {
            e.preventDefault();
            alert('Veuillez ajouter au moins une opération.');
            return false;
        }

        // Validate each operation
        for (let operation of operations) {
            const category = operation.querySelector('.categorie-select');
            if (category && !category.value) {
                e.preventDefault();
                alert('Veuillez choisir une catégorie pour chaque opération.');
                return false;
            }
        }
    });

    // Prevent Enter key submission
    document.addEventListener('keydown', function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            return false;
        }
    });
});
</script>
</body>
</html>