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
        // Cascading deletes are handled by foreign keys (ON DELETE CASCADE)
        // But we can verify status first if needed (e.g., prevent deleting Closed orders)
        
        $stmt = $db->prepare('DELETE FROM ordre WHERE id = ?');
        $stmt->execute([$id]);
        
        $_SESSION['message'] = "Ordre de travail supprimé avec succès.";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Erreur lors de la suppression de l'ordre : " . $e->getMessage();
    }
} else {
    $_SESSION['message'] = "ID d'ordre invalide.";
}

header('Location: ' . url('liste-ordre'));
exit;
