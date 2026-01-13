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
    $_SESSION['message'] = "ID de demande invalide.";
    header('Location: ' . url('liste-demande'));
    exit;
}

try {
    // Check if demand exists and is not already closed
    $stmt = $db->prepare('SELECT numero, etat FROM demande WHERE id = ?');
    $stmt->execute([$id]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        $_SESSION['message'] = "Demande introuvable.";
    } elseif ($demande['etat'] === 'cloturer') {
        $_SESSION['message'] = "La demande " . $demande['numero'] . " est déjà clôturée.";
    } else {
        // Update status to cloturer
        $stmt = $db->prepare('UPDATE demande SET etat = ? WHERE id = ?');
        $stmt->execute(['cloturer', $id]);
        $_SESSION['message'] = "La demande " . $demande['numero'] . " a été clôturée avec succès.";
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Erreur lors de la clôture de la demande : " . $e->getMessage();
}

header('Location: ' . url('liste-demande'));
exit;
