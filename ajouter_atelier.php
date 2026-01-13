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

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    
    // Validate required fields
    if (empty($nom)) {
        $errors[] = "Le nom est requis.";
    }
    
    // If no errors, insert data
    if (empty($errors)) {
        try {
            $stmt = $db->prepare('INSERT INTO atelier (nom) VALUES (?)');
            $stmt->execute([$nom]);
            
            $_SESSION['message'] = "Atelier ajouté avec succès.";
            header('Location: ' . url('liste-atelier'));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'ajout de l'atelier : " . $e->getMessage();
        }
    }
}

// Only include header.php if not already routed and not redirecting
if (!defined('ROUTED')) {
    require 'header.php';
}

// Enforce route access
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Nouvel atelier</h1>
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

        <div class="panel">
            <div class="panel-heading">
                <span>Informations de l'atelier</span>
            </div>
            <div class="panel-body">
                <form method="POST" class="space-y-6">
                    <!-- Nom -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="nom" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Nom *</label>
                        <div class="md:col-span-8">
                            <input type="text" id="nom" name="nom" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Nom de l'atelier"
                                value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('liste-atelier') ?>" class="btn-default">
                            Annuler
                        </a>
                        <button type="submit" class="btn-primary">
                            Enregistrer l'atelier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
