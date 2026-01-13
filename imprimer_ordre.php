<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/security.php';

if (!isset($_GET['id'])) {
    die("ID manquant.");
}

$id_ordre = (int)$_GET['id'];

// Fetch OT details
$stmt = $db->prepare("
    SELECT o.*, 
           b.matricule_interne as code_vehicule, b.matricule as immatriculation,
           d.index_km as kilometrage,
           a.nom as atelier_nom,
           s.designation as system_nom
    FROM ordre o
    JOIN demande d ON o.id_demande = d.id
    JOIN bus b ON d.id_vehicule = b.id_bus
    LEFT JOIN atelier a ON o.id_atelier = a.id
    LEFT JOIN systeme s ON o.id_system = s.id
    WHERE o.id = ?
");
$stmt->execute([$id_ordre]);
$ordre = $stmt->fetch();

if (!$ordre) {
    die("Ordre introuvable.");
}

// Fetch anomalies
$stmt = $db->prepare("
    SELECT an.designation
    FROM ordre_anomalie oa
    JOIN anomalie an ON oa.id_anomalie = an.id
    WHERE oa.id_ordre = ?
");
$stmt->execute([$id_ordre]);
$anomalies = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch existing interventions with planned technicians
$stmt = $db->prepare("
    SELECT oi.id, i.libelle, oi.status
    FROM ordre_intervention oi
    JOIN intervention i ON oi.id_intervention = i.id
    WHERE oi.id_ordre = ?
");
$stmt->execute([$id_ordre]);
$interventions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($interventions as &$int) {
    $stmtTechs = $db->prepare("
        SELECT t.nom 
        FROM ordre_intervention_technicien oit
        JOIN maintenance t ON oit.id_technicien = t.id
        WHERE oit.id_ordre_intervention = ? AND oit.type = 'prévu'
    ");
    $stmtTechs->execute([$int['id']]);
    $int['techniciens_prevu'] = $stmtTechs->fetchAll(PDO::FETCH_COLUMN);
}
unset($int);


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Imprimer Ordre de Travail #<?= $ordre['numero'] ?></title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            height: 27.7cm; /* A4 height minus margins */
            display: flex;
            flex-direction: column;
        }
        .half {
            height: 50%;
            padding: 20px;
            box-sizing: border-box;
        }
        .top-half {
            border-bottom: 2px dashed #ccc;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0 0;
            font-size: 14pt;
            color: #666;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-item {
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 100px;
        }
        .value {
            font-weight: 600;
        }
        .section-title {
            font-size: 13pt;
            font-weight: bold;
            margin: 20px 0 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .anomalies-list {
            margin: 0;
            padding-left: 20px;
        }
        .bottom-half {
            display: flex;
            flex-direction: column;
        }
        .intervention-title {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            text-decoration: underline;
        }
        .writing-area {
            flex-grow: 1;
            border: 1px solid #eee;
            background-image: linear-gradient(#eee 1px, transparent 1px);
            background-size: 100% 2.5em;
            line-height: 2.5em;
            padding: 0 10px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <!-- Top Half -->
        <div class="half top-half">
            <div class="header">
                <h1>Sociéte reginale de transport de jendouba</h1>
                <h2>Direction Technique</h2>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <span class="label">N° Ordre:</span>
                    <span class="value"><?= htmlspecialchars($ordre['numero']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Date:</span>
                    <span class="value"><?= date('d/m/Y', strtotime($ordre['date'])) ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Véhicule:</span>
                    <span class="value"><?= htmlspecialchars($ordre['code_vehicule']) ?> (<?= htmlspecialchars($ordre['immatriculation']) ?>)</span>
                </div>
                <div class="detail-item">
                    <span class="label">Index:</span>
                    <span class="value"><?= number_format($ordre['kilometrage'], 0, ',', ' ') ?> KM</span>
                </div>
                <div class="detail-item">
                    <span class="label">Atelier:</span>
                    <span class="value"><?= htmlspecialchars($ordre['atelier_nom'] ?? '-') ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Système:</span>
                    <span class="value"><?= htmlspecialchars($ordre['system_nom'] ?? '-') ?></span>
                </div>
            </div>

            <div class="section-title">Anomalies signalées</div>
            <ul class="anomalies-list">
                <?php foreach ($anomalies as $an): ?>
                    <li><?= htmlspecialchars($an) ?></li>
                <?php endforeach; ?>
                <?php if (empty($anomalies)): ?>
                    <li>Aucune anomalie spécifiée</li>
                <?php endif; ?>
            </ul>

            <div class="section-title">Detail interventions</div>
            <ul class="anomalies-list">
                <?php foreach ($interventions as $int): ?>
                    <li>
                        <strong><?= htmlspecialchars($int['libelle']) ?></strong>
                        <?php if (!empty($int['techniciens_prevu'])): ?>
                            <br>
                            <small style="color: #666;">Techniciens prévus: <?= htmlspecialchars(implode(', ', $int['techniciens_prevu'])) ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>

                <?php if (empty($interventions)): ?>
                    <li>Aucune intervention prévue</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Bottom Half -->
        <div class="half bottom-half" style="margin-top: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px;">
                <div class="intervention-title" style="margin-bottom: 0; flex-grow: 1; text-align: left;">Intervention réaliser</div>
                <div style="text-align: center;">
                    <strong style="font-size: 10pt;">Signature Chef d'Atelier</strong>
                    <div style="height: 60px; width: 160px; border: 1px solid #ccc; margin-top: 5px;"></div>
                </div>
            </div>
            
            <div class="writing-area">
                <!-- Lines for manual writing -->
            </div>
            
            <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <div style="text-align: center;">
                    <strong style="font-size: 10pt;">Signature Technicien</strong>
                    <div style="height: 70px; width: 180px; border: 1px solid #ccc; margin-top: 5px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="no-print" style="position: fixed; bottom: 20px; right: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Imprimer à nouveau
        </button>
    </div>
</body>
</html>
