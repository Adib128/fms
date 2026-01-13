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
    <div class="mx-auto flex max-w-9xl flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Planification Vidange (Index)</h1>
                <p class="text-sm text-slate-500">Liste des véhicules avec kilométrage restant avant vidange moteur basé sur l'index</p>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])) : ?>
            <div class="alert-success flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-none text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <div class="flex-1 text-sm">
                    <?= $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $tableData = [];
        $error = null;
        
        try {
            // Get all buses
            $busQuery = "SELECT b.id_bus, b.matricule_interne, b.matricule, b.type, b.freq_vidange_moteur, b.freq_vidange_boite, b.freq_vidange_pont, s.lib as agence 
                         FROM bus b 
                         LEFT JOIN station s ON b.id_station = s.id_station 
                         ORDER BY b.matricule_interne ASC";
            $buses = $db->query($busQuery)->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($buses as $bus) {
                $idBus = $bus['id_bus'];
                $freqVidangeMoteur = (int)$bus['freq_vidange_moteur'];
                $freqVidangeBoite = (int)$bus['freq_vidange_boite'];
                $freqVidangePont = (int)$bus['freq_vidange_pont'];
                
                // Get current total kilometrage from total_kilometrage table (used for all compartiments)
                $currentIndexStmt = $db->prepare("SELECT kilometrage FROM total_kilometrage WHERE id_bus = ?");
                $currentIndexStmt->execute([$idBus]);
                $currentIndexResult = $currentIndexStmt->fetch(PDO::FETCH_ASSOC);
                $currentIndex = $currentIndexResult ? (int)$currentIndexResult['kilometrage'] : null;
                
                // Helper function to calculate km reste based on index for a compartiment
                $calculateKmResteIndex = function($idBus, $compartimentName, $freqVidange, $currentIndex) use ($db) {
                    $indexLastVidange = null;
                    $kmParcouru = 0;
                    $kmReste = null;
                    
                    // Find the last vidange operation for the compartiment
                    // Get index_km from maintenance_records
                    $lastVidangeStmt = $db->prepare("
                        SELECT mr.index_km
                        FROM maintenance_operations mo
                        INNER JOIN maintenance_records mr ON mo.record_id = mr.id
                        LEFT JOIN compartiments c ON mo.compartiment_id = c.id
                        WHERE mr.id_bus = ?
                        AND LOWER(TRIM(c.name)) = LOWER(TRIM(?))
                        AND mo.oil_operation = 'Vidange'
                        ORDER BY mr.date DESC, mr.id DESC
                        LIMIT 1
                    ");
                    $lastVidangeStmt->execute([$idBus, $compartimentName]);
                    $lastVidange = $lastVidangeStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($lastVidange && !empty($lastVidange['index_km']) && $currentIndex !== null) {
                        $indexLastVidange = (int)$lastVidange['index_km'];
                        
                        // Calculate km parcouru = current index - last vidange index
                        $kmParcouru = $currentIndex - $indexLastVidange;
                        
                        // Calculate remaining km
                        if ($freqVidange > 0) {
                            $kmReste = $freqVidange - $kmParcouru;
                        }
                    }
                    
                    return ['index_last_vidange' => $indexLastVidange, 'km_parcouru' => $kmParcouru, 'km_reste' => $kmReste];
                };
                
                // Calculate for Moteur
                $moteurData = $calculateKmResteIndex($idBus, 'Moteur', $freqVidangeMoteur, $currentIndex);
                
                // Calculate for Boite Vitesse
                $boiteData = $calculateKmResteIndex($idBus, 'Boite Vitesse', $freqVidangeBoite, $currentIndex);
                
                // Calculate for Pont
                $pontData = $calculateKmResteIndex($idBus, 'Pont', $freqVidangePont, $currentIndex);
                
                $tableData[] = [
                    'vehicule' => $bus['matricule_interne'] ?? 'N/A',
                    'agence' => $bus['agence'] ?? '-',
                    'freq_vidange' => $freqVidangeMoteur,
                    'index_last_vidange' => $moteurData['index_last_vidange'],
                    'current_index' => $currentIndex,
                    'km_parcouru' => $moteurData['km_parcouru'],
                    'km_reste' => $moteurData['km_reste'],
                    'km_reste_boite' => $boiteData['km_reste'],
                    'km_reste_pont' => $pontData['km_reste']
                ];
            }
            
            // Sort by km_reste (lowest first, null at end)
            usort($tableData, function($a, $b) {
                if ($a['km_reste'] === null && $b['km_reste'] === null) return 0;
                if ($a['km_reste'] === null) return 1;
                if ($b['km_reste'] === null) return -1;
                return $a['km_reste'] <=> $b['km_reste'];
            });
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        ?>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <strong>Erreur:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Main Table -->
        <div class="panel">
            <div class="panel-heading">
                <span>Liste des véhicules</span>
                <span class="badge"><?= count($tableData) ?> véhicules</span>
            </div>
            <div class="panel-body overflow-x-auto">
                <?php if (empty($tableData)): ?>
                    <div class="text-center py-8 text-slate-500">
                        <p>Aucun véhicule trouvé dans la base de données.</p>
                    </div>
                <?php else: ?>
                    <table class="table" id="tab">
                        <thead>
                            <tr>
                                <th>Véhicule</th>
                                <th>Freq Vidange</th>
                                <th>Agence</th>
                                <th>Index Der Vidange</th>
                                <th>Index Actuel</th>
                                <th>Total KM après Vidange</th>
                                <th>Kilométrage Reste</th>
                                <th>Klm Reste Vidange Boite</th>
                                <th>Klm Reste Vidange Pont</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableData as $row): ?>
                                <?php
                                $rowClass = '';
                                $kmClass = '';
                                if ($row['km_reste'] !== null) {
                                    if ($row['km_reste'] <= 0) {
                                        $rowClass = 'bg-red-50';
                                        $kmClass = 'text-red-600 font-bold';
                                    } elseif ($row['km_reste'] <= 1000) {
                                        $rowClass = 'bg-amber-50';
                                        $kmClass = 'text-amber-600 font-semibold';
                                    } else {
                                        $kmClass = 'text-emerald-600';
                                    }
                                }
                                ?>
                                <?php
                                // Calculate classes for boite and pont
                                $boiteClass = '';
                                $pontClass = '';
                                if ($row['km_reste_boite'] !== null) {
                                    if ($row['km_reste_boite'] <= 0) {
                                        $boiteClass = 'text-red-600 font-bold';
                                    } elseif ($row['km_reste_boite'] <= 1000) {
                                        $boiteClass = 'text-amber-600 font-semibold';
                                    } else {
                                        $boiteClass = 'text-emerald-600';
                                    }
                                }
                                if ($row['km_reste_pont'] !== null) {
                                    if ($row['km_reste_pont'] <= 0) {
                                        $pontClass = 'text-red-600 font-bold';
                                    } elseif ($row['km_reste_pont'] <= 1000) {
                                        $pontClass = 'text-amber-600 font-semibold';
                                    } else {
                                        $pontClass = 'text-emerald-600';
                                    }
                                }
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td class="font-medium"><?= htmlspecialchars($row['vehicule']) ?></td>
                                    <td class="font-mono"><?= $row['freq_vidange'] > 0 ? number_format($row['freq_vidange'], 0, ',', ' ') : '-' ?></td>
                                    <td><?= htmlspecialchars($row['agence']) ?></td>
                                    <td class="font-mono"><?= $row['index_last_vidange'] !== null ? number_format($row['index_last_vidange'], 0, ',', ' ') : '-' ?></td>
                                    <td class="font-mono"><?= $row['current_index'] !== null ? number_format($row['current_index'], 0, ',', ' ') : '-' ?></td>
                                    <td class="font-mono"><?= $row['km_parcouru'] > 0 ? number_format($row['km_parcouru'], 0, ',', ' ') : '-' ?></td>
                                    <td class="font-mono <?= $kmClass ?>"><?= $row['km_reste'] !== null ? number_format($row['km_reste'], 0, ',', ' ') : '-' ?></td>
                                    <td class="font-mono <?= $boiteClass ?>"><?= $row['km_reste_boite'] !== null ? number_format($row['km_reste_boite'], 0, ',', ' ') : '-' ?></td>
                                    <td class="font-mono <?= $pontClass ?>"><?= $row['km_reste_pont'] !== null ? number_format($row['km_reste_pont'], 0, ',', ' ') : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
$(document).ready(function() {
    if ($('#tab').length) {
        $('#tab').DataTable({
            pageLength: 25,
            order: [[6, 'asc']], // Sort by KM Reste Moteur column (index 6) ascending
            orderFixed: [[6, 'asc']], // Keep this order fixed
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr.json'
            },
            columnDefs: [
                {
                    targets: [6, 7, 8], // Kilométrage Reste columns (Moteur, Boite, Pont)
                    type: 'num', // Treat as numeric
                    render: function(data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            // For sorting, return numeric value or a very large number for null
                            var num = parseInt(data.replace(/\s/g, '')) || null;
                            return num !== null ? num : 999999999;
                        }
                        return data; // For display, return as is
                    }
                }
            ],
            dom: 'Bfrtip',
            buttons: [
                { extend: 'print', text: 'Imprimer', className: 'btn btn-default', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] } },
                { extend: 'excel', text: 'Excel', className: 'btn btn-default', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] } },
                { extend: 'pdf', text: 'PDF', className: 'btn btn-default', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] } }
            ]
        });
    }
});
</script>
</body>
</html>

