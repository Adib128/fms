<?php
require 'header.php';
?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Détails Vidange</h1>
                        <p class="mt-2 text-sm text-gray-600">Informations complètes sur la vidange</p>
                    </div>
                    <a href="/liste-vidange" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                        <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Retour à la liste
                    </a>
                </div>
            </div>

            <?php
            // Get vidange ID from URL
            $id_vidange = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id_vidange === 0) {
                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>ID de vidange invalide</div>";
                echo "<script>setTimeout(() => window.location.replace('/liste-vidange'), 2000)</script>";
                exit;
            }
            
            // Get vidange details with bus information
            $vidange_query = $db->prepare("SELECT v.*, b.matricule_interne, b.type as bus_type FROM vidange v INNER JOIN bus b ON v.id_bus = b.id_bus WHERE v.id_vidange = ?");
            $vidange_query->execute([$id_vidange]);
            $vidange = $vidange_query->fetch(PDO::FETCH_ASSOC);
            
            if (!$vidange) {
                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>Vidange non trouvée</div>";
                echo "<script>setTimeout(() => window.location.replace('/liste-vidange'), 2000)</script>";
                exit;
            }
            
            // Get operations for this vidange
            $operations_query = $db->prepare("SELECT ov.*, h.libelle as huile_libelle FROM operations_vidange ov LEFT JOIN huile h ON ov.id_huile = h.id_huile WHERE ov.id_vidange = ? ORDER BY ov.id_operation");
            $operations_query->execute([$id_vidange]);
            $operations = $operations_query->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <!-- Main Content Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                
                <!-- Vidange Information -->
                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">Informations générales</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Date -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-2">Date de vidange</label>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo $vidange['date_vidange'] ? date('d/m/Y', strtotime($vidange['date_vidange'])) : '-'; ?>
                            </p>
                        </div>
                        
                        <!-- Véhicule -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-2">Véhicule</label>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo htmlspecialchars($vidange['matricule_interne'] . ' - ' . $vidange['bus_type']); ?>
                            </p>
                        </div>
                        
                        <!-- Index -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-2">Index</label>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo number_format($vidange['indexe'], 0, ',', ' '); ?> km
                            </p>
                        </div>
                        
                        <!-- Référence Document -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-2">Réf. Document</label>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo $vidange['ref_doc'] ? htmlspecialchars($vidange['ref_doc']) : '-'; ?>
                            </p>
                        </div>
                        
                        <!-- Kilométrage -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-2">Kilométrage enregistré</label>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo number_format($vidange['kilometrage'], 0, ',', ' '); ?> km
                            </p>
                        </div>
                        
                        <!-- Nombre d'opérations -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-2">Nombre d'opérations</label>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo count($operations); ?> opération(s)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Operations Section -->
                <div class="border-t border-gray-200 pt-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900">Opérations effectuées</h2>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            <?php echo count($operations); ?> opération(s)
                        </span>
                    </div>
                    
                    <?php if (empty($operations)): ?>
                        <div class="text-center py-12 bg-gray-50 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune opération</h3>
                            <p class="mt-1 text-sm text-gray-500">Aucune opération n'a été enregistrée pour cette vidange.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($operations as $index => $operation): ?>
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-3">
                                                    Opération <?php echo $index + 1; ?>
                                                </span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    <?php echo $operation['categorie'] === 'Huiles' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'; ?>">
                                                    <?php echo htmlspecialchars($operation['categorie']); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <?php if ($operation['categorie'] === 'Huiles'): ?>
                                                    <div>
                                                        <span class="text-sm text-gray-500">Compartiment:</span>
                                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($operation['compartiment']); ?></p>
                                                    </div>
                                                    <div>
                                                        <span class="text-sm text-gray-500">Nature:</span>
                                                        <p class="font-medium text-gray-900">
                                                            <?php 
                                                            $nature_labels = [
                                                                'apoint' => 'Appoint',
                                                                'vidange' => 'Vidange', 
                                                                'controle' => 'Contrôle'
                                                            ];
                                                            echo $nature_labels[$operation['nature_operation']] ?? htmlspecialchars($operation['nature_operation']);
                                                            ?>
                                                        </p>
                                                    </div>
                                                    <?php if ($operation['huile_libelle']): ?>
                                                    <div>
                                                        <span class="text-sm text-gray-500">Huile:</span>
                                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($operation['huile_libelle']); ?></p>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if ($operation['quantite']): ?>
                                                    <div>
                                                        <span class="text-sm text-gray-500">Quantité:</span>
                                                        <p class="font-medium text-gray-900"><?php echo $operation['quantite']; ?> L</p>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                <?php elseif ($operation['categorie'] === 'Filtres'): ?>
                                                    <div>
                                                        <span class="text-sm text-gray-500">Compartiment:</span>
                                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($operation['compartiment']); ?></p>
                                                    </div>
                                                    <div>
                                                        <span class="text-sm text-gray-500">Type filtre:</span>
                                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($operation['type_filtre']); ?></p>
                                                    </div>
                                                    <?php if ($operation['action_filtre']): ?>
                                                    <div>
                                                        <span class="text-sm text-gray-500">Action:</span>
                                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($operation['action_filtre']); ?></p>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="border-t border-gray-200 pt-8 mt-8">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="/modifier-vidange?id=<?php echo $id_vidange; ?>" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Modifier la vidange
                        </a>
                        
                        <button onclick="confirmDelete(<?php echo $id_vidange; ?>)" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Supprimer la vidange
                        </button>
                        
                        <a href="/liste-vidange" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Retour à la liste
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Delete confirmation function
function confirmDelete(id) {
    if (confirm('Voulez-vous vraiment supprimer cette vidange ?')) {
        window.location.href = '/supprimer-vidange?id=' + id;
    }
    return false;
}
</script>

</body>
</html>
