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

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM demande_passation WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Demande de passation supprimée avec succès.";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

header('Location: ' . url('passation-demande'));
exit;
