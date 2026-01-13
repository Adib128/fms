<?php
require 'header.php';

// Security check - only admin and responsable can access
$userProfile = $_SESSION['profile'] ?? null;
if (!in_array($userProfile, ['admin', 'responsable'])) {
    header('HTTP/1.0 403 Forbidden');
    header('Location: /403.php');
    exit;
}
?>

<div id="page-wrapper">
    <div class="mx-auto flex max-w-7xl flex-col gap-6">
        <?php if (isset($_SESSION["message"])) : ?>
            <div class="alert-success flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-none text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <div class="flex-1 text-sm">
                    <?= $_SESSION["message"]; unset($_SESSION["message"]); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION["error"])) : ?>
            <div class="border-l-4 border-red-500 bg-red-50 p-4 rounded flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-none text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <div class="flex-1 text-sm text-red-700">
                    <?= $_SESSION["error"]; unset($_SESSION["error"]); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Kilométrage Estimé</h1>
                <p class="text-sm text-slate-500">Estimer et insérer le kilométrage estimé basé sur la consommation de carburant</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="liste_kilometrage.php" class="btn-default">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                    Liste kilométrage
                </a>
            </div>
        </div>

        <!-- Explanation Panel -->
        <div class="panel">
            <div class="panel-heading">
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    Formule d'estimation
                </span>
            </div>
            <div class="panel-body">
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <p class="text-sm text-slate-700 mb-2"><strong>Kilométrage estimé</strong> = (Quantité GO / Consommation standard) × 100</p>
                    <ul class="text-sm text-slate-600 list-disc list-inside space-y-1">
                        <li><strong>Quantité GO</strong>: Quantité de gasoil (en litres) depuis la table <code class="bg-slate-200 px-1 rounded">carburant.qte_go</code></li>
                        <li><strong>Consommation standard</strong>: Consommation en L/100km depuis la table <code class="bg-slate-200 px-1 rounded">bus.conso</code></li>
                        <li><strong>Date</strong>: Date du document carburant depuis <code class="bg-slate-200 px-1 rounded">doc_carburant.date</code></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php
        // Get date filters from POST or set defaults
        $date_start = isset($_POST['date_start']) ? $_POST['date_start'] : '';
        $date_end = isset($_POST['date_end']) ? $_POST['date_end'] : '';
        
        // Calculate statistics based on date filters
        $totalQteGo = 0;
        $totalKmEstime = 0;
        $vehicleCount = 0;
        $entryCount = 0;
        
        if ($date_start && $date_end) {
            // Preview calculation for filtered dates
            $previewQuery = "
                SELECT 
                    c.id_carburant,
                    c.id_bus,
                    c.qte_go,
                    dc.date as date_carburant,
                    CASE 
                        WHEN b.conso IS NOT NULL AND CAST(b.conso AS DECIMAL(10,2)) > 0 
                        THEN ROUND((c.qte_go / CAST(b.conso AS DECIMAL(10,2))) * 100, 0)
                        ELSE 0
                    END as kilometrage_estime
                FROM carburant c
                INNER JOIN doc_carburant dc ON c.id_doc_carburant = dc.id_doc_carburant
                INNER JOIN bus b ON c.id_bus = b.id_bus
                WHERE dc.date >= :date_start
                AND dc.date <= :date_end
                AND c.qte_go > 0
                AND b.conso IS NOT NULL 
                AND CAST(b.conso AS DECIMAL(10,2)) > 0
            ";
            
            $previewStmt = $db->prepare($previewQuery);
            $previewStmt->bindParam(':date_start', $date_start);
            $previewStmt->bindParam(':date_end', $date_end);
            $previewStmt->execute();
            $previewData = $previewStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate totals
            $vehicleCount = count(array_unique(array_column($previewData, 'id_bus')));
            $entryCount = count($previewData);
            
            foreach ($previewData as $row) {
                $totalQteGo += $row['qte_go'];
                $totalKmEstime += $row['kilometrage_estime'];
            }
        }
        ?>

        <!-- Filter Panel -->
        <div class="panel">
            <div class="panel-heading">
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filtres de date
                </span>
            </div>
            <div class="panel-body">
                <form method="post" id="filter-form" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label for="date_start" class="block text-sm font-medium text-slate-700 mb-1">Date début</label>
                        <input type="date" id="date_start" name="date_start" value="<?= htmlspecialchars($date_start) ?>" class="input" required>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label for="date_end" class="block text-sm font-medium text-slate-700 mb-1">Date fin</label>
                        <input type="date" id="date_end" name="date_end" value="<?= htmlspecialchars($date_end) ?>" class="input" required>
                    </div>
                    <div>
                        <button type="submit" class="btn-primary">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                            Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Panel -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100">
                        <svg class="h-6 w-6 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 17H7A5 5 0 0 1 7 7h2"/>
                            <path d="M15 7h2a5 5 0 1 1 0 10h-2"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900"><?= number_format($entryCount, 0, ',', ' ') ?></p>
                        <p class="text-sm text-slate-500">Entrées carburant</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="12" rx="2"/>
                            <path d="M3 12h18"/>
                            <path d="M7 17v1"/>
                            <path d="M17 17v1"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900"><?= number_format($vehicleCount, 0, ',', ' ') ?></p>
                        <p class="text-sm text-slate-500">Véhicules</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100">
                        <svg class="h-6 w-6 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900"><?= number_format($totalQteGo, 0, ',', ' ') ?> L</p>
                        <p class="text-sm text-slate-500">Total Gasoil</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100">
                        <svg class="h-6 w-6 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3v18h18"/>
                            <path d="M7 15l4-4 3 3 4-5"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900"><?= number_format($totalKmEstime, 0, ',', ' ') ?> km</p>
                        <p class="text-sm text-slate-500">KM Estimé Total</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Panel -->
        <div class="panel">
            <div class="panel-heading">
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14"/>
                        <path d="M5 12h14"/>
                    </svg>
                    Actions
                </span>
            </div>
            <div class="panel-body">
                <?php if ($date_start && $date_end): ?>
                <form method="post" id="calcul-form" onsubmit="return confirmAction()">
                    <input type="hidden" name="action" value="insert_kilometrage">
                    <input type="hidden" name="date_start" value="<?= htmlspecialchars($date_start) ?>">
                    <input type="hidden" name="date_end" value="<?= htmlspecialchars($date_end) ?>">
                    <div class="flex flex-col gap-4">
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-amber-800">Attention</p>
                                    <p class="text-sm text-amber-700 mt-1">Cette action va insérer <strong><?= number_format($entryCount, 0, ',', ' ') ?></strong> enregistrements de kilométrage dans la base de données et mettre à jour les totaux de kilométrage pour chaque véhicule.</p>
                                    <p class="text-sm text-amber-700 mt-1">Période: <strong><?= date('d/m/Y', strtotime($date_start)) ?></strong> au <strong><?= date('d/m/Y', strtotime($date_end)) ?></strong></p>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="btn-success" <?= $entryCount == 0 ? 'disabled' : '' ?>>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                    <polyline points="17 21 17 13 7 13 7 21"/>
                                    <polyline points="7 3 7 8 15 8"/>
                                </svg>
                                Insérer le kilométrage
                            </button>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4"/>
                            <path d="M12 8h.01"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-800">Veuillez sélectionner une période</p>
                            <p class="text-sm text-blue-700 mt-1">Utilisez les filtres ci-dessus pour sélectionner une date de début et une date de fin, puis cliquez sur "Filtrer" pour voir les statistiques.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'insert_kilometrage') {
    try {
        $db->beginTransaction();
        
        $insertCount = 0;
        $updateCount = 0;
        $errors = [];
        
        // Get date filters from POST
        $date_start = isset($_POST['date_start']) ? $_POST['date_start'] : '';
        $date_end = isset($_POST['date_end']) ? $_POST['date_end'] : '';
        
        if (!$date_start || !$date_end) {
            throw new Exception("Veuillez sélectionner une période (date début et date fin)");
        }
        
        // Get all carburant entries with valid consumption data for the selected period
        // Each carburant entry is linked to a doc_carburant via id_doc_carburant
        // The date for kilometrage comes from doc_carburant.date
        $dataQuery = "
            SELECT 
                c.id_carburant,
                c.id_bus,
                c.qte_go,
                c.id_doc_carburant,
                dc.date as date_carburant,
                dc.num_doc_carburant,
                b.conso,
                CASE 
                    WHEN b.conso IS NOT NULL AND CAST(b.conso AS DECIMAL(10,2)) > 0 
                    THEN ROUND((c.qte_go / CAST(b.conso AS DECIMAL(10,2))) * 100, 0)
                    ELSE 0
                END as kilometrage_estime
            FROM carburant c
            INNER JOIN doc_carburant dc ON c.id_doc_carburant = dc.id_doc_carburant
            INNER JOIN bus b ON c.id_bus = b.id_bus
            WHERE dc.date >= :date_start
            AND dc.date <= :date_end
            AND c.qte_go > 0
            AND b.conso IS NOT NULL 
            AND CAST(b.conso AS DECIMAL(10,2)) > 0
            ORDER BY dc.date ASC, c.id_carburant ASC
        ";
        
        $dataStmt = $db->prepare($dataQuery);
        $dataStmt->bindParam(':date_start', $date_start);
        $dataStmt->bindParam(':date_end', $date_end);
        $dataStmt->execute();
        $dataRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($dataRows) == 0) {
            throw new Exception("Aucune donnée carburant valide trouvée pour la période sélectionnée");
        }
        
        // Prepare insert statement for kilometrage
        $insertStmt = $db->prepare('INSERT INTO kilometrage (id_kilometrage, date_kilometrage, kilometrage, num_doc, id_bus) VALUES (NULL, :date_kilometrage, :kilometrage, :num_doc, :id_bus)');
        
        // Track total kilometrage per bus for updating total_kilometrage
        $busKilometrageSum = [];
        
        foreach ($dataRows as $row) {
            // The date comes from doc_carburant.date (linked via carburant.id_doc_carburant)
            $dateKilometrage = $row['date_carburant']; // This is doc_carburant.date
            $kilometrage = (int)$row['kilometrage_estime'];
            $numDoc = (int)$row['id_doc_carburant']; // Use id_doc_carburant as reference
            $idBus = (int)$row['id_bus'];
            
            // Insert into kilometrage table
            $insertStmt->bindParam(':date_kilometrage', $dateKilometrage);
            $insertStmt->bindParam(':kilometrage', $kilometrage);
            $insertStmt->bindParam(':num_doc', $numDoc);
            $insertStmt->bindParam(':id_bus', $idBus);
            $insertStmt->execute();
            $insertCount++;
            
            // Accumulate kilometrage per bus
            if (!isset($busKilometrageSum[$idBus])) {
                $busKilometrageSum[$idBus] = 0;
            }
            $busKilometrageSum[$idBus] += $kilometrage;
        }
        
        // Update total_kilometrage for each bus
        $updateStmt = $db->prepare('UPDATE total_kilometrage SET kilometrage = kilometrage + :km_to_add WHERE id_bus = :id_bus');
        $checkStmt = $db->prepare('SELECT COUNT(*) FROM total_kilometrage WHERE id_bus = :id_bus');
        $insertTotalStmt = $db->prepare('INSERT INTO total_kilometrage (id_total_kilometrage, kilometrage, id_bus) VALUES (NULL, :kilometrage, :id_bus)');
        
        foreach ($busKilometrageSum as $idBus => $totalKm) {
            // Check if bus exists in total_kilometrage
            $checkStmt->bindParam(':id_bus', $idBus);
            $checkStmt->execute();
            $exists = $checkStmt->fetchColumn() > 0;
            
            if ($exists) {
                // Update existing record
                $updateStmt->bindParam(':km_to_add', $totalKm);
                $updateStmt->bindParam(':id_bus', $idBus);
                $updateStmt->execute();
            } else {
                // Insert new record
                $insertTotalStmt->bindParam(':kilometrage', $totalKm);
                $insertTotalStmt->bindParam(':id_bus', $idBus);
                $insertTotalStmt->execute();
            }
            $updateCount++;
        }
        
        $db->commit();
        
        $_SESSION["message"] = "Succès! $insertCount enregistrements de kilométrage insérés. $updateCount véhicules mis à jour dans total_kilometrage.";
        echo "<script>window.location.replace('calcul_kilometrage_2025.php');</script>";
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION["error"] = "Erreur lors de l'insertion: " . $e->getMessage();
        echo "<script>window.location.replace('calcul_kilometrage_2025.php');</script>";
        exit;
    }
}
?>

<script>
    function confirmAction() {
        const entryCount = <?= $entryCount ?>;
        const vehicleCount = <?= $vehicleCount ?>;
        return confirm('Êtes-vous sûr de vouloir insérer le kilométrage estimé?\n\nCette action va:\n- Insérer ' + entryCount.toLocaleString('fr-FR') + ' enregistrements dans la table kilometrage\n- Mettre à jour les totaux de kilométrage pour ' + vehicleCount.toLocaleString('fr-FR') + ' véhicules');
    }
</script>
</body>
</html>

