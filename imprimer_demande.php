<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/security.php';

if (!isset($_GET['id'])) {
    die("ID manquant.");
}

$id_demande = (int)$_GET['id'];

// Fetch Demande details
$stmt = $db->prepare("
    SELECT d.*, 
           b.matricule_interne as code_vehicule, b.matricule as immatriculation,
           c.nom_prenom as chauffeur_nom,
           s.lib as station_nom
    FROM demande d
    JOIN bus b ON d.id_vehicule = b.id_bus
    LEFT JOIN chauffeur c ON d.id_chauffeur = c.id_chauffeur
    LEFT JOIN station s ON d.id_station = s.id_station
    WHERE d.id = ?
");
$stmt->execute([$id_demande]);
$demande = $stmt->fetch();

if (!$demande) {
    die("Demande introuvable.");
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Imprimer Demande de réparation #<?= $demande['numero'] ?></title>
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
            padding: 20px;
            box-sizing: border-box;
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
            width: 150px;
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
        .description-box {
            border: 1px solid #eee;
            padding: 15px;
            min-height: 200px;
            background-color: #f9f9f9;
            white-space: pre-wrap;
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
        <div class="header">
            <h1>Sociéte reginale de transport de jendouba</h1>
            <h2>Demande de réparation</h2>
        </div>

        <div class="details-grid">
            <div class="detail-item">
                <span class="label">N° Demande:</span>
                <span class="value"><?= htmlspecialchars($demande['numero']) ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Date:</span>
                <span class="value"><?= date('d/m/Y', strtotime($demande['date'])) ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Véhicule:</span>
                <span class="value"><?= htmlspecialchars($demande['code_vehicule']) ?> (<?= htmlspecialchars($demande['immatriculation']) ?>)</span>
            </div>
            <div class="detail-item">
                <span class="label">Kilométrage:</span>
                <span class="value"><?= number_format($demande['index_km'], 0, ',', ' ') ?> KM</span>
            </div>
            <div class="detail-item">
                <span class="label">Agence:</span>
                <span class="value"><?= htmlspecialchars($demande['station_nom'] ?? '-') ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Chauffeur:</span>
                <span class="value"><?= htmlspecialchars($demande['chauffeur_nom'] ?? '-') ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Priorité:</span>
                <span class="value"><?= htmlspecialchars($demande['priorite'] ?? 'Basse') ?></span>
            </div>
            <div class="detail-item">
                <span class="label">État:</span>
                <span class="value"><?= htmlspecialchars($demande['etat']) ?></span>
            </div>
            <?php if (!empty($demande['num_bdc'])): ?>
            <div class="detail-item">
                <span class="label">N° BDC:</span>
                <span class="value"><?= htmlspecialchars($demande['num_bdc']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="section-title">Description de l'anomalie</div>
        <div class="description-box">
            <?= htmlspecialchars($demande['description']) ?>
        </div>

        <div style="margin-top: 50px; display: flex; justify-content: space-between;">
            <div>
                <strong>Signature Chauffeur</strong>
                <div style="height: 80px; width: 200px; border: 1px solid #eee; margin-top: 10px;"></div>
            </div>
            <div>
                <strong>Signature Responsable</strong>
                <div style="height: 80px; width: 200px; border: 1px solid #eee; margin-top: 10px;"></div>
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
