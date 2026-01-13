<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and helpers
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/helpers.php';

// Security check
require_once __DIR__ . '/helpers/security.php';
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

// Handle both GET and POST requests
$id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int) ($_GET['id'] ?? 0);
}

if ($id > 0) {
    try {
        // Check if atelier is used in reclamation table
        $checkStmt = $db->prepare('SELECT COUNT(*) as count FROM reclamation WHERE id_station = (SELECT id_station FROM station WHERE lib = (SELECT nom FROM atelier WHERE id = ?))');
        $checkStmt->execute([$id]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // For now, we'll just check if the atelier name exists in any related tables
        // Since atelier is a new table, we'll allow deletion
        // In the future, you can add proper foreign key checks
        
        $stmt = $db->prepare('DELETE FROM atelier WHERE id = ?');
        $stmt->execute([$id]);
        
        $_SESSION['message'] = "Atelier supprimé avec succès.";
    } catch (PDOException $e) {
        // Check if it's a foreign key constraint error
        if ($e->getCode() == '23000') {
            $_SESSION['message'] = "Impossible de supprimer cet atelier car il est utilisé dans d'autres enregistrements.";
        } else {
            $_SESSION['message'] = "Erreur lors de la suppression de l'atelier : " . $e->getMessage();
        }
    }
} else {
    $_SESSION['message'] = "ID d'atelier invalide.";
}

header('Location: ' . url('liste-atelier'));
exit;
