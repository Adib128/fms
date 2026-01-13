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
    $designation = trim($_POST['designation'] ?? '');
    $id_atelier = (int) ($_POST['id_atelier'] ?? 0);

    if (empty($designation)) {
        $errors[] = "La désignation est requise.";
    }
    if ($id_atelier <= 0) {
        $errors[] = "L'atelier est requis.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO systeme (designation, id_atelier) VALUES (?, ?)");
            $stmt->execute([$designation, $id_atelier]);
            $_SESSION['message'] = "Système ajouté avec succès.";
            header('Location: ' . url('liste-systeme'));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    }
}

// Only include header.php if not already routed and not redirecting
if (!defined('ROUTED')) {
    require 'header.php';
}

// Enforce route access
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

// Fetch ateliers for dropdown
$ateliers = $db->query("SELECT id, nom FROM atelier ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Ajouter un système</h1>
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
                <span>Informations du système</span>
            </div>
            <div class="panel-body">
                <form method="POST" class="space-y-6">
                    <!-- Désignation -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="designation" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Désignation *</label>
                        <div class="md:col-span-8">
                            <input type="text" id="designation" name="designation" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Désignation du système"
                                value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Atelier -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="id_atelier" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Atelier *</label>
                        <div class="md:col-span-8">
                            <select name="id_atelier" id="id_atelier" class="w-full" required>
                                <option value="">Sélectionner un atelier</option>
                                <?php foreach ($ateliers as $atelier): ?>
                                    <option value="<?= $atelier['id'] ?>" <?= (isset($_POST['id_atelier']) && $_POST['id_atelier'] == $atelier['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($atelier['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('liste-systeme') ?>" class="btn-default">
                            Annuler
                        </a>
                        <button type="submit" class="btn-primary">
                            Enregistrer le système
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
