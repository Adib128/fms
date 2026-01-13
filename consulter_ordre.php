<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and helpers
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/helpers.php';

// Security check
require_once __DIR__ . '/helpers/security.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id === 0) {
    $_SESSION['message'] = "ID d'ordre invalide.";
    header('Location: ' . url('liste-ordre'));
    exit;
}

// Fetch existing order
$ordre = null;
try {
    $stmt = $db->prepare('
        SELECT o.*, 
               d.numero as demande_numero, d.date as demande_date, d.index_km, d.description as demande_description,
               b.matricule_interne as code_vehicule, b.matricule as immatriculation, b.marque,
               a.nom as atelier_nom,
               s.designation as system_nom
        FROM ordre o
        JOIN demande d ON o.id_demande = d.id
        JOIN bus b ON d.id_vehicule = b.id_bus
        LEFT JOIN atelier a ON o.id_atelier = a.id
        LEFT JOIN systeme s ON o.id_system = s.id
        WHERE o.id = ?
    ');
    $stmt->execute([$id]);
    $ordre = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ordre) {
        $_SESSION['message'] = "Ordre introuvable.";
        header('Location: ' . url('liste-ordre'));
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Erreur lors de la récupération de l'ordre.";
    header('Location: ' . url('liste-ordre'));
    exit;
}

// Fetch interventions
$interventions = [];
try {
    $stmt = $db->prepare("
        SELECT oi.*, i.libelle as intervention_nom
        FROM ordre_intervention oi
        JOIN intervention i ON oi.id_intervention = i.id
        WHERE oi.id_ordre = ?
    ");
    $stmt->execute([$id]);
    $interventions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Technicians and Articles for each intervention
    foreach ($interventions as &$int) {
        // Fetch Technicians (Planned and Realized)
        $stmtTechs = $db->prepare("
            SELECT t.nom, oit.type
            FROM ordre_intervention_technicien oit
            JOIN maintenance t ON oit.id_technicien = t.id
            WHERE oit.id_ordre_intervention = ?
        ");
        $stmtTechs->execute([$int['id']]);
        $all_techs = $stmtTechs->fetchAll(PDO::FETCH_ASSOC);
        
        $int['techniciens_prevu'] = array_filter($all_techs, fn($t) => $t['type'] === 'prévu');
        $int['techniciens_realise'] = array_filter($all_techs, fn($t) => $t['type'] === 'réalisé');


        // Fetch Articles
        $stmtArts = $db->prepare("
            SELECT a.designiation, a.code
            FROM ordre_intervention_article oia
            JOIN article a ON oia.id_article = a.id
            WHERE oia.id_ordre_intervention = ?
        ");
        $stmtArts->execute([$int['id']]);
        $int['articles'] = $stmtArts->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($int);
} catch (PDOException $e) {
    // Silent error
}

// Fetch anomalies
$anomalies = [];
try {
    $stmt = $db->prepare("
        SELECT a.designation
        FROM ordre_anomalie oa
        JOIN anomalie a ON oa.id_anomalie = a.id
        WHERE oa.id_ordre = ?
    ");
    $stmt->execute([$id]);
    $anomalies = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Silent error
}

require 'header.php';
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Détails de l'ordre de travail</h1>
                <p class="text-sm text-slate-500">Numéro : <?= htmlspecialchars($ordre['numero']) ?></p>
            </div>
            <div class="flex gap-2">
                <a href="<?= url('liste-ordre') ?>" class="btn-default">
                    <span class="iconify h-5 w-5 mr-2" data-icon="mdi:arrow-left"></span>
                    Retour à la liste
                </a>
                <a href="<?= url('imprimer-ordre') ?>?id=<?= $ordre['id'] ?>" target="_blank" class="btn-default">
                    <span class="iconify h-5 w-5 mr-2" data-icon="mdi:printer"></span>
                    Imprimer
                </a>
                <?php if ($userProfile !== 'responsable'): ?>
                <a href="<?= url('modifier-ordre') ?>?id=<?= $ordre['id'] ?>" class="btn-primary">
                    <span class="iconify h-5 w-5 mr-2" data-icon="mdi:pencil"></span>
                    Modifier
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Main Info & Interventions -->
            <div class="lg:col-span-2 space-y-6">
                <div class="panel">
                    <div class="panel-heading">
                        <span>Informations de l'Ordre</span>
                    </div>
                    <div class="panel-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Date de l'ordre</label>
                                <p class="text-sm font-semibold text-gray-900"><?= date('d/m/Y', strtotime($ordre['date'])) ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Atelier</label>
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($ordre['atelier_nom'] ?? '-') ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Système</label>
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($ordre['system_nom'] ?? '-') ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Demande associée</label>
                                <p class="text-sm font-semibold text-gray-900">
                                    <a href="<?= url('consulter-demande') ?>?id=<?= $ordre['id_demande'] ?>" class="text-blue-600 hover:underline">
                                        <?= htmlspecialchars($ordre['demande_numero']) ?>
                                    </a>
                                </p>
                            </div>
                        </div>

                        <?php if (!empty($anomalies)): ?>
                            <div class="mt-6 pt-6 border-t border-gray-100">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Anomalies signalées</label>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($anomalies as $an): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-100">
                                            <?= htmlspecialchars($an) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <span>Detail interventions</span>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($interventions)): ?>
                            <p class="text-sm text-gray-500 text-center py-4">Aucune intervention enregistrée.</p>
                        <?php else: ?>
                            <div class="grid grid-cols-1 gap-4">
                                <?php foreach ($interventions as $int): ?>
                                    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                                        <div class="flex justify-between items-start mb-3">
                                            <h4 class="text-sm font-bold text-gray-900"><?= htmlspecialchars($int['intervention_nom']) ?></h4>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $int['status'] === 'réaliser' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-orange-100 text-orange-800 border border-orange-200' ?>">
                                                <?= htmlspecialchars($int['status']) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <div class="flex flex-col gap-1 text-xs text-gray-600">
                                                <div class="flex items-center">
                                                    <span class="iconify h-4 w-4 mr-2 text-blue-400" data-icon="mdi:account-clock"></span>
                                                    <span class="font-medium mr-1">Techniciens Prévus:</span>
                                                </div>
                                                <div class="flex flex-wrap gap-1 ml-6">
                                                    <?php if (!empty($int['techniciens_prevu'])): ?>
                                                        <?php foreach ($int['techniciens_prevu'] as $tech): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                                <?= htmlspecialchars($tech['nom']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 italic">Non spécifié</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-1 text-xs text-gray-600">
                                                <div class="flex items-center">
                                                    <span class="iconify h-4 w-4 mr-2 text-green-400" data-icon="mdi:account-check"></span>
                                                    <span class="font-medium mr-1">Techniciens Réalisés:</span>
                                                </div>
                                                <div class="flex flex-wrap gap-1 ml-6">
                                                    <?php if (!empty($int['techniciens_realise'])): ?>
                                                        <?php foreach ($int['techniciens_realise'] as $tech): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-green-50 text-green-700 border border-green-100">
                                                                <?= htmlspecialchars($tech['nom']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 italic">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="flex flex-col gap-1 text-xs text-gray-600">
                                                <div class="flex items-center">
                                                    <span class="iconify h-4 w-4 mr-2 text-gray-400" data-icon="mdi:package-variant"></span>
                                                    <span class="font-medium mr-1">Articles PDR:</span>
                                                </div>
                                                <div class="flex flex-wrap gap-1 ml-6">
                                                    <?php if (!empty($int['articles'])): ?>
                                                        <?php foreach ($int['articles'] as $art): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800" title="<?= htmlspecialchars($art['code']) ?>">
                                                                <?= htmlspecialchars($art['designiation']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex items-center text-xs text-gray-600">
                                                <span class="iconify h-4 w-4 mr-2 text-gray-400" data-icon="mdi:calendar-check"></span>
                                                <span class="font-medium mr-1">Réalisé le:</span> <?= $int['realised_at'] ? date('d/m/Y H:i', strtotime($int['realised_at'])) : '-' ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($int['description'])): ?>
                                            <div class="mt-3 pt-3 border-t border-gray-100">
                                                <p class="text-xs text-gray-500 italic">
                                                    <?= nl2br(htmlspecialchars($int['description'])) ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Vehicle & Status -->
            <div class="space-y-6">
                <div class="panel">
                    <div class="panel-heading">
                        <span>Véhicule</span>
                    </div>
                    <div class="panel-body space-y-4">
                        <div class="flex items-center gap-4 p-3 bg-blue-50 rounded-xl border border-blue-100">
                            <div class="h-12 w-12 rounded-lg bg-blue-600 flex items-center justify-center text-white">
                                <span class="iconify h-8 w-8" data-icon="mdi:bus"></span>
                            </div>
                            <div>
                                <p class="text-xs text-blue-600 font-medium uppercase tracking-wider">Matricule Interne</p>
                                <p class="text-lg font-bold text-blue-900"><?= htmlspecialchars($ordre['code_vehicule']) ?></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Immatriculation</label>
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($ordre['immatriculation']) ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Marque</label>
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($ordre['marque']) ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Kilométrage</label>
                                <p class="text-sm font-semibold text-gray-900"><?= number_format($ordre['index_km'], 0, ',', ' ') ?> KM</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <span>Statut de l'ordre</span>
                    </div>
                    <div class="panel-body">
                        <?php
                        $etatClass = match ($ordre['etat']) {
                            'Ouvert', 'ouvert', 'En cours' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'Valider', 'valider' => 'bg-green-100 text-green-800 border-green-200',
                            'Cloturer', 'cloturer' => 'bg-slate-900 text-white border-slate-900',
                            default => 'bg-slate-900 text-white border-slate-900'
                        };
                        ?>
                        <div class="flex flex-col items-center gap-3">
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold border <?= $etatClass ?> w-full justify-center shadow-sm">
                                <?= strtoupper(htmlspecialchars($ordre['etat'])) ?>
                            </span>
                            <p class="text-xs text-gray-500 text-center">
                                Créé le <?= date('d/m/Y à H:i', strtotime($ordre['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
