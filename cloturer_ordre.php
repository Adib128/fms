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

if ($id === 0) {
    $_SESSION['message'] = "ID d'ordre invalide.";
    header('Location: ' . url('liste-ordre'));
    exit;
}

try {
    // Check if order exists and is not already closed
    $stmt = $db->prepare('SELECT numero, etat FROM ordre WHERE id = ?');
    $stmt->execute([$id]);
    $ordre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ordre) {
        $_SESSION['message'] = "Ordre de travail introuvable.";
    } elseif ($ordre['etat'] === 'cloturer') {
        $_SESSION['message'] = "L'ordre " . $ordre['numero'] . " est déjà clôturé.";
    } else {
        // Update status to cloturer
        $stmt = $db->prepare('UPDATE ordre SET etat = ? WHERE id = ?');
        $stmt->execute(['cloturer', $id]);
        $_SESSION['message'] = "L'ordre " . $ordre['numero'] . " a été clôturé avec succès.";
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Erreur lors de la clôture de l'ordre : " . $e->getMessage();
}

header('Location: ' . url('liste-ordre'));
exit;
