<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and helpers BEFORE header.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/helpers.php';

// Security check
require_once __DIR__ . '/helpers/security.php';

$errors = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id === 0) {
    $_SESSION['message'] = "ID d'anomalie invalide.";
    header('Location: ' . url('liste-anomalie'));
    exit;
}

// Fetch existing data
try {
    $stmt = $db->prepare("SELECT * FROM anomalie WHERE id = ?");
    $stmt->execute([$id]);
    $anomalie = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$anomalie) {
        $_SESSION['message'] = "Anomalie introuvable.";
        header('Location: ' . url('liste-anomalie'));
        exit;
    }
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designation = trim($_POST['designation'] ?? '');
    $id_system = (int) ($_POST['id_system'] ?? 0);

    if (empty($designation)) {
        $errors[] = "La désignation est requise.";
    }
    if ($id_system <= 0) {
        $errors[] = "Le système est requis.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE anomalie SET designation = ?, id_system = ? WHERE id = ?");
            $stmt->execute([$designation, $id_system, $id]);
            $_SESSION['message'] = "Anomalie modifiée avec succès.";
            header('Location: ' . url('liste-anomalie'));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la modification : " . $e->getMessage();
        }
    }
}

// Only include header.php if not already routed and not redirecting
if (!defined('ROUTED')) {
    require 'header.php';
}

// Enforce route access
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

// Fetch systems for dropdown, grouped by atelier
$systems = $db->query("
    SELECT s.id, s.designation, a.nom as atelier_nom 
    FROM systeme s 
    JOIN atelier a ON s.id_atelier = a.id 
    ORDER BY a.nom, s.designation
")->fetchAll(PDO::FETCH_ASSOC);

// Use POST data if available, otherwise use existing data
$formData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $anomalie;
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Modifier l'anomalie</h1>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="panel min-h-[1000px] flex flex-col">
            <div class="panel-heading">
                <span>Informations de l'anomalie</span>
            </div>
            <div class="panel-body flex-grow min-h-[950px]">
                <form method="POST" class="space-y-6">
                    <!-- Désignation -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="designation" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Désignation *</label>
                        <div class="md:col-span-8">
                            <input type="text" id="designation" name="designation" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Désignation de l'anomalie"
                                value="<?= htmlspecialchars($formData['designation'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Système -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="id_system" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Système *</label>
                        <div class="md:col-span-8">
                            <select name="id_system" id="id_system" class="w-full" required data-skip-tom-select="true">
                                <option value="">Sélectionner un système</option>
                                <?php 
                                $currentAtelier = null;
                                foreach ($systems as $sys): 
                                    if ($currentAtelier !== $sys['atelier_nom']) {
                                        if ($currentAtelier !== null) echo '</optgroup>';
                                        $currentAtelier = $sys['atelier_nom'];
                                        echo '<optgroup label="' . htmlspecialchars($currentAtelier) . '">';
                                    }
                                ?>
                                    <option value="<?= $sys['id'] ?>" <?= ((isset($formData['id_system']) && $formData['id_system'] == $sys['id'])) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sys['designation']) ?>
                                    </option>
                                <?php endforeach; 
                                if ($currentAtelier !== null) echo '</optgroup>';
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('liste-anomalie') ?>" class="btn-default">
                            Annuler
                        </a>
                        <button type="submit" class="btn-primary">
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        new Choices('#id_system', {
            searchEnabled: true,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: 'Sélectionner un système...'
        });
    });
</script>
</body>
</html>
