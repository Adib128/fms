<?php
require_once "config.php";

// Initialize variables
$errors = [];
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        $errors[] = "ID de liquide invalide.";
    } else {
        try {
            // Check if liquide exists
            $stmt = $db->prepare("SELECT * FROM liquides WHERE id = ?");
            $stmt->execute([$id]);
            $liquide = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$liquide) {
                $errors[] = "Liquide non trouvé.";
            } else {
                // Delete the liquide (no usage check for now since column doesn't exist yet)
                $deleteStmt = $db->prepare("DELETE FROM liquides WHERE id = ?");
                $result = $deleteStmt->execute([$id]);
                
                if ($result) {
                    $success = "Liquide supprimé avec succès !";
                } else {
                    $errors[] = "Erreur lors de la suppression du liquide.";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la suppression du liquide : " . $e->getMessage();
        }
    }
    
    // Redirect back to list with success or error message
    if (!empty($errors)) {
        header('Location: ' . url('liste-liquides') . '?error=' . urlencode(implode(', ', $errors)));
    } else {
        $_SESSION['message'] = $success;
        header('Location: ' . url('liste-liquides'));
    }
    exit;
} else {
    // If not POST request, redirect to list
    header('Location: ' . url('liste-liquides'));
    exit;
}
?>
