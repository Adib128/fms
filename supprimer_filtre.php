<?php
require 'header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id === 0) {
        header('Location: ' . url('liste-filtres'));
        exit;
    }

    try {
        // Check if filter is used in operations
        $checkStmt = $db->prepare('SELECT COUNT(*) as count FROM maintenance_operations WHERE filter_type_id = ?');
        $checkStmt->execute([$id]);
        $usageCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($usageCount > 0) {
            $errors[] = "Impossible de supprimer ce type de filtre car il est utilisé dans {$usageCount} opération(s) d'entretien.";
        } else {
            // Delete the filter
            $stmt = $db->prepare('DELETE FROM filter_types WHERE id = ?');
            $stmt->execute([$id]);
            
            $success = "Type de filtre supprimé avec succès !";
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur lors de la suppression du type de filtre : " . $e->getMessage();
    }
}

// Redirect back to list
if (empty($errors)) {
    header('Location: ' . url('liste-filtres') . '?success=' . urlencode($success));
} else {
    header('Location: ' . url('liste-filtres') . '?error=' . urlencode(implode(', ', $errors)));
}
exit;
?>
