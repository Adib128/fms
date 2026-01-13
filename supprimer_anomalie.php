<?php
require 'config.php';
require_once __DIR__ . '/helpers/security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM anomalie WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Anomalie supprimée avec succès.";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Erreur lors de la suppression : " . $e->getMessage();
    }
} else {
    $_SESSION['message'] = "ID invalide.";
}

header('Location: ' . url('liste-anomalie'));
exit;
