<?php
// Simple database schema test
require_once 'config.php';

echo "<h2>Database Schema Check for Control Operations</h2>";

try {
    // Check the operations_vidange table structure
    $stmt = $db->query("DESCRIBE operations_vidange");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current operations_vidange table structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "</tr>";
        
        // Check specifically for nature_operation
        if ($column['Field'] === 'nature_operation') {
            $nature_type = $column['Type'];
            echo "<tr style='background-color: #ffffcc;'>";
            echo "<td colspan='4'><strong>nature_operation column:</strong> " . htmlspecialchars($nature_type) . "</td>";
            echo "</tr>";
            
            if (strpos($nature_type, 'controle') !== false) {
                echo "<tr style='background-color: #ccffcc;'>";
                echo "<td colspan='4'>✅ 'controle' is SUPPORTED in the ENUM</td>";
                echo "</tr>";
            } else {
                echo "<tr style='background-color: #ffcccc;'>";
                echo "<td colspan='4'>❌ 'controle' is NOT supported in the ENUM - NEEDS FIX</td>";
                echo "</tr>";
            }
        }
    }
    echo "</table>";
    
    // Test if we can select existing control operations
    echo "<h3>Testing control operation queries:</h3>";
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM operations_vidange WHERE nature_operation = 'controle'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Existing control operations: " . $result['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error querying control operations: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Test inserting a control operation
    echo "<h3>Test inserting control operation:</h3>";
    try {
        // Get a test bus
        $stmt = $db->query("SELECT id_bus FROM bus LIMIT 1");
        $test_bus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_bus) {
            // Try to insert
            $stmt = $db->prepare("INSERT INTO operations_vidange (id_vidange, id_bus, categorie, compartiment, type_huile, nature_operation, id_huile, quantite, created_at) VALUES (NULL, ?, 'Huiles', 'Compartiment boite vitesse', 'Boite vitesse', 'controle', 1, NULL, NOW())");
            $result = $stmt->execute([$test_bus['id_bus']]);
            
            if ($result) {
                $insert_id = $db->lastInsertId();
                echo "<p style='color: green;'>✅ Control operation inserted successfully (ID: $insert_id)</p>";
                
                // Clean up
                $stmt = $db->prepare("DELETE FROM operations_vidange WHERE id_operation = ?");
                $stmt->execute([$insert_id]);
                echo "<p style='color: blue;'>🧹 Test operation cleaned up</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to insert control operation</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No test bus found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Insert test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>Required SQL Fix:</h3>";
echo "<code style='background: #f0f0f0; padding: 10px; display: block;'>";
echo "ALTER TABLE operations_vidange MODIFY COLUMN nature_operation ENUM('apoint', 'vidange', 'controle') NULL;";
echo "</code>";
?>
