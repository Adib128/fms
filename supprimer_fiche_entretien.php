<?php
// Start output buffering to prevent header issues
ob_start();

require 'header.php';

// Get fiche ID from URL
$id_fiche = $_GET['id'] ?? null;

if (!$id_fiche) {
    header('Location: /liste-fiche-entretien');
    exit;
}

// Get fiche details
try {
    $stmt = $db->prepare('SELECT * FROM fiche_entretien WHERE id_fiche = ?');
    $stmt->execute([$id_fiche]);
    $fiche = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fiche) {
        header('Location: /liste-fiche-entretien');
        exit;
    }
    
    // Get related data counts for confirmation
    $recordsStmt = $db->prepare('SELECT COUNT(*) as count FROM maintenance_records WHERE fiche_id = ?');
    $recordsStmt->execute([$id_fiche]);
    $recordsCount = $recordsStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $operationsStmt = $db->prepare('SELECT COUNT(*) as count FROM maintenance_operations mo 
                                  INNER JOIN maintenance_records mr ON mo.record_id = mr.id 
                                  WHERE mr.fiche_id = ?');
    $operationsStmt->execute([$id_fiche]);
    $operationsCount = $operationsStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count checklist items from both tables (handle missing tables gracefully)
    $checklistOldCount = 0;
    $checklistNewCount = 0;
    
    // Try to count from old checklist_fiche table (may not exist)
    try {
        $checklistOldStmt = $db->prepare('SELECT COUNT(*) as count FROM checklist_fiche WHERE fiche_id = ?');
        $checklistOldStmt->execute([$id_fiche]);
        $checklistOldCount = $checklistOldStmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        // Table doesn't exist, count is 0
        $checklistOldCount = 0;
    }
    
    // Try to count from new maintenance_checklist_status table
    try {
        $checklistNewStmt = $db->prepare('SELECT COUNT(*) as count FROM maintenance_checklist_status WHERE id_fiche = ?');
        $checklistNewStmt->execute([$id_fiche]);
        $checklistNewCount = $checklistNewStmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        // Table doesn't exist, count is 0
        $checklistNewCount = 0;
    }
    
    $checklistCount = $checklistOldCount + $checklistNewCount;
    
} catch (PDOException $e) {
    // Log error but don't output anything
    error_log("Error loading fiche for deletion: " . $e->getMessage());
    header('Location: /liste-fiche-entretien');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Delete checklist items from the old checklist_fiche table (if it exists)
        $deletedOldChecklist = 0;
        try {
            $deleteChecklist = $db->prepare('DELETE FROM checklist_fiche WHERE fiche_id = ?');
            $deleteChecklist->execute([$id_fiche]);
            $deletedOldChecklist = $deleteChecklist->rowCount();
        } catch (PDOException $e) {
            // Table doesn't exist or error, continue
            $deletedOldChecklist = 0;
        }
        
        // Delete checklist items from the new maintenance_checklist_status table
        $deletedNewChecklist = 0;
        try {
            $deleteMaintenanceChecklist = $db->prepare('DELETE FROM maintenance_checklist_status WHERE id_fiche = ?');
            $deleteMaintenanceChecklist->execute([$id_fiche]);
            $deletedNewChecklist = $deleteMaintenanceChecklist->rowCount();
        } catch (PDOException $e) {
            // Table doesn't exist or error, continue
            $deletedNewChecklist = 0;
        }
        
        // Delete maintenance operations (linked through maintenance_records)
        $deleteOperations = $db->prepare('DELETE mo FROM maintenance_operations mo 
                                         INNER JOIN maintenance_records mr ON mo.record_id = mr.id 
                                         WHERE mr.fiche_id = ?');
        $deleteOperations->execute([$id_fiche]);
        $deletedOperations = $deleteOperations->rowCount();
        
        // Delete maintenance records
        $deleteRecords = $db->prepare('DELETE FROM maintenance_records WHERE fiche_id = ?');
        $deleteRecords->execute([$id_fiche]);
        $deletedRecords = $deleteRecords->rowCount();
        
        // Delete the fiche
        $deleteFiche = $db->prepare('DELETE FROM fiche_entretien WHERE id_fiche = ?');
        $deleteFiche->execute([$id_fiche]);
        $deletedFiche = $deleteFiche->rowCount();
        
        $db->commit();
        
        // Redirect back to list with success message
        $_SESSION['success_message'] = "Fiche d'entretien supprimée avec succès ($deletedRecords véhicules, $deletedOperations opérations, " . 
                                     ($deletedOldChecklist + $deletedNewChecklist) . " items de checklist)";
        header('Location: /liste-fiche-entretien');
        exit;
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error deleting fiche $id_fiche: " . $e->getMessage());
        $error_message = 'Une erreur est survenue lors de la suppression de la fiche';
    }
}
?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Supprimer la fiche d'entretien</h1>
                        <p class="mt-2 text-sm text-gray-600">Cette action est irréversible</p>
                    </div>
                    <a href="/liste-fiche-entretien" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                        <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Retour
                    </a>
                </div>
            </div>

            <!-- Confirmation Card -->
            <div class="bg-white shadow-lg rounded-lg p-6 max-w-2xl mx-auto">
                <?php if (isset($error_message)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm text-red-800"><?php echo htmlspecialchars($error_message); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Warning Icon -->
                <div class="flex justify-center mb-6">
                    <div class="bg-red-100 rounded-full p-4">
                        <svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>

                <!-- Fiche Details -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Détails de la fiche à supprimer</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Numéro:</span>
                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($fiche['numd_doc']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Date:</span>
                            <span class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($fiche['date'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Véhicules:</span>
                            <span class="text-sm text-gray-900"><?php echo $recordsCount; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Opérations:</span>
                            <span class="text-sm text-gray-900"><?php echo $operationsCount; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Items de checklist:</span>
                            <span class="text-sm text-gray-900"><?php echo $checklistCount; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Warning Message -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-800">
                                <strong>Attention:</strong> Cette action supprimera définitivement:
                            </p>
                            <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                                <li>La fiche d'entretien et toutes ses informations</li>
                                <li>Tous les enregistrements de véhicules associés</li>
                                <li>Toutes les opérations de maintenance</li>
                                <li>Tous les items de checklist cochés</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Form -->
                <form method="POST" class="space-y-4">
                    <div class="flex justify-end space-x-3">
                        <a href="/details-fiche-entretien?id=<?php echo $id_fiche; ?>" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Annuler
                        </a>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <svg class="h-4 w-4 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Supprimer définitivement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>

<?php
// Flush output buffer
ob_end_flush();
?>
