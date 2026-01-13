<?php
// Only include header.php if not already routed
if (!defined('ROUTED')) {
    require 'header.php';
}

// Security check
require_once __DIR__ . '/helpers/security.php';
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

// Security check - only admin and responsable can access
$userProfile = $_SESSION['profile'] ?? null;
if (!in_array($userProfile, ['admin', 'responsable'])) {
    header('HTTP/1.0 403 Forbidden');
    header('Location: /403.php');
    exit;
}

// Prepare data for Tab 1: Planning selon kilometrage d'exploitation
$tableDataExp = [];
$errorExp = null;

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
        
        // Calculate for Boite Vitesse
        $boiteData = $calculateKmReste($idBus, 'Boite Vitesse', $freqVidangeBoite);
        
        // Calculate for Pont
        $pontData = $calculateKmReste($idBus, 'Pont', $freqVidangePont);
        
        $tableDataExp[] = [
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
    usort($tableDataExp, function($a, $b) {
        if ($a['km_reste'] === null && $b['km_reste'] === null) return 0;
        if ($a['km_reste'] === null) return 1;
        if ($b['km_reste'] === null) return -1;
        return $a['km_reste'] <=> $b['km_reste'];
    });
    
} catch (Exception $e) {
    $errorExp = $e->getMessage();
}

// Prepare data for Tab 2: Planning entretien selon kilometrage index
$tableDataIndex = [];
$errorIndex = null;

