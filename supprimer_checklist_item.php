<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get item ID from URL
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: /liste-checklist-items');
    exit;
}

// Handle deletion immediately (no confirmation needed)
require_once 'config.php'; // Include database config

try {
    $db->beginTransaction();
    
    // Try to delete from maintenance_checklist_status first (if table exists)
    try {
        $deleteUsage = $db->prepare('DELETE FROM maintenance_checklist_status WHERE checklist_item_id = ?');
        $deleteUsage->execute([$id]);
    } catch (PDOException $e) {
        // Table might not exist, continue with item deletion
        error_log("maintenance_checklist_status table not found during deletion: " . $e->getMessage());
    }
    
    // Delete the item
    $deleteItem = $db->prepare('DELETE FROM checklist_items WHERE id = ?');
    $deleteItem->execute([$id]);
    
    $db->commit();
    
    // Set success message and redirect
    $_SESSION['message'] = 'Item de checklist supprimé avec succès';
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log("Error deleting item: " . $e->getMessage());
    $_SESSION['error'] = 'Une erreur est survenue lors de la suppression de l\'item: ' . $e->getMessage();
}

// Redirect back to list
header('Location: /liste-checklist-items');
exit;
