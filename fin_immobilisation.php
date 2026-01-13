<?php
require 'config.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/helpers/security.php';

// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check
enforceRouteAccess('/liste-immobilisation', getCurrentUserProfile());

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $db->beginTransaction();

        // 1. Get vehicle ID from immobilization record
        $stmt = $db->prepare("SELECT id_vehicule FROM immobilisation WHERE id = ?");
        $stmt->execute([$id]);
        $immob = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($immob) {
            // 2. Update immobilization end date
            $stmtUpdate = $db->prepare("UPDATE immobilisation SET end_date = NOW() WHERE id = ?");
            $stmtUpdate->execute([$id]);

            // 3. Update vehicle status to 'Disponible'
            $stmtBus = $db->prepare("UPDATE bus SET etat = 'Disponible' WHERE id_bus = ?");
            $stmtBus->execute([$immob['id_vehicule']]);

            $db->commit();
            $_SESSION['message'] = "L'immobilisation a été terminée avec succès.";
        } else {
            $_SESSION['message'] = "Immobilisation introuvable.";
        }

    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['message'] = "Erreur : " . $e->getMessage();
    }
} else {
    $_SESSION['message'] = "ID invalide.";
}

header('Location: ' . url('liste-immobilisation'));
exit;
