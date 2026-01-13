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

// Fetch Anomalies for dropdown
try {
    $anomalies = $db->query("
        SELECT a.id, a.designation, s.designation as systeme_nom 
        FROM anomalie a 
        JOIN systeme s ON a.id_system = s.id 
        ORDER BY s.designation, a.designation
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur lors du chargement des anomalies : " . $e->getMessage();
}

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = trim($_POST['libelle'] ?? '');
    $id_anomalie = !empty($_POST['id_anomalie']) ? (int)$_POST['id_anomalie'] : null;
    
    // Validate required fields
    if (empty($libelle)) {
        $errors[] = "Le libellé est requis.";
    }
    
    // If no errors, insert data
    if (empty($errors)) {
        try {
            $stmt = $db->prepare('INSERT INTO intervention (libelle, id_anomalie) VALUES (?, ?)');
            $stmt->execute([$libelle, $id_anomalie]);
            
            $_SESSION['message'] = "Intervention ajoutée avec succès.";
            header('Location: ' . url('liste-intervention'));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'ajout de l'intervention : " . $e->getMessage();
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
                <h1 class="page-title">Nouvelle intervention</h1>
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
                <span>Informations de l'intervention</span>
            </div>
            <div class="panel-body flex-grow min-h-[950px]">
                <form method="POST" class="space-y-6">
                    <!-- Libellé -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="libelle" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Libellé *</label>
                        <div class="md:col-span-8">
                            <input type="text" id="libelle" name="libelle" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Libellé de l'intervention"
                                value="<?= htmlspecialchars($_POST['libelle'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Anomalie -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="id_anomalie" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Anomalie (Optionnel)</label>
                        <div class="md:col-span-8">
                            <select id="id_anomalie" name="id_anomalie" class="w-full" data-skip-tom-select="true">
                                <option value="">Aucune (Générique)</option>
                                <?php foreach ($anomalies as $an): ?>
                                    <option value="<?= $an['id'] ?>" <?= (isset($_POST['id_anomalie']) && $_POST['id_anomalie'] == $an['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($an['systeme_nom'] . ' : ' . $an['designation']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="<?= url('liste-intervention') ?>" class="btn-default">
                            Annuler
                        </a>
                        <button type="submit" class="btn-primary">
                            Enregistrer l'intervention
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        new Choices('#id_anomalie', {
            searchEnabled: true,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: 'Rechercher une anomalie...'
        });
    });
</script>
</body>
</html>
