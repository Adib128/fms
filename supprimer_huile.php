<?php
require 'header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id === 0) {
        header('Location: ' . url('liste-huile'));
        exit;
    }

    try {
        // Check if oil type exists
        $checkStmt = $db->prepare('SELECT COUNT(*) as count FROM oil_types WHERE id = ?');
        $checkStmt->execute([$id]);
        $oilExists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($oilExists === 0) {
            $errors[] = "Type d'huile non trouvé.";
        } else {
            // Check if oil type is used in operations
            $usageStmt = $db->prepare('SELECT COUNT(*) as count FROM maintenance_operations WHERE oil_type_id = ?');
            $usageStmt->execute([$id]);
            $usageCount = $usageStmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($usageCount > 0) {
                $errors[] = "Impossible de supprimer ce type d'huile car il est utilisé dans {$usageCount} opération(s) d'entretien.";
            } else {
                // Delete the oil type
                $stmt = $db->prepare('DELETE FROM oil_types WHERE id = ?');
                $stmt->execute([$id]);
                
                $success = "Type d'huile supprimé avec succès !";
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur lors de la suppression du type d'huile : " . $e->getMessage();
    }
}

// Redirect back to list
if (empty($errors)) {
    header('Location: ' . url('liste-huile') . '?success=' . urlencode($success));
} else {
    header('Location: ' . url('liste-huile') . '?error=' . urlencode(implode(', ', $errors)));
}
exit;
?>
