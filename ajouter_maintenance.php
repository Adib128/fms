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

// Fetch ateliers for dropdown
try {
    $ateliers = $db->query("SELECT * FROM atelier ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur lors du chargement des ateliers : " . $e->getMessage();
}

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = trim($_POST['matricule'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $id_atelier = (int) ($_POST['id_atelier'] ?? 0);
    
    // Validate required fields
    if (empty($nom)) {
        $errors[] = "Le nom est requis.";
    }
    if ($id_atelier <= 0) {
        $errors[] = "L'atelier est requis.";
    }
    
    // If no errors, insert data
    if (empty($errors)) {
        try {
            $stmt = $db->prepare('INSERT INTO maintenance (matricule, nom, specialite, id_atelier) VALUES (?, ?, ?, ?)');
            $stmt->execute([$matricule, $nom, $specialite, $id_atelier]);
            
            $_SESSION['message'] = "Technicien ajouté avec succès.";
            header('Location: ' . url('liste-maintenance'));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'ajout du technicien : " . $e->getMessage();
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
                <h1 class="page-title">Nouveau technicien</h1>
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
                <span>Informations du technicien</span>
            </div>
            <div class="panel-body flex-grow min-h-[950px]">
                <form method="POST" class="space-y-6">
                    <!-- Matricule -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="matricule" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Matricule</label>
                        <div class="md:col-span-8">
                            <input type="text" id="matricule" name="matricule"
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Matricule (optionnel)"
                                value="<?= htmlspecialchars($_POST['matricule'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Nom -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="nom" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Nom *</label>
                        <div class="md:col-span-8">
                            <input type="text" id="nom" name="nom" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Nom du technicien"
                                value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Spécialité -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="specialite" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Spécialité</label>
                        <div class="md:col-span-8">
                            <input type="text" id="specialite" name="specialite"
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Spécialité (optionnel)"
                                value="<?= htmlspecialchars($_POST['specialite'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Atelier -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="id_atelier" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Atelier *</label>
                        <div class="md:col-span-8">
                            <select id="id_atelier" name="id_atelier" required class="tom-select w-full">
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
                        <a href="<?= url('liste-maintenance') ?>" class="btn-default">
                            Annuler
                        </a>
                        <button type="submit" class="btn-primary">
                            Enregistrer le technicien
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.querySelectorAll('.tom-select').forEach((el) => {
        new TomSelect(el, {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });
</script>
</body>
</html>
