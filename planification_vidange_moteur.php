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
                <h1 class="page-title">Planification Vidange - Moteur</h1>
                <p class="text-sm text-slate-500">Suivi des vidanges moteur basé sur le kilométrage estimé depuis la dernière vidange</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= url('ajouter-vidange') ?>" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Enregistrer Vidange
                </a>
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

        <!-- Legend Panel -->
        <div class="panel">
            <div class="panel-heading">
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    Légende et Calcul
                </span>
            </div>
            <div class="panel-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <p class="text-sm font-semibold text-slate-700 mb-2">Formule de calcul</p>
                        <p class="text-sm text-slate-600"><strong>KM Parcouru</strong> = Σ (qte_go / conso) × 100</p>
                        <p class="text-sm text-slate-600 mt-1"><strong>KM Restant</strong> = Fréquence vidange - KM Parcouru</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-100 text-red-800 text-sm font-medium">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            Urgent (≤ 0 km)
                        </span>
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-100 text-amber-800 text-sm font-medium">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            Attention (≤ 1000 km)
                        </span>
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 text-sm font-medium">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            OK (&gt; 1000 km)
                        </span>
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100 text-slate-800 text-sm font-medium">
                            <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                            Pas de vidange
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Get all buses with their consumption data
        $busQuery = "
            SELECT 
                b.id_bus,
                b.matricule_interne,
                b.matricule,
                b.type,
                b.marque,
                b.conso,
                b.freq_vidange_moteur,
                s.lib as station_name
            FROM bus b
            LEFT JOIN station s ON b.id_station = s.id_station
            WHERE b.conso IS NOT NULL AND CAST(b.conso AS DECIMAL(10,2)) > 0
            ORDER BY b.matricule_interne ASC
        ";
        
        $busResult = $db->query($busQuery);
        $buses = $busResult->fetchAll(PDO::FETCH_ASSOC);
        
        $planificationData = [];
        $stats = [
            'urgent' => 0,
            'attention' => 0,
            'ok' => 0,
            'no_vidange' => 0
        ];
        
        foreach ($buses as $bus) {
            $idBus = $bus['id_bus'];
            $conso = (float)$bus['conso'];
            $freqVidange = (int)$bus['freq_vidange_moteur'];
            
            // Find the last vidange for "Compartiment moteur" with nature_operation = 'vidange'
            $lastVidangeQuery = $db->prepare("
                SELECT v.id_vidange, v.date_vidange, v.indexe, ov.compartiment
                FROM vidange v
                INNER JOIN operations_vidange ov ON v.id_vidange = ov.id_vidange
                WHERE v.id_bus = :id_bus
                AND ov.compartiment = 'Compartiment moteur'
                AND ov.nature_operation = 'vidange'
                ORDER BY v.date_vidange DESC
                LIMIT 1
            ");
            $lastVidangeQuery->bindParam(':id_bus', $idBus);
            $lastVidangeQuery->execute();
            $lastVidange = $lastVidangeQuery->fetch(PDO::FETCH_ASSOC);
            
            $dateLastVidange = null;
            $kmParcouru = 0;
            $kmRestant = null;
            $status = 'no_vidange';
            
            if ($lastVidange) {
                $dateLastVidange = $lastVidange['date_vidange'];
                
                // Calculate km since last vidange using carburant consumption
                // Sum of (qte_go / conso) * 100 for all carburant entries after last vidange date
                $kmQuery = $db->prepare("
                    SELECT SUM(c.qte_go) as total_qte_go
                    FROM carburant c
                    INNER JOIN doc_carburant dc ON c.id_doc_carburant = dc.id_doc_carburant
                    WHERE c.id_bus = :id_bus
                    AND dc.date > :date_vidange
                    AND c.qte_go > 0
                ");
                $kmQuery->bindParam(':id_bus', $idBus);
                $kmQuery->bindParam(':date_vidange', $dateLastVidange);
                $kmQuery->execute();
                $kmResult = $kmQuery->fetch(PDO::FETCH_ASSOC);
                
                $totalQteGo = (float)($kmResult['total_qte_go'] ?? 0);
                
                if ($totalQteGo > 0 && $conso > 0) {
                    $kmParcouru = round(($totalQteGo / $conso) * 100);
                }
                
                // Calculate remaining km
                if ($freqVidange > 0) {
                    $kmRestant = $freqVidange - $kmParcouru;
                    
                    if ($kmRestant <= 0) {
                        $status = 'urgent';
                        $stats['urgent']++;
                    } elseif ($kmRestant <= 1000) {
                        $status = 'attention';
                        $stats['attention']++;
                    } else {
                        $status = 'ok';
                        $stats['ok']++;
                    }
                } else {
                    $status = 'no_vidange';
                    $stats['no_vidange']++;
                }
            } else {
                $stats['no_vidange']++;
            }
            
            $planificationData[] = [
                'id_bus' => $idBus,
                'matricule_interne' => $bus['matricule_interne'],
                'matricule' => $bus['matricule'],
                'type' => $bus['type'],
                'marque' => $bus['marque'],
                'station_name' => $bus['station_name'],
                'conso' => $conso,
                'freq_vidange' => $freqVidange,
                'date_last_vidange' => $dateLastVidange,
                'km_parcouru' => $kmParcouru,
                'km_restant' => $kmRestant,
                'status' => $status
            ];
        }
        
        // Sort by km_restant (urgent first)
        usort($planificationData, function($a, $b) {
            // No vidange goes to the end
            if ($a['status'] === 'no_vidange' && $b['status'] !== 'no_vidange') return 1;
            if ($b['status'] === 'no_vidange' && $a['status'] !== 'no_vidange') return -1;
            if ($a['status'] === 'no_vidange' && $b['status'] === 'no_vidange') return 0;
            
            // Sort by km_restant ascending (most urgent first)
            return ($a['km_restant'] ?? PHP_INT_MAX) <=> ($b['km_restant'] ?? PHP_INT_MAX);
        });
        ?>

        <!-- Statistics Panel -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-red-200 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100">
                        <svg class="h-6 w-6 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-600"><?= $stats['urgent'] ?></p>
                        <p class="text-sm text-slate-500">Urgent</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-amber-200 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100">
                        <svg class="h-6 w-6 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-amber-600"><?= $stats['attention'] ?></p>
                        <p class="text-sm text-slate-500">Attention</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-emerald-200 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-emerald-600"><?= $stats['ok'] ?></p>
                        <p class="text-sm text-slate-500">OK</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                        <svg class="h-6 w-6 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-600"><?= $stats['no_vidange'] ?></p>
                        <p class="text-sm text-slate-500">Sans vidange</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table Panel -->
        <div class="panel min-h-[600px]">
            <div class="panel-heading">
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="12" rx="2"/>
                        <path d="M3 12h18"/>
                        <path d="M7 17v1"/>
                        <path d="M17 17v1"/>
                    </svg>
                    Planification Vidange Moteur
                </span>
                <span class="badge">
                    <?= count($planificationData) ?> véhicules
                </span>
            </div>
            <div class="panel-body overflow-x-auto">
                <style>
                    .planif-table td, .planif-table th {
                        padding: 0.75rem 1rem !important;
                        vertical-align: middle;
                    }
                    .planif-table {
                        width: 100% !important;
                    }
                    .status-badge {
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                        padding: 0.375rem 0.75rem;
                        border-radius: 9999px;
                        font-size: 0.75rem;
                        font-weight: 600;
                    }
                    .status-urgent {
                        background-color: #fef2f2;
                        color: #dc2626;
                        border: 1px solid #fecaca;
                    }
                    .status-attention {
                        background-color: #fffbeb;
                        color: #d97706;
                        border: 1px solid #fde68a;
                    }
                    .status-ok {
                        background-color: #ecfdf5;
                        color: #059669;
                        border: 1px solid #a7f3d0;
                    }
                    .status-no-vidange {
                        background-color: #f8fafc;
                        color: #64748b;
                        border: 1px solid #e2e8f0;
                    }
                    .km-negative {
                        color: #dc2626;
                        font-weight: 700;
                    }
                    .km-warning {
                        color: #d97706;
                        font-weight: 600;
                    }
                    .km-ok {
                        color: #059669;
                        font-weight: 500;
                    }
                </style>
                <table class="table planif-table" id="planif-tab">
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th>Véhicule</th>
                            <th>Agence</th>
                            <th>Type / Marque</th>
                            <th class="text-right">Freq. Vidange (km)</th>
                            <th class="text-center">Dernière Vidange</th>
                            <th class="text-right">KM Parcouru</th>
                            <th class="text-right">KM Restant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($planificationData as $row): ?>
                            <?php
                            $statusClass = '';
                            $statusLabel = '';
                            $kmClass = '';
                            
                            switch ($row['status']) {
                                case 'urgent':
                                    $statusClass = 'status-urgent';
                                    $statusLabel = 'Urgent';
                                    $kmClass = 'km-negative';
                                    break;
                                case 'attention':
                                    $statusClass = 'status-attention';
                                    $statusLabel = 'Attention';
                                    $kmClass = 'km-warning';
                                    break;
                                case 'ok':
                                    $statusClass = 'status-ok';
                                    $statusLabel = 'OK';
                                    $kmClass = 'km-ok';
                                    break;
                                default:
                                    $statusClass = 'status-no-vidange';
                                    $statusLabel = 'Pas de vidange';
                                    break;
                            }
                            ?>
                            <tr>
                                <td>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="font-semibold"><?= htmlspecialchars($row['matricule_interne'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($row['matricule']): ?>
                                        <span class="text-xs text-slate-500 block"><?= htmlspecialchars($row['matricule'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['station_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= htmlspecialchars($row['type'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($row['marque']): ?>
                                        <span class="text-xs text-slate-500 block"><?= htmlspecialchars($row['marque'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-mono">
                                    <?= $row['freq_vidange'] > 0 ? number_format($row['freq_vidange'], 0, ',', ' ') : '-' ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['date_last_vidange']): ?>
                                        <?= date('d/m/Y', strtotime($row['date_last_vidange'])) ?>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-mono">
                                    <?php if ($row['date_last_vidange']): ?>
                                        <?= number_format($row['km_parcouru'], 0, ',', ' ') ?>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-mono <?= $kmClass ?>">
                                    <?php if ($row['km_restant'] !== null): ?>
                                        <?= number_format($row['km_restant'], 0, ',', ' ') ?>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
    const titreRapport = 'Planification Vidange Moteur';
    
    $('#planif-tab').DataTable({
        pageLength: 25,
        responsive: true,
        order: [], // Keep the PHP sorting (urgent first)
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr.json',
            processing: "Traitement en cours...",
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ enregistrements",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ enregistrements",
            infoEmpty: "Affichage de 0 à 0 sur 0 enregistrements",
            infoFiltered: "(filtré de _MAX_ enregistrements au total)",
            zeroRecords: "Aucun enregistrement trouvé",
            emptyTable: "Aucune donnée disponible"
        },
        dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
              "<'row'<'col-sm-12'tr>>" +
              "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [
            {
                extend: 'print',
                title: titreRapport,
                text: '<svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>Imprimer',
                className: 'btn btn-default text-sm',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
            },
            {
                extend: 'excelHtml5',
                title: titreRapport,
                text: '<svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Excel',
                className: 'btn btn-default text-sm',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
            },
            {
                extend: 'pdfHtml5',
                title: titreRapport,
                text: '<svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>PDF',
                className: 'btn btn-default text-sm',
                orientation: 'landscape',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
            }
        ]
    });
});
</script>
</body>
</html>


