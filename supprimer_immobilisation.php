<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/helpers/security.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM immobilisation WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Immobilisation supprimée avec succès.";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

header('Location: ' . url('liste-immobilisation'));
exit;
