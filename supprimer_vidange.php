<?php
require_once "config.php"; 
session_start();

// Get and validate the vidange ID
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id === 0) {
    $_SESSION["error"] = "ID de vidange invalide";
    header('location: /liste-vidange');
    exit;
}

try {
    // Start transaction for data integrity
    $db->beginTransaction();
    
    // First delete related operations from operations_vidange table
    $deleteOperations = $db->prepare("DELETE FROM operations_vidange WHERE id_vidange = ?");
    $deleteOperations->execute([$id]);
    $operationsDeleted = $deleteOperations->rowCount();
    
    // Then delete the vidange record
    $deleteVidange = $db->prepare("DELETE FROM vidange WHERE id_vidange = ?");
    $deleteVidange->execute([$id]);
    $vidangeDeleted = $deleteVidange->rowCount();
    
    if ($vidangeDeleted > 0) {
        // Commit the transaction
        $db->commit();
        
        $_SESSION["message"] = "Vidange supprimée avec succès";
        if ($operationsDeleted > 0) {
            $_SESSION["message"] .= " (incluant $operationsDeleted opération(s) associée(s))";
        }
    } else {
        // Rollback if no vidange was deleted
        $db->rollback();
        $_SESSION["error"] = "Vidange non trouvée";
    }
    
} catch (PDOException $e) {
    // Rollback on error
    $db->rollback();
    $_SESSION["error"] = "Erreur lors de la suppression: " . $e->getMessage();
    
    // Log the error for debugging
    error_log("Error deleting vidange ID $id: " . $e->getMessage());
}

// Redirect to the updated liste-vidange page
header('location: /liste-vidange');
exit;
?>