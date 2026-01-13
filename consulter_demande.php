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
    $_SESSION['message'] = "ID de demande invalide.";
    header('Location: ' . url('liste-demande'));
    exit;
}

// Fetch existing demande
$demande = null;
try {
    $stmt = $db->prepare('
        SELECT d.*, 
               b.matricule_interne as code_vehicule, b.matricule as immatriculation, b.marque,
               c.nom_prenom as chauffeur_nom,
               s.lib as station_nom
        FROM demande d
        JOIN bus b ON d.id_vehicule = b.id_bus
        LEFT JOIN chauffeur c ON d.id_chauffeur = c.id_chauffeur
        LEFT JOIN station s ON d.id_station = s.id_station
        WHERE d.id = ?
    ');
    $stmt->execute([$id]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$demande) {
        $_SESSION['message'] = "Demande introuvable.";
        header('Location: ' . url('liste-demande'));
        exit;
    }

    // Fetch related orders
    $stmtOrders = $db->prepare('
        SELECT id, numero, date, etat, created_at
        FROM ordre
        WHERE id_demande = ?
        ORDER BY created_at DESC
    ');
    $stmtOrders->execute([$id]);
    $relatedOrders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['message'] = "Erreur lors de la récupération de la demande.";
    header('Location: ' . url('liste-demande'));
    exit;
}


require 'header.php';
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Détails de la demande</h1>
                <p class="text-sm text-slate-500">Numéro : <?= htmlspecialchars($demande['numero']) ?></p>
            </div>
            <div class="flex gap-2">
                <?php if ($demande['etat'] !== 'Cloturer'): ?>
                <a href="<?= url('modifier-demande') ?>?id=<?= $demande['id'] ?>" class="btn-primary">
                    <span class="iconify h-5 w-5 mr-2" data-icon="mdi:pencil"></span>
                    Modifier
                </a>
                <?php endif; ?>
                <a href="<?= url('liste-demande') ?>" class="btn-default">
                    <span class="iconify h-5 w-5 mr-2" data-icon="mdi:arrow-left"></span>
                    Retour à la liste
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <div class="panel">
                    <div class="panel-heading">
                        <span>Informations Générales</span>
                    </div>
                    <div class="panel-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Date</label>
                                <p class="text-sm font-semibold text-gray-900"><?= date('d/m/Y', strtotime($demande['date'])) ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Numéro BDC</label>
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($demande['num_bdc'] ?? '-') ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Agence</label>
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($demande['station_nom'] ?? '-') ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Chauffeur</label>
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($demande['chauffeur_nom'] ?? '-') ?></p>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <span>Description détaillée</span>
                    </div>
                    <div class="panel-body">
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 min-h-[100px] text-sm text-gray-700 shadow-inner">
                            <?= htmlspecialchars($demande['description']) ?>
                        </div>
                    </div>
                </div>

                <!-- Related Work Orders -->
                <div class="panel">
                    <div class="panel-heading">
                        <span>Ordres de travail associés</span>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($relatedOrders)): ?>
                            <div class="text-center py-8">
                                <span class="iconify h-12 w-12 text-slate-300 mx-auto mb-3" data-icon="mdi:clipboard-text-off-outline"></span>
                                <p class="text-slate-500 text-sm">Aucun ordre de travail associé à cette demande.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-100">
                                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Numéro</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">État</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php foreach ($relatedOrders as $ordre): ?>
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="py-3 px-4 text-sm font-bold text-slate-900">
                                                    <?= htmlspecialchars($ordre['numero']) ?>
                                                </td>
                                                <td class="py-3 px-4 text-sm text-slate-600">
                                                    <?= date('d/m/Y', strtotime($ordre['date'])) ?>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <?php
                                                    $ordreEtatClass = match (strtolower($ordre['etat'])) {
                                                        'ouvert', 'en cours' => 'bg-blue-100 text-blue-800',
                                                        'valider' => 'bg-green-100 text-green-800',
                                                        'cloturer' => 'bg-slate-900 text-white',
                                                        default => 'bg-slate-100 text-slate-800'
                                                    };
                                                    ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $ordreEtatClass ?>">
                                                        <?= strtoupper(htmlspecialchars($ordre['etat'])) ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-right">
                                                    <a href="<?= url('consulter-ordre') ?>?id=<?= $ordre['id'] ?>" target="_blank" class="inline-flex items-center text-brand-600 hover:text-brand-700 font-semibold text-sm">
                                                        <span class="iconify h-4 w-4 mr-1" data-icon="mdi:eye"></span>
                                                        Consulter
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Status & Priority -->
            <div class="space-y-6">
                <div class="panel">
                    <div class="panel-heading">
                        <span>Véhicule</span>
                    </div>
                    <div class="panel-body space-y-4">
                        <div class="flex items-center gap-4 p-3 bg-blue-50 rounded-xl border border-blue-100">
                            <div class="h-12 w-12 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-sm">
                                <span class="iconify h-8 w-8" data-icon="mdi:bus"></span>
                            </div>
                            <div>
                                <p class="text-xs text-blue-600 font-medium uppercase tracking-wider">Matricule Interne</p>
                                <p class="text-lg font-bold text-blue-900"><?= htmlspecialchars($demande['code_vehicule']) ?></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Immatriculation</label>
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($demande['immatriculation']) ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Marque</label>
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($demande['marque']) ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Kilométrage</label>
                                <p class="text-sm font-semibold text-gray-900"><?= number_format($demande['index_km'], 0, ',', ' ') ?> KM</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <span>Statut & Priorité</span>
                    </div>
                    <div class="panel-body space-y-6">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">État de la demande</label>
                            <?php
                            $etatClass = match ($demande['etat']) {
                                'En cours' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'Valider' => 'bg-green-100 text-green-800 border-green-200',
                                'Cloturer' => 'bg-slate-900 text-white border-slate-900',
                                default => 'bg-slate-900 text-white border-slate-900'
                            };
                            ?>
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold border <?= $etatClass ?> w-full justify-center shadow-sm">
                                <?= strtoupper(htmlspecialchars($demande['etat'])) ?>
                            </span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Priorité</label>
                            <?php
                            $prioriteClass = match ($demande['priorite']) {
                                'Basse' => 'bg-slate-100 text-slate-800 border-slate-200',
                                'Moyenne' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'Haute' => 'bg-orange-100 text-orange-800 border-orange-200',
                                'Critique' => 'bg-red-100 text-red-800 border-red-200',
                                default => 'bg-gray-100 text-gray-800 border-gray-200'
                            };
                            ?>
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold border <?= $prioriteClass ?> w-full justify-center shadow-sm">
                                <?= strtoupper(htmlspecialchars($demande['priorite'] ?? 'Non définie')) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
