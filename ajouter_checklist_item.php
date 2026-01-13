<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$error = '';

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php'; // Include database config
    
    $code = $_POST['code'] ?? '';
    $parti = $_POST['parti'] ?? '';
    $label = $_POST['label'] ?? '';
    
    if (empty($code) || empty($parti) || empty($label)) {
        $error = 'Le code, la partie et le libellé sont obligatoires';
    } else {
        try {
            $stmt = $db->prepare('INSERT INTO checklist_items (code, label, parti) VALUES (?, ?, ?)');
            $stmt->execute([$code, $label, $parti]);
            
            $_SESSION['message'] = 'Item de checklist ajouté avec succès';
            
            header('Location: /liste-checklist-items');
            exit;
        } catch (PDOException $e) {
            $error = 'Une erreur est survenue lors de l\'ajout de l\'item: ' . $e->getMessage();
        }
    }
}

// Now include header for display
require 'header.php';
?>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Ajouter un Item de Checklist</h1>
                        <p class="mt-2 text-sm text-gray-600">Créer un nouvel item pour les checklists d'entretien</p>
                    </div>
                    <a href="/liste-checklist-items" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                        <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Retour à la liste
                    </a>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white shadow-lg rounded-lg p-6 max-w-2xl mx-auto">
                <?php if (!empty($error)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm text-red-800"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Code *</label>
                        <input type="text" 
                               id="code" 
                               name="code" 
                               required 
                               value="<?= htmlspecialchars($_POST['code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Ex: M001">
                    </div>

                    <div>
                        <label for="label" class="block text-sm font-medium text-gray-700 mb-2">Libellé *</label>
                        <input type="text" 
                               id="label" 
                               name="label" 
                               required 
                               value="<?= htmlspecialchars($_POST['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Ex: Vérification niveau d'huile moteur">
                    </div>

                    <div>
                        <label for="parti" class="block text-sm font-medium text-gray-700 mb-2">Partie *</label>
                        <select id="parti" 
                                name="parti" 
                                required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="MOTEUR" <?= (($_POST['parti'] ?? '') === 'MOTEUR') ? 'selected' : '' ?>>MOTEUR</option>
                            <option value="TRANSMISSION" <?= (($_POST['parti'] ?? '') === 'TRANSMISSION') ? 'selected' : '' ?>>TRANSMISSION</option>
                            <option value="FREINAGE" <?= (($_POST['parti'] ?? '') === 'FREINAGE') ? 'selected' : '' ?>>FREINAGE</option>
                            <option value="SUSPENSION ET DIRECTION" <?= (($_POST['parti'] ?? '') === 'SUSPENSION ET DIRECTION') ? 'selected' : '' ?>>SUSPENSION ET DIRECTION</option>
                            <option value="ELECTRICITE AUTO" <?= (($_POST['parti'] ?? '') === 'ELECTRICITE AUTO') ? 'selected' : '' ?>>ELECTRICITE AUTO</option>
                            <option value="CARROSSERIE" <?= (($_POST['parti'] ?? '') === 'CARROSSERIE') ? 'selected' : '' ?>>CARROSSERIE</option>
                        </select>
                    </div>


                    <div class="flex justify-end space-x-3">
                        <a href="/liste-checklist-items" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Annuler
                        </a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="h-4 w-4 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Ajouter l'item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
