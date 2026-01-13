<?php
require 'header.php';

$errors = [];
$success = '';
$filtre = null;

// Get filter ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header('Location: ' . url('liste-filtres'));
    exit;
}

// Get filter details
try {
    $stmt = $db->prepare('SELECT * FROM filter_types WHERE id = ?');
    $stmt->execute([$id]);
    $filtre = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$filtre) {
        header('Location: ' . url('liste-filtres'));
        exit;
    }
} catch (PDOException $e) {
    $errors[] = "Erreur lors de la récupération du type de filtre : " . $e->getMessage();
}

// Get compartments for the usage dropdown
try {
    $compartimentStmt = $db->query('SELECT DISTINCT name FROM compartiments ORDER BY name ASC');
    $compartiments = $compartimentStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $compartiments = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $usageFilter = trim($_POST['usageFilter'] ?? '');

    if (empty($name)) {
        $errors[] = "Le nom du type de filtre est obligatoire.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare('UPDATE filter_types SET name = :name, usageFilter = :usageFilter WHERE id = :id');
            $stmt->execute([
                ':name' => $name,
                ':usageFilter' => $usageFilter,
                ':id' => $id
            ]);
            
            $success = "Type de filtre modifié avec succès !";
            
            // Update the filter data
            $filtre['name'] = $name;
            $filtre['usageFilter'] = $usageFilter;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la modification du type de filtre : " . $e->getMessage();
        }
    }
}
?>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Modifier Type de Filtre</h1>
                <p class="mt-2 text-sm text-gray-600">Modifier les informations du type de filtre</p>
            </div>

            <?php if ($filtre): ?>
                <!-- Form Card -->
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <form class="space-y-8" method="POST" action="">
                        
                        <!-- Name Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="name" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Nom du filtre</label>
                            <div class="md:col-span-8">
                                <input type="text" id="name" name="name" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Ex: Filtre à air, Filtre à huile, Filtre à carburant"
                                    value="<?= htmlspecialchars($_POST['name'] ?? $filtre['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <!-- Usage Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="usageFilter" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Usage du filtre</label>
                            <div class="md:col-span-8">
                                <select id="usageFilter" name="usageFilter" 
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    data-skip-tom-select="true">
                                    <option value="" disabled selected>Choisir...</option>
                                    <?php foreach ($compartiments as $compartiment): ?>
                                        <option value="<?= htmlspecialchars($compartiment, ENT_QUOTES, 'UTF-8'); ?>" 
                                                <?= (($_POST['usageFilter'] ?? $filtre['usageFilter']) === $compartiment ? 'selected' : ''); ?>>
                                            <?= htmlspecialchars($compartiment, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                                <strong>Erreurs :</strong>
                                <ul class="mt-2 list-disc list-inside">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                            <a href="<?= url('liste-filtres') ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                                </svg>
                                Annuler
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 13l4 4L19 7"/>
                                </svg>
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
