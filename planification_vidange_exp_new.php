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
                <h1 class="page-title">Planification Vidange (EXP)</h1>
                <p class="text-sm text-slate-500">Liste des véhicules avec kilométrage restant avant vidange moteur</p>
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
                
                // Helper function to calculate km reste for a compartiment
                $calculateKmReste = function($idBus, $compartimentName, $freqVidange) use ($db) {
                    $dateVidange = null;
                    $kmParcouru = 0;
                    $kmReste = null;
                    
                    // Find the last vidange operation for the compartiment
                    // Use LOWER and TRIM for case-insensitive and whitespace-tolerant matching
                    $lastVidangeStmt = $db->prepare("
                        SELECT mr.date
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
                    
                    if ($lastVidange) {
                        $dateVidange = $lastVidange['date'];
                        
                        // Sum kilometrage after last vidange date
                        $kmStmt = $db->prepare("
                            SELECT COALESCE(SUM(kilometrage), 0) as total 
                            FROM kilometrage 
                            WHERE id_bus = ? 
                            AND date_kilometrage > ?
                        ");
                        $kmStmt->execute([$idBus, $dateVidange]);
                        $kmParcouru = (int)$kmStmt->fetchColumn();
                        
                        // Calculate remaining km
                        if ($freqVidange > 0) {
                            $kmReste = $freqVidange - $kmParcouru;
                        }
                    }
                    
                    return ['km_parcouru' => $kmParcouru, 'km_reste' => $kmReste];
                };
                
                // Calculate for Moteur
                $moteurData = $calculateKmReste($idBus, 'Moteur', $freqVidangeMoteur);
                
                // Calculate for Boite Vitesse (note: compartiment name is "Boite Vitesse" in database)
                $boiteData = $calculateKmReste($idBus, 'Boite Vitesse', $freqVidangeBoite);
                
                // Calculate for Pont
                $pontData = $calculateKmReste($idBus, 'Pont', $freqVidangePont);
                
                $tableData[] = [
                    'vehicule' => $bus['matricule_interne'] ?? 'N/A',
                    'agence' => $bus['agence'] ?? '-',
                    'freq_vidange' => $freqVidangeMoteur,
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

        <!-- Filters Section -->
        <div class="panel">
            <div class="panel-heading">
                <span>Filtres de recherche</span>
            </div>
            <div class="panel-body">
                <div class="search-form__row">
                    <div class="search-form__field">
                        <span>Freq Vidange</span>
                        <select id="filterFreqVidange" class="form-control" data-skip-tom-select="true">
                            <option value="">Tous</option>
                            <option value="0">Non défini (-)</option>
                            <?php
                            // Get unique freq_vidange values from tableData
                            $freqValues = array_unique(array_column($tableData, 'freq_vidange'));
                            sort($freqValues);
                            foreach ($freqValues as $freq):
                                if ($freq > 0):
                            ?>
                                <option value="<?= $freq ?>"><?= number_format($freq, 0, ',', ' ') ?> km</option>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </select>
                    </div>
                    
                    <div class="search-form__field">
                        <span>KLM RESTE MOTEUR</span>
                        <select id="filterKmResteMoteur" class="form-control" data-skip-tom-select="true">
                            <option value="">Tous</option>
                            <option value="negative">≤ 0 km (Urgent)</option>
                            <option value="low">1 - 1000 km (Attention)</option>
                            <option value="medium">1001 - 5000 km</option>
                            <option value="high">> 5000 km</option>
                            <option value="null">Non défini</option>
                        </select>
                    </div>
                    
                    <div class="search-form__actions">
                        <button type="button" id="clearFilters" class="btn btn-default">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18" />
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            </svg>
                            Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
        </div>

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
                                <th>Total KM après Vidange</th>
                                <th>Klm Reste Moteur</th>
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
        var table = $('#tab').DataTable({
            pageLength: 25,
            order: [[4, 'asc']], // Sort by KM Reste Moteur column (index 4) ascending
            orderFixed: [[4, 'asc']], // Keep this order fixed
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr.json'
            },
            columnDefs: [
                {
                    targets: [4, 5, 6], // Kilométrage Reste columns (Moteur, Boite, Pont)
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
                { 
                    extend: 'print', 
                    text: 'Imprimer', 
                    className: 'btn btn-default', 
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] },
                    title: 'Planification de la vidange selon le kilométrage d\'exploitation',
                    messageTop: 'Planification de la vidange selon le kilométrage d\'exploitation',
                    customize: function(win) {
                        $(win.document.body).find('h1').css({
                            'text-align': 'center',
                            'font-size': '18px',
                            'font-weight': 'bold',
                            'margin-bottom': '20px'
                        });
                    }
                },
                { extend: 'excel', text: 'Excel', className: 'btn btn-default', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
                { extend: 'pdf', text: 'PDF', className: 'btn btn-default', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } }
            ]
        });
        
        // Custom filter function for Freq Vidange and KLM RESTE MOTEUR
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var freqFilter = $('#filterFreqVidange').val();
                var kmResteFilter = $('#filterKmResteMoteur').val();
                
                // Filter by Freq Vidange (column index 1)
                if (freqFilter !== '') {
                    var freqValue = parseInt(freqFilter);
                    var rowFreqText = data[1].trim();
                    
                    // Handle "-" case (non défini)
                    if (rowFreqText === '-') {
                        // Show only if filter is "0" (non défini)
                        return freqValue === 0;
                    }
                    
                    // For numeric values, compare with filter
                    var rowFreq = parseInt(rowFreqText.replace(/\s/g, '')) || 0;
                    if (rowFreq !== freqValue) {
                        return false;
                    }
                }
                
                // Filter by KLM RESTE MOTEUR (column index 4)
                if (kmResteFilter !== '') {
                    var kmResteValue = data[4].trim();
                    
                    // Handle "-" case (null value)
                    if (kmResteValue === '-') {
                        if (kmResteFilter !== 'null') {
                            return false;
                        }
                        return true; // Show null values when "null" filter is selected
                    }
                    
                    var kmResteNum = parseInt(kmResteValue.replace(/\s/g, '')) || null;
                    
                    if (kmResteFilter === 'negative') {
                        // ≤ 0 km
                        if (kmResteNum === null || kmResteNum > 0) {
                            return false;
                        }
                    } else if (kmResteFilter === 'low') {
                        // 1 - 1000 km
                        if (kmResteNum === null || kmResteNum <= 0 || kmResteNum > 1000) {
                            return false;
                        }
                    } else if (kmResteFilter === 'medium') {
                        // 1001 - 5000 km
                        if (kmResteNum === null || kmResteNum <= 1000 || kmResteNum > 5000) {
                            return false;
                        }
                    } else if (kmResteFilter === 'high') {
                        // > 5000 km
                        if (kmResteNum === null || kmResteNum <= 5000) {
                            return false;
                        }
                    } else if (kmResteFilter === 'null') {
                        // Non défini - already handled above
                        return false;
                    }
                }
                
                return true;
            }
        );
        
        // Apply filters on change
        $('#filterFreqVidange, #filterKmResteMoteur').on('change', function() {
            table.draw();
        });
        
        // Clear filters button
        $('#clearFilters').on('click', function() {
            $('#filterFreqVidange').val('');
            $('#filterKmResteMoteur').val('');
            table.draw();
        });
    }
});
</script>
</body>
</html>