try {
    // Get all buses
    $busQuery = "SELECT b.id_bus, b.matricule_interne, b.matricule, b.type, b.freq_vidange_moteur, b.freq_vidange_boite, b.freq_vidange_pont, s.lib as agence 
                 FROM bus b 
                 LEFT JOIN station s ON b.id_station = s.id_station 
                 ORDER BY b.matricule_interne ASC";
    $busesIndex = $db->query($busQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($busesIndex as $bus) {
        $idBus = $bus['id_bus'];
        $freqVidangeMoteur = (int)$bus['freq_vidange_moteur'];
        $freqVidangeBoite = (int)$bus['freq_vidange_boite'];
        $freqVidangePont = (int)$bus['freq_vidange_pont'];
        
        // Get current total kilometrage from total_kilometrage table
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
        
        $tableDataIndex[] = [
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
    usort($tableDataIndex, function($a, $b) {
        if ($a['km_reste'] === null && $b['km_reste'] === null) return 0;
        if ($a['km_reste'] === null) return 1;
        if ($b['km_reste'] === null) return -1;
        return $a['km_reste'] <=> $b['km_reste'];
    });
    
} catch (Exception $e) {
    $errorIndex = $e->getMessage();
}
?>

<style>
    .planning-section {
        display: none;
    }
    .planning-section.active {
        display: block;
    }
</style>

<div id="page-wrapper">
    <div class="mx-auto flex max-w-9xl flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Planning entretien</h1>
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

        <!-- Planning selon kilometrage d'exploitation -->
        <div id="planning-exp" class="planning-section active">
            <?php if ($errorExp): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <strong>Erreur:</strong> <?= htmlspecialchars($errorExp) ?>
                </div>
            <?php endif; ?>

            <!-- Filters Section for EXP -->
            <div class="panel">
                <div class="panel-heading">
                    <span>Filtres de recherche</span>
                </div>
                <div class="panel-body">
                    <div class="search-form__row">
                        <div class="search-form__field">
                            <span>Type de planning</span>
                            <select id="planningTypeFilter" class="form-control" data-skip-tom-select="true">
                                <option value="exp">Planning selon kilometrage d'exploitation</option>
                                <option value="index">Planning entretien selon kilometrage index</option>
                            </select>
                        </div>
                        
                        <div class="search-form__field">
                            <span>Frequence Vidange</span>
                            <select id="filterFreqVidangeExp" class="form-control" data-skip-tom-select="true">
                                <option value="">Tous</option>
                                <option value="0">Non défini (-)</option>
                                <?php
                                $freqValues = array_unique(array_column($tableDataExp, 'freq_vidange'));
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
                            <span>kilometrage Reste pour vidange moteur</span>
                            <select id="filterKmResteMoteurExp" class="form-control" data-skip-tom-select="true">
                                <option value="">Tous</option>
                                <option value="negative">≤ 0 km (Urgent)</option>
                                <option value="low">1 - 1000 km (Attention)</option>
                                <option value="medium">1001 - 5000 km</option>
                                <option value="high">> 5000 km</option>
                                <option value="null">Non défini</option>
                            </select>
                        </div>
                        
                        <div class="search-form__actions">
                            <button type="button" id="clearFiltersExp" class="btn btn-default">
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
                    <span class="badge"><?= count($tableDataExp) ?> véhicules</span>
                </div>
                <div class="panel-body overflow-x-auto">
                    <?php if (empty($tableDataExp)): ?>
                        <div class="text-center py-8 text-slate-500">
                            <p>Aucun véhicule trouvé dans la base de données.</p>
                        </div>
                    <?php else: ?>
                        <table class="table" id="tab-exp-table">
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
                                <?php foreach ($tableDataExp as $row): ?>
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

        <!-- Planning entretien selon kilometrage index -->
        <div id="planning-index" class="planning-section">
            <?php if ($errorIndex): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <strong>Erreur:</strong> <?= htmlspecialchars($errorIndex) ?>
                </div>
            <?php endif; ?>

            <!-- Filters Section for Index -->
            <div class="panel">
                <div class="panel-heading">
                    <span>Filtres de recherche</span>
                </div>
                <div class="panel-body">
                    <div class="search-form__row">
                        <div class="search-form__field">
                            <span>Type de planning</span>
                            <select id="planningTypeFilterIndex" class="form-control" data-skip-tom-select="true">
                                <option value="exp">Planning selon kilometrage d'exploitation</option>
                                <option value="index">Planning entretien selon kilometrage index</option>
                            </select>
                        </div>
                        
                        <div class="search-form__field">
                            <span>Frequence Vidange</span>
                            <select id="filterFreqVidangeIndex" class="form-control" data-skip-tom-select="true">
                                <option value="">Tous</option>
                                <option value="0">Non défini (-)</option>
                                <?php
                                $freqValuesIndex = array_unique(array_column($tableDataIndex, 'freq_vidange'));
                                sort($freqValuesIndex);
                                foreach ($freqValuesIndex as $freq):
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
                            <span>kilometrage Reste pour vidange moteur</span>
                            <select id="filterKmResteMoteurIndex" class="form-control" data-skip-tom-select="true">
                                <option value="">Tous</option>
                                <option value="negative">≤ 0 km (Urgent)</option>
                                <option value="low">1 - 1000 km (Attention)</option>
                                <option value="medium">1001 - 5000 km</option>
                                <option value="high">> 5000 km</option>
                                <option value="null">Non défini</option>
                            </select>
                        </div>
                        
                        <div class="search-form__actions">
                            <button type="button" id="clearFiltersIndex" class="btn btn-default">
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
                    <span class="badge"><?= count($tableDataIndex) ?> véhicules</span>
                </div>
                <div class="panel-body overflow-x-auto">
                    <?php if (empty($tableDataIndex)): ?>
                        <div class="text-center py-8 text-slate-500">
                            <p>Aucun véhicule trouvé dans la base de données.</p>
                        </div>
                    <?php else: ?>
                        <table class="table" id="tab-index-table">
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
                                <?php foreach ($tableDataIndex as $row): ?>
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
    // Planning type filter functionality
    function switchPlanningType(planningType) {
        // Hide all planning sections
        $('.planning-section').removeClass('active');
        
        // Show selected planning section
        if (planningType === 'exp') {
            $('#planning-exp').addClass('active');
            // Initialize DataTable if not already initialized
            if (!$.fn.DataTable.isDataTable('#tab-exp-table')) {
                initExpTable();
            }
        } else if (planningType === 'index') {
            $('#planning-index').addClass('active');
            // Initialize DataTable if not already initialized
            if (!$.fn.DataTable.isDataTable('#tab-index-table')) {
                initIndexTable();
            }
        }
    }
    
    $('#planningTypeFilter, #planningTypeFilterIndex').on('change', function() {
        const planningType = $(this).val();
        
        // Update both filters to stay in sync
        $('#planningTypeFilter, #planningTypeFilterIndex').val(planningType);
        
        // Switch planning type
        switchPlanningType(planningType);
    });
    
    // Initialize DataTable for EXP (default)
    function initExpTable() {
        if ($('#tab-exp-table').length) {
            var tableExp = $('#tab-exp-table').DataTable({
                pageLength: 25,
                order: [[4, 'asc']],
                orderFixed: [[4, 'asc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr.json'
                },
                columnDefs: [
                    {
                        targets: [4, 5, 6],
                        type: 'num',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                var num = parseInt(data.replace(/\s/g, '')) || null;
                                return num !== null ? num : 999999999;
                            }
                            return data;
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
                        title: 'Planning selon kilometrage d\'exploitation',
                        messageTop: 'Planning selon kilometrage d\'exploitation'
                    },
                    { extend: 'excel', text: 'Excel', className: 'btn btn-default', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
                    { extend: 'pdf', text: 'PDF', className: 'btn btn-default', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } }
                ]
            });
            
            // Custom filter function
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'tab-exp-table') {
                        return true;
                    }
                    
                    var freqFilter = $('#filterFreqVidangeExp').val();
                    var kmResteFilter = $('#filterKmResteMoteurExp').val();
                    
                    if (freqFilter !== '') {
                        var freqValue = parseInt(freqFilter);
                        var rowFreqText = data[1].trim();
                        
                        if (rowFreqText === '-') {
                            return freqValue === 0;
                        }
                        
                        var rowFreq = parseInt(rowFreqText.replace(/\s/g, '')) || 0;
                        if (rowFreq !== freqValue) {
                            return false;
                        }
                    }
                    
                    if (kmResteFilter !== '') {
                        var kmResteValue = data[4].trim();
                        
                        if (kmResteValue === '-') {
                            if (kmResteFilter !== 'null') {
                                return false;
                            }
                            return true;
                        }
                        
                        var kmResteNum = parseInt(kmResteValue.replace(/\s/g, '')) || null;
                        
                        if (kmResteFilter === 'negative') {
                            if (kmResteNum === null || kmResteNum > 0) {
                                return false;
                            }
                        } else if (kmResteFilter === 'low') {
                            if (kmResteNum === null || kmResteNum <= 0 || kmResteNum > 1000) {
                                return false;
                            }
                        } else if (kmResteFilter === 'medium') {
                            if (kmResteNum === null || kmResteNum <= 1000 || kmResteNum > 5000) {
                                return false;
                            }
                        } else if (kmResteFilter === 'high') {
                            if (kmResteNum === null || kmResteNum <= 5000) {
                                return false;
                            }
                        } else if (kmResteFilter === 'null') {
                            return false;
                        }
                    }
                    
                    return true;
                }
            );
            
            $('#filterFreqVidangeExp, #filterKmResteMoteurExp').on('change', function() {
                tableExp.draw();
            });
            
            $('#clearFiltersExp').on('click', function() {
                $('#filterFreqVidangeExp').val('');
                $('#filterKmResteMoteurExp').val('');
                tableExp.draw();
            });
        }
    }
    
    // Initialize DataTable for Index
    function initIndexTable() {
        if ($('#tab-index-table').length) {
            var tableIndex = $('#tab-index-table').DataTable({
                pageLength: 25,
                order: [[6, 'asc']],
                orderFixed: [[6, 'asc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr.json'
                },
                columnDefs: [
                    {
                        targets: [6, 7, 8],
                        type: 'num',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                var num = parseInt(data.replace(/\s/g, '')) || null;
                                return num !== null ? num : 999999999;
                            }
                            return data;
                        }
                    }
                ],
                dom: 'Bfrtip',
                buttons: [
                    { 
                        extend: 'print', 
                        text: 'Imprimer', 
                        className: 'btn btn-default', 
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] },
                        title: 'Planning entretien selon kilometrage index',
                        messageTop: 'Planning entretien selon kilometrage index'
                    },
                    { extend: 'excel', text: 'Excel', className: 'btn btn-default', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] } },
                    { extend: 'pdf', text: 'PDF', className: 'btn btn-default', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] } }
                ]
            });
            
            // Custom filter function for Index table
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'tab-index-table') {
                        return true;
                    }
                    
                    var freqFilter = $('#filterFreqVidangeIndex').val();
                    var kmResteFilter = $('#filterKmResteMoteurIndex').val();
                    
                    if (freqFilter !== '') {
                        var freqValue = parseInt(freqFilter);
                        var rowFreqText = data[1].trim();
                        
                        if (rowFreqText === '-') {
                            return freqValue === 0;
                        }
                        
                        var rowFreq = parseInt(rowFreqText.replace(/\s/g, '')) || 0;
                        if (rowFreq !== freqValue) {
                            return false;
                        }
                    }
                    
                    if (kmResteFilter !== '') {
                        var kmResteValue = data[6].trim(); // Column 6 is "Kilométrage Reste"
                        
                        if (kmResteValue === '-') {
                            if (kmResteFilter !== 'null') {
                                return false;
                            }
                            return true;
                        }
                        
                        var kmResteNum = parseInt(kmResteValue.replace(/\s/g, '')) || null;
                        
                        if (kmResteFilter === 'negative') {
                            if (kmResteNum === null || kmResteNum > 0) {
                                return false;
                            }
                        } else if (kmResteFilter === 'low') {
                            if (kmResteNum === null || kmResteNum <= 0 || kmResteNum > 1000) {
                                return false;
                            }
                        } else if (kmResteFilter === 'medium') {
                            if (kmResteNum === null || kmResteNum <= 1000 || kmResteNum > 5000) {
                                return false;
                            }
                        } else if (kmResteFilter === 'high') {
                            if (kmResteNum === null || kmResteNum <= 5000) {
                                return false;
                            }
                        } else if (kmResteFilter === 'null') {
                            return false;
                        }
                    }
                    
                    return true;
                }
            );
            
            $('#filterFreqVidangeIndex, #filterKmResteMoteurIndex').on('change', function() {
                tableIndex.draw();
            });
            
            $('#clearFiltersIndex').on('click', function() {
                $('#filterFreqVidangeIndex').val('');
                $('#filterKmResteMoteurIndex').val('');
                tableIndex.draw();
            });
        }
    }
    
    // Initialize EXP table on page load (default)
    initExpTable();
});
</script>
</body>
</html>

