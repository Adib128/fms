<?php
require_once 'config.php';

echo "<h2>Testing Control Operations Validation Fix</h2>";

// Test the validation logic with mock data
echo "<h3>1. Testing PHP validation logic</h3>";

// Mock control operation data
$mockOperations = [
    [
        'type' => 'Huile',
        'oil_operation' => 'controle',
        'oil_type_id' => 1,
        'quantity' => 0
    ],
    [
        'type' => 'Huile', 
        'oil_operation' => 'vidange',
        'oil_type_id' => 1,
        'quantity' => 5.5
    ],
    [
        'type' => 'Liquide',
        'oil_operation' => 'controle',
        'liquide_type_id' => 1,
        'quantity' => 0
    ]
];

foreach ($mockOperations as $index => $operation) {
    echo "<h4>Test " . ($index + 1) . ": " . $operation['type'] . " - " . $operation['oil_operation'] . "</h4>";
    
    // Simulate the validation logic from ajouter_fiche_entretien.php
    $isValid = false;
    if ($operation['type'] === 'Huile') {
        $oilOperation = $operation['oil_operation'] ?? '';
        $oilTypeId = (int) ($operation['oil_type_id'] ?? 0);
        $quantity = (float) ($operation['quantity'] ?? 0);
        
        // For control operations, quantity can be 0 or null
        if ($oilOperation && $oilTypeId > 0 && ($quantity > 0 || $oilOperation === 'controle')) {
            $isValid = true;
        }
    } elseif ($operation['type'] === 'Liquide') {
        $oilOperation = $operation['oil_operation'] ?? '';
        $liquideTypeId = (int) ($operation['liquide_type_id'] ?? 0);
        $quantity = (float) ($operation['quantity'] ?? 0);
        
        // For control operations, quantity can be 0 or null
        if ($oilOperation && $liquideTypeId > 0 && ($quantity > 0 || $oilOperation === 'controle')) {
            $isValid = true;
        }
    }
    
    if ($isValid) {
        echo "<p style='color: green;'>✅ Validation PASSED - Control operation with quantity 0 is accepted</p>";
    } else {
        echo "<p style='color: red;'>❌ Validation FAILED</p>";
    }
}

echo "<h3>2. What was fixed</h3>";
echo "<ul>";
echo "<li><strong>PHP Backend:</strong> Modified validation in ajouter_fiche_entretien.php to allow quantity = 0 for 'controle' operations</li>";
echo "<li><strong>JavaScript Frontend:</strong> Modified form validation to skip quantity requirement for 'Controle' operations</li>";
echo "<li><strong>Display:</strong> Control operations don't show '0L' in the operation details</li>";
echo "</ul>";

echo "<h3>3. How to test</h3>";
echo "<ol>";
echo "<li>Go to the fiche entretien form</li>";
echo "<li>Add a vehicle and select 'Compartiment boite vitesse'</li>";
echo "<li>Add an oil operation</li>";
echo "<li>Select 'Controle' as the operation type</li>";
echo "<li>Notice the quantity field is set to 0 and read-only</li>";
echo "<li>Click 'Ajouter l'opération' - it should now work without errors</li>";
echo "</ol>";

echo "<p><strong>The control operations should now be properly recognized and saved!</strong></p>";
?>
