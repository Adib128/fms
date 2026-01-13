<?php
require_once 'config.php';

echo "<h2>Testing Control Operations Fix</h2>";

// Test 1: Check if operations_vidange table exists and has correct structure
echo "<h3>1. Checking operations_vidange table</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'operations_vidange'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p style='color: green;'>✅ operations_vidange table exists</p>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE operations_vidange");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $has_controle = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'nature_operation') {
                echo "<p>nature_operation column: " . htmlspecialchars($column['Type']) . "</p>";
                if (strpos($column['Type'], 'controle') !== false) {
                    $has_controle = true;
                    echo "<p style='color: green;'>✅ 'controle' is supported in nature_operation ENUM</p>";
                } else {
                    echo "<p style='color: red;'>❌ 'controle' is NOT supported - needs update</p>";
                }
                break;
            }
        }
        
        if (!$has_controle) {
            echo "<p><strong>Run this fix:</strong> ALTER TABLE operations_vidange MODIFY COLUMN nature_operation ENUM('apoint', 'vidange', 'controle') NULL;</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ operations_vidange table does NOT exist</p>";
        echo "<p><strong>Run this fix:</strong> mysql -u root -p energie < create_operations_table.sql</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Try to insert a control operation if table exists
echo "<h3>2. Testing control operation insert</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'operations_vidange'");
    if ($stmt->rowCount() > 0) {
        // Get a test bus
        $stmt = $db->query("SELECT id_bus FROM bus LIMIT 1");
        $test_bus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_bus) {
            // Try to insert control operation for "Compartiment boite vitesse"
            $stmt = $db->prepare("INSERT INTO operations_vidange (id_vidange, id_bus, categorie, compartiment, type_huile, nature_operation, id_huile, quantite, created_at) VALUES (NULL, ?, 'Huiles', 'Compartiment boite vitesse', 'Boite vitesse', 'controle', 1, NULL, NOW())");
            $result = $stmt->execute([$test_bus['id_bus']]);
            
            if ($result) {
                $insert_id = $db->lastInsertId();
                echo "<p style='color: green;'>✅ Control operation inserted successfully (ID: $insert_id)</p>";
                
                // Verify the insert
                $stmt = $db->prepare("SELECT * FROM operations_vidange WHERE id_operation = ?");
                $stmt->execute([$insert_id]);
                $operation = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($operation) {
                    echo "<p>Verification:</p>";
                    echo "<ul>";
                    echo "<li>Bus ID: " . $operation['id_bus'] . "</li>";
                    echo "<li>Compartiment: " . htmlspecialchars($operation['compartiment']) . "</li>";
                    echo "<li>Nature: " . htmlspecialchars($operation['nature_operation']) . "</li>";
                    echo "<li>Quantité: " . ($operation['quantite'] ?? 'NULL') . "</li>";
                    echo "</ul>";
                }
                
                // Clean up
                $stmt = $db->prepare("DELETE FROM operations_vidange WHERE id_operation = ?");
                $stmt->execute([$insert_id]);
                echo "<p style='color: blue;'>🧹 Test operation cleaned up</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to insert control operation</p>";
                echo "<p>Error info: " . print_r($stmt->errorInfo(), true) . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No test bus found in database</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ operations_vidange table doesn't exist - can't test insert</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Insert test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>3. Next Steps</h3>";
echo "<ol>";
echo "<li>If table doesn't exist: <code>mysql -u root -p energie < create_operations_table.sql</code></li>";
echo "<li>Test the frontend: <a href='ajouter_operation.php'>Add Operation</a></li>";
echo "<li>Try adding control operations in all 3 compartments</li>";
echo "<li>Verify quantity field behavior</li>";
echo "</ol>";
?>
