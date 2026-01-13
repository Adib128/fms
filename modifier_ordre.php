<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and helpers BEFORE header.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/helpers.php';

// Security check
require_once __DIR__ . '/helpers/security.php';

$errors = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id === 0) {
    $_SESSION['message'] = "ID d'ordre invalide.";
    header('Location: ' . url('liste-ordre'));
    exit;
}

// Fetch Ordre Data
try {
    $stmt = $db->prepare("
        SELECT o.*, d.numero as demande_numero, d.description as demande_desc, d.index_km,
               a.nom as atelier_nom, b.matricule_interne as code_bus, b.matricule as immatriculation,
               s.lib as station_nom, c.nom_prenom as chauffeur_nom
        FROM ordre o
        LEFT JOIN demande d ON o.id_demande = d.id
        LEFT JOIN atelier a ON o.id_atelier = a.id
        LEFT JOIN bus b ON d.id_vehicule = b.id_bus
        LEFT JOIN station s ON d.id_station = s.id_station
        LEFT JOIN chauffeur c ON d.id_chauffeur = c.id_chauffeur
        WHERE o.id = ?
    ");
    $stmt->execute([$id]);
    $ordre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ordre) {
        $_SESSION['message'] = "Ordre introuvable.";
        header('Location: ' . url('liste-ordre'));
        exit;
    }

    // Fetch Interventions
    // Fetch Interventions with associated Technicians and Articles
    $stmtInt = $db->prepare("
        SELECT oi.*, i.libelle
        FROM ordre_intervention oi 
        JOIN intervention i ON oi.id_intervention = i.id 
        WHERE oi.id_ordre = ?
    ");
    $stmtInt->execute([$id]);
    $interventions = $stmtInt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Technicians and Articles for each intervention
    foreach ($interventions as &$int) {
        // Fetch Technicians (Planned and Realized)
        $stmtTechs = $db->prepare("
            SELECT t.id, t.nom, oit.type
            FROM ordre_intervention_technicien oit
            JOIN maintenance t ON oit.id_technicien = t.id
            WHERE oit.id_ordre_intervention = ?
        ");
        $stmtTechs->execute([$int['id']]);
        $all_techs = $stmtTechs->fetchAll(PDO::FETCH_ASSOC);
        
        $int['techniciens_prevu'] = array_filter($all_techs, fn($t) => $t['type'] === 'prévu');
        $int['techniciens_realise'] = array_filter($all_techs, fn($t) => $t['type'] === 'réalisé');
        
        $int['technicien_ids_realise'] = array_column($int['techniciens_realise'], 'id');
        $int['technicien_noms_realise'] = array_column($int['techniciens_realise'], 'nom');
        $int['technicien_noms_prevu'] = array_column($int['techniciens_prevu'], 'nom');


        // Fetch Articles
        $stmtArts = $db->prepare("
            SELECT a.id, a.designiation, a.code, oia.quantite
            FROM ordre_intervention_article oia
            JOIN article a ON oia.id_article = a.id
            WHERE oia.id_ordre_intervention = ?
        ");
        $stmtArts->execute([$int['id']]);
        $int['articles'] = $stmtArts->fetchAll(PDO::FETCH_ASSOC);
        $int['article_ids'] = array_column($int['articles'], 'id');
        $int['article_noms'] = array_column($int['articles'], 'designiation');
    }
    unset($int); // Break reference

    // Calculate pending interventions
    $pendingInterventionsCount = 0;
    foreach ($interventions as $int) {
        if (($int['status'] ?? '') !== 'réaliser') {
            $pendingInterventionsCount++;
        }
    }

    // Fetch Anomalies
    $stmtAn = $db->prepare("
        SELECT an.id, an.designation, s.designation as systeme_nom
        FROM ordre_anomalie oa 
        JOIN anomalie an ON oa.id_anomalie = an.id 
        JOIN systeme s ON an.id_system = s.id
        WHERE oa.id_ordre = ?
    ");
    $stmtAn->execute([$id]);
    $anomalies_assoc = $stmtAn->fetchAll(PDO::FETCH_ASSOC);
    $anomalies_ids = array_column($anomalies_assoc, 'id');



    // Fetch Lists for Forms

    $listTechniciens = $db->query("SELECT id, nom, matricule FROM maintenance ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    $listArticles = $db->query("SELECT id, code, designiation FROM article ORDER BY designiation")->fetchAll(PDO::FETCH_ASSOC);
    $listInterventions = $db->query("SELECT id, libelle, id_anomalie FROM intervention ORDER BY libelle")->fetchAll(PDO::FETCH_ASSOC);
    $listAteliers = $db->query("SELECT id, nom FROM atelier ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    $allAnomalies = $db->query("SELECT id, designation FROM anomalie ORDER BY designation")->fetchAll(PDO::FETCH_ASSOC);
    $listDemandes = $db->query("SELECT id, numero FROM demande ORDER BY numero DESC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['message'] = "Erreur de chargement : " . $e->getMessage();
    header('Location: ' . url('liste-ordre'));
    exit;
}

// Handle Status Change
    if (isset($_POST['action']) && in_array($_POST['action'], ['Valider', 'Cloturer'])) {
        $newEtat = $_POST['action'];
        
        // Validation check: all interventions must be 'réaliser' to validate
        if ($newEtat === 'Valider') {
            $stmtCheckInt = $db->prepare("SELECT COUNT(*) FROM ordre_intervention WHERE id_ordre = ? AND (status IS NULL OR status != 'réaliser')");
            $stmtCheckInt->execute([$id]);
            $pendingInterventions = $stmtCheckInt->fetchColumn();
            
            if ($pendingInterventions > 0) {
                $errors[] = "Impossible de valider l'ordre : il reste $pendingInterventions intervention(s) non réalisée(s).";
            }
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                $stmt = $db->prepare("UPDATE ordre SET etat = ? WHERE id = ?");
                $stmt->execute([$newEtat, $id]);
                
                // If closing the OT, the demand will be closed manually via update-demande
                
                $db->commit();
                $_SESSION['message'] = "État mis à jour : " . ucfirst($newEtat);
                header("Location: " . url('modifier-ordre') . "?id=$id");
                exit;
            } catch (PDOException $e) {
                if ($db->inTransaction()) $db->rollBack();
                $errors[] = "Erreur de mise à jour : " . $e->getMessage();
            }
        }
    }

// Handle General Info Update
if (isset($_POST['update_general_info'])) {
    if ($ordre['etat'] !== 'Ouvert') {
        $errors[] = "Modification impossible : l'ordre n'est pas ouvert.";
    } else {
        $date_new = $_POST['date'] ?? '';
        $id_demande_new = (int) ($_POST['id_demande'] ?? 0);
        
        if (empty($date_new)) $errors[] = "La date est requise.";
        if ($id_demande_new <= 0) $errors[] = "La demande est requise.";
        
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("UPDATE ordre SET date = ?, id_demande = ? WHERE id = ?");
                $stmt->execute([$date_new, $id_demande_new, $id]);
                
                $_SESSION['message'] = "Informations générales mises à jour.";
                header("Location: " . url('modifier-ordre') . "?id=$id");
                exit;
            } catch (PDOException $e) {
                $errors[] = "Erreur de mise à jour : " . $e->getMessage();
            }
        }
    }
}

// Handle Anomalie Sync
if (isset($_POST['sync_anomalies'])) {
    // Check if order is editable
    if ($ordre['etat'] !== 'Ouvert') {
        $errors[] = "Modification impossible : l'ordre n'est pas ouvert.";
    } else {
        $selected_anomalies = $_POST['anomalies'] ?? [];
        $id_atelier_new = (int) ($_POST['id_atelier'] ?? 0);
    
    try {
        $db->beginTransaction();
        
        // Update Atelier and System if changed
        if ($id_atelier_new > 0) {
            $id_system_new = (int) ($_POST['id_system'] ?? 0);
            $stmtUpdateOrdre = $db->prepare("UPDATE ordre SET id_atelier = ?, id_system = ? WHERE id = ?");
            $stmtUpdateOrdre->execute([$id_atelier_new, $id_system_new, $id]);
        }
        
        // Delete old relations
        $stmtDelete = $db->prepare("DELETE FROM ordre_anomalie WHERE id_ordre = ?");
        $stmtDelete->execute([$id]);
        
        // Insert new relations
        if (!empty($selected_anomalies)) {
            $stmtInsert = $db->prepare("INSERT INTO ordre_anomalie (id_ordre, id_anomalie) VALUES (?, ?)");
            foreach ($selected_anomalies as $an_id) {
                if (!empty($an_id)) {
                    $stmtInsert->execute([$id, $an_id]);
                }
            }
        }
        
        $db->commit();
        $_SESSION['message'] = "Informations de l'ordre mises à jour.";
        header("Location: " . url('modifier-ordre') . "?id=$id");
        exit;
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        $errors[] = "Erreur de mise à jour : " . $e->getMessage();
    }
    }
}




// Handle Add Intervention
if (isset($_POST['add_intervention'])) {
    if ($ordre['etat'] !== 'Ouvert') {
        $errors[] = "Modification impossible : l'ordre n'est pas ouvert.";
    } else {
        $id_int = (int) $_POST['id_intervention'];
    if ($id_int > 0) {
        try {
            // Check if already exists for this order
            $stmt = $db->prepare("SELECT COUNT(*) FROM ordre_intervention WHERE id_ordre = ? AND id_intervention = ?");
            $stmt->execute([$id, $id_int]);
            if ($stmt->fetchColumn() == 0) {
                $stmt = $db->prepare("INSERT INTO ordre_intervention (id_ordre, id_intervention) VALUES (?, ?)");
                $stmt->execute([$id, $id_int]);
                $_SESSION['message'] = "Intervention ajoutée.";
            } else {
                $errors[] = "Cette intervention est déjà associée à cet ordre.";
            }
            if (empty($errors)) {
                header("Location: " . url('modifier-ordre') . "?id=$id");
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur d'ajout : " . $e->getMessage();
        }
    }
    }
}

// Handle Delete Intervention
if (isset($_POST['delete_intervention_from_ordre'])) {
    if ($ordre['etat'] !== 'Ouvert') {
        $errors[] = "Modification impossible : l'ordre n'est pas ouvert.";
    } else {
        $id_int = (int) $_POST['id_intervention'];
    try {
        $stmt = $db->prepare("DELETE FROM ordre_intervention WHERE id_ordre = ? AND id_intervention = ?");
        $stmt->execute([$id, $id_int]);
        $_SESSION['message'] = "Intervention retirée.";
        header("Location: " . url('modifier-ordre') . "?id=$id");
        exit;
    } catch (PDOException $e) {
        $errors[] = "Erreur de suppression : " . $e->getMessage();
    }
    }
}

// Handle Realiser Intervention
if (isset($_POST['realiser_intervention'])) {
    if ($ordre['etat'] !== 'Ouvert') {
        $errors[] = "Modification impossible : l'ordre n'est pas ouvert.";
    } else {
        $id_oi = (int) $_POST['id_ordre_intervention'];
        $techniciens = $_POST['techniciens'] ?? []; // Array of IDs
        $articles = $_POST['articles'] ?? []; // Array of IDs
        $desc = $_POST['description'] ?? null;
        $date = $_POST['realised_at'] ?? date('Y-m-d H:i:s');
        
        try {
            $db->beginTransaction();

            // 1. Update main intervention record
            $stmt = $db->prepare("UPDATE ordre_intervention SET description = ?, realised_at = ?, status = 'réaliser' WHERE id = ? AND id_ordre = ?");
            $stmt->execute([$desc, $date, $id_oi, $id]);

            // 2. Update Technicians
            // Delete old REALIZED technicians (keep planned ones)
            $stmtDelTech = $db->prepare("DELETE FROM ordre_intervention_technicien WHERE id_ordre_intervention = ? AND type = 'réalisé'");
            $stmtDelTech->execute([$id_oi]);
            // Insert new
            if (!empty($techniciens)) {
                $stmtInsTech = $db->prepare("INSERT INTO ordre_intervention_technicien (id_ordre_intervention, id_technicien, type) VALUES (?, ?, 'réalisé')");
                foreach ($techniciens as $tech_id) {
                    $stmtInsTech->execute([$id_oi, $tech_id]);
                }
            }


            // 3. Update Articles
            // Delete old
            $stmtDelArt = $db->prepare("DELETE FROM ordre_intervention_article WHERE id_ordre_intervention = ?");
            $stmtDelArt->execute([$id_oi]);
            // Insert new
            if (!empty($articles)) {
                $stmtInsArt = $db->prepare("INSERT INTO ordre_intervention_article (id_ordre_intervention, id_article, quantite) VALUES (?, ?, 1)");
                foreach ($articles as $art_id) {
                    $stmtInsArt->execute([$id_oi, $art_id]);
                }
            }
            
            $db->commit();
            $_SESSION['message'] = "Intervention marquée comme réalisée.";
            header("Location: " . url('modifier-ordre') . "?id=$id");
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors[] = "Erreur de mise à jour : " . $e->getMessage();
        }
    }
}



// Only include header.php if not already routed and not redirecting
if (!defined('ROUTED')) {
    require 'header.php';
}

// Enforce route access
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

$isEditable = ($ordre['etat'] === 'Ouvert');
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <!-- Header Actions -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Ordre N° <?= htmlspecialchars($ordre['numero']) ?></h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= url('imprimer-ordre') ?>?id=<?= $id ?>" target="_blank" class="btn-default">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 9V2h12v7" />
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                        <path d="M6 14h12v8H6z" />
                    </svg>
                    Imprimer
                </a>
                
                <?php if ($ordre['etat'] === 'Ouvert'): ?>
                    <form method="POST" onsubmit="return confirm('Valider cet ordre ?');">
                        <input type="hidden" name="action" value="Valider">
                        <button type="submit" 
                                <?= $pendingInterventionsCount > 0 ? 'disabled' : '' ?>
                                class="btn-primary bg-green-600 hover:bg-green-700 border-green-600 <?= $pendingInterventionsCount > 0 ? 'opacity-50 cursor-not-allowed' : '' ?>"
                                title="<?= $pendingInterventionsCount > 0 ? 'Toutes les interventions doivent être réalisées pour valider' : '' ?>">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                            Valider
                        </button>
                    </form>
                <?php elseif ($ordre['etat'] === 'Valider'): ?>
                    <form method="POST" onsubmit="return confirm('Clôturer cet ordre ? Cette action est irréversible.');">
                        <input type="hidden" name="action" value="Cloturer">
                        <button type="submit" class="btn-primary bg-gray-800 hover:bg-gray-900 border-gray-800">
                            Clôturer l'ordre
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>


        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Info Panels -->
        <div class="flex flex-col gap-6">
            <!-- General Info -->
            <div class="panel w-full">
                <div class="panel-heading flex items-center justify-between">
                    <span>Informations Générales</span>
                    <?php if ($isEditable): ?>
                        <button type="button" onclick="openEditGeneralInfoModal()" class="btn-primary py-1 px-3 text-xs">
                            <span class="iconify h-4 w-4 mr-1" data-icon="mdi:pencil"></span>
                            Modifier
                        </button>
                    <?php endif; ?>
                </div>
                <div class="panel-body grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <span class="text-gray-500 block text-sm mb-1">État</span>
                        <?php
                        $etatClass = match ($ordre['etat']) {
                            'Ouvert' => 'bg-blue-100 text-blue-800',
                            'Valider' => 'bg-green-100 text-green-800',
                            'Cloturer' => 'bg-slate-900 text-white',
                            default => 'bg-slate-900 text-white'
                        };
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $etatClass ?>">
                            <?= ucfirst(htmlspecialchars($ordre['etat'])) ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-sm">Date</span>
                        <span class="font-medium"><?= date('d/m/Y', strtotime($ordre['date'])) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-sm">Demande liée</span>
                        <span class="font-medium"><?= htmlspecialchars($ordre['demande_numero']) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-sm">Véhicule</span>
                        <span class="font-medium"><?= htmlspecialchars($ordre['code_bus'] . ' - ' . $ordre['immatriculation']) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-sm">Index KM</span>
                        <span class="font-medium"><?= $ordre['index_km'] ? htmlspecialchars($ordre['index_km']) . ' km' : 'N/A' ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-sm">Station</span>
                        <span class="font-medium"><?= htmlspecialchars($ordre['station_nom'] ?? 'N/A') ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-sm mb-1">Atelier</span>
                        <span class="font-medium"><?= htmlspecialchars($ordre['atelier_nom']) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-sm mb-1">Système</span>
                        <?php 
                            // Fetch system name if not already joined
                            $systemName = 'N/A';
                            if (!empty($ordre['id_system'])) {
                                $stmtSys = $db->prepare("SELECT designation FROM systeme WHERE id = ?");
                                $stmtSys->execute([$ordre['id_system']]);
                                $systemName = $stmtSys->fetchColumn() ?: 'N/A';
                            }
                        ?>
                        <span class="font-medium"><?= htmlspecialchars($systemName) ?></span>
                    </div>
                </div>
            </div>

            <div class="panel w-full">
                <div class="panel-heading">
                    <span>Anomalies</span>
                    <?php if ($isEditable): ?>
                        <button type="button" onclick="openSyncAnomaliesModal()" class="btn-primary py-1 px-3 text-xs">
                            <span class="iconify h-4 w-4 mr-1" data-icon="mdi:pencil"></span>
                            Modifier
                        </button>
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <?php if (empty($anomalies_assoc)): ?>
                        <p class="text-gray-500 italic">Aucune anomalie spécifiée.</p>
                    <?php else: ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($anomalies_assoc as $an): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                    <span class="iconify mr-1.5 h-4 w-4" data-icon="mdi:alert-circle-outline"></span>
                                    <?= htmlspecialchars($an['designation']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Interventions -->
            <div class="panel w-full">
                <div class="panel-heading flex items-center justify-between">
                    <span class="font-semibold">Detail interventions</span>
                    <?php if ($isEditable): ?>
                        <button type="button" onclick="openAddInterventionModal()" class="btn-primary py-1 px-3 text-xs">
                            <span class="iconify h-4 w-4 mr-1" data-icon="mdi:plus"></span>
                            Ajouter
                        </button>
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <div class="mb-4">
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <span class="iconify h-5 w-5" data-icon="mdi:magnify"></span>
                            </span>
                            <input type="text" id="interventionSearch" 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 shadow-sm" 
                                   placeholder="Rechercher une intervention, technicien, article, description...">
                        </div>
                    </div>
                    <div id="interventionList" class="grid grid-cols-1 gap-4">
                        <div id="noResultsMessage" class="hidden col-span-full text-center py-8 text-gray-500 italic bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            Aucune intervention ne correspond à votre recherche.
                        </div>
                        <?php if (empty($interventions)): ?>
                            <div class="col-span-full text-center py-8 text-gray-500 italic bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                Aucune intervention spécifiée.
                            </div>
                        <?php else: ?>
                            <?php foreach ($interventions as $int): ?>
                                <div class="intervention-card bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden flex flex-col">
                                    <!-- Card Header -->
                                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="iconify text-blue-500 h-5 w-5" data-icon="mdi:tools"></span>
                                            <h3 class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($int['libelle']) ?></h3>
                                        </div>
                                        <?php
                                        $statusClass = ($int['status'] === 'réaliser') ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800';
                                        ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $statusClass ?>">
                                            <?= ucfirst($int['status'] ?? 'en cours') ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Card Body -->
                                    <div class="p-4 flex-grow space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                                            <div>
                                                <span class="text-gray-500 block mb-0.5">Techniciens Prévus</span>
                                                <div class="flex flex-wrap gap-1">
                                                    <?php if (!empty($int['techniciens_prevu'])): ?>
                                                        <?php foreach ($int['techniciens_prevu'] as $tech): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                                <?= htmlspecialchars($tech['nom']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="font-medium text-gray-400 italic">Non spécifié</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 block mb-0.5">Techniciens Réalisés</span>
                                                <div class="flex flex-wrap gap-1">
                                                    <?php if (!empty($int['techniciens_realise'])): ?>
                                                        <?php foreach ($int['techniciens_realise'] as $tech): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                                                                <?= htmlspecialchars($tech['nom']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="font-medium text-gray-400 italic">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div>
                                                <span class="text-gray-500 block mb-0.5">Articles</span>
                                                <div class="flex flex-wrap gap-1">
                                                    <?php if (!empty($int['articles'])): ?>
                                                        <?php foreach ($int['articles'] as $art): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800" title="<?= htmlspecialchars($art['code']) ?>">
                                                                <?= htmlspecialchars($art['designiation']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="font-medium text-gray-900">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 block mb-0.5">Date Réalisation</span>
                                                <span class="font-medium text-gray-900">
                                                    <?= $int['realised_at'] ? date('d/m/Y H:i', strtotime($int['realised_at'])) : '-' ?>
                                                </span>
                                            </div>
                                        </div>

                                        <?php if (!empty($int['description'])): ?>
                                            <div class="pt-3 border-t border-gray-100">
                                                <span class="text-gray-500 block text-[10px] uppercase tracking-wider mb-1">Description</span>
                                                <p class="text-xs text-gray-600 bg-gray-50 p-2 rounded border border-gray-100 italic">
                                                    <?= htmlspecialchars($int['description']) ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Card Footer Actions -->
                                    <?php if ($isEditable): ?>
                                        <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                                            <?php if ($int['status'] !== 'réaliser'): ?>
                                                <button type="button" 
                                                        onclick='openRealiserModal(<?= htmlspecialchars(json_encode($int), ENT_QUOTES, 'UTF-8') ?>)'
                                                        class="inline-flex items-center gap-1.5 text-green-600 hover:text-green-700 font-medium text-xs transition-colors">
                                                    <span class="iconify h-4 w-4" data-icon="mdi:check-circle-outline"></span>
                                                    Réaliser
                                                </button>
                                            <?php endif; ?>
                                            <form method="POST" onsubmit="return confirm('Retirer cette intervention ?');" class="inline">
                                                <input type="hidden" name="delete_intervention_from_ordre" value="1">
                                                <input type="hidden" name="id_intervention" value="<?= $int['id_intervention'] ?>">
                                                <button type="submit" class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-700 font-medium text-xs transition-colors">
                                                    <span class="iconify h-4 w-4" data-icon="mdi:delete-outline"></span>
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>








<!-- Add Intervention Modal -->

<!-- New Intervention Type Modal -->

<style>
  /* Ensure dropdowns inside these modals are not clipped and are scrollable */
  #addInterventionModal .choices,
  #newInterventionTypeModal .choices { overflow: visible; }
  #addInterventionModal .choices__inner,
  #newInterventionTypeModal .choices__inner { overflow: visible; }
  #addInterventionModal .choices__list--dropdown,
  #newInterventionTypeModal .choices__list--dropdown {
    max-height: 60vh;
    overflow-y: auto;
    z-index: 60;
  }
</style>

<!-- Realiser Intervention Modal -->

<!-- Edit General Info Modal -->


<!-- Modals moved to bottom -->
<!-- Edit General Info Modal -->
<div id="editGeneralInfoModal" class="fixed inset-0 hidden overflow-y-auto" style="z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="relative flex flex-col bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full h-[50vh]">
            <form method="POST" class="flex flex-col h-full w-full">
                <input type="hidden" name="update_general_info" value="1">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 flex-grow overflow-y-auto">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Modifier Informations Générales</h3>
                            
                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Date *</label>
                                <input type="date" name="date" required class="w-full text-sm border-gray-300 rounded" value="<?= htmlspecialchars($ordre['date']) ?>">
                            </div>

                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Demande liée *</label>
                                <select name="id_demande" id="modal_id_demande" required class="choices-select w-full" data-skip-tom-select="true">
                                    <option value="">Sélectionner une demande</option>
                                    <?php foreach ($listDemandes as $dem): ?>
                                        <option value="<?= $dem['id'] ?>" <?= $dem['id'] == $ordre['id_demande'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dem['numero']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Enregistrer
                    </button>
                    <button type="button" onclick="closeEditGeneralInfoModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sync Anomalies Modal -->
<div id="syncAnomaliesModal_Final" class="fixed inset-0 hidden overflow-y-auto" style="z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <div class="relative flex flex-col bg-white rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-lg sm:w-full border border-gray-100 h-[70vh]" style="opacity: 1; transform: none;">
            <form method="POST" class="flex flex-col h-full w-full">
                <input type="hidden" name="sync_anomalies" value="1">
                <div class="bg-white px-6 pt-6 pb-8 flex-grow overflow-y-auto">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-blue-50 text-blue-600">
                            <span class="iconify h-6 w-6" data-icon="mdi:pencil-circle"></span>
                        </div>
                        <div class="flex-grow">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Modifier Atelier & Anomalies</h3>
                            
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Atelier</label>
                                    <select name="id_atelier" id="modal_id_atelier" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" onchange="loadSystems()" data-skip-tom-select="true">
                                        <option value="">Choisir...</option>
                                        <?php foreach ($listAteliers as $at): ?>
                                            <option value="<?= $at['id'] ?>" <?= $at['id'] == $ordre['id_atelier'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($at['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Système</label>
                                    <select name="id_system" id="modal_id_system" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" onchange="loadAnomalies()" data-skip-tom-select="true">
                                        <option value="">Choisir un atelier d'abord...</option>
                                    </select>
                                </div>

                                <div id="anomalies_container">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Anomalies</label>
                                    <div id="anomalies_list" class="max-h-80 overflow-y-auto border border-gray-200 rounded-xl p-3 text-sm bg-gray-50/50">
                                        <p class="text-gray-400 italic text-center py-4">Sélectionnez un système pour voir les anomalies.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50/50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-gray-100">
                    <button type="submit" class="btn-primary px-6">
                        Enregistrer
                    </button>
                    <button type="button" onclick="closeSyncAnomaliesModal()" class="btn-default px-6">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Intervention Modal -->
<div id="addInterventionModal" class="fixed inset-0 hidden overflow-y-auto" style="z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <div class="relative flex flex-col bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-gray-100 h-[60vh]">
            <form method="POST" class="flex flex-col h-full w-full">
                <input type="hidden" name="add_intervention" value="1">
                <div class="bg-white px-6 pt-6 pb-8 flex-grow overflow-y-auto">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-blue-50 text-blue-600">
                            <span class="iconify h-6 w-6" data-icon="mdi:plus-circle"></span>
                        </div>
                        <div class="flex-grow">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Nouvelle Intervention</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Intervention *</label>
                                <div class="flex items-center gap-2">
                                    <div class="flex-grow">
                                        <select name="id_intervention" id="modal_add_id_intervention" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" data-skip-tom-select="true">
                                            <option value="">Choisir...</option>
                                            <?php foreach ($listInterventions as $int): ?>
                                                <option value="<?= $int['id'] ?>"><?= htmlspecialchars($int['libelle']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="button" onclick="openNewInterventionTypeModal()" class="bg-green-50 text-green-600 p-2.5 rounded-xl hover:bg-green-100 transition-colors" title="Nouvelle intervention">
                                        <span class="iconify h-6 w-6" data-icon="mdi:plus"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50/50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-gray-100">
                    <button type="submit" class="btn-primary px-6">
                        Ajouter
                    </button>
                    <button type="button" onclick="closeAddInterventionModal()" class="btn-default px-6">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Intervention Type Modal -->
<div id="newInterventionTypeModal" class="fixed inset-0 hidden overflow-y-auto" style="z-index: 10000;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <div class="relative flex flex-col bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-gray-100 h-[60vh]">
            <div class="flex-grow bg-white px-6 pt-6 pb-8 overflow-y-auto">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-blue-50 text-blue-600">
                        <span class="iconify h-6 w-6" data-icon="mdi:plus-circle"></span>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Nouvelle Intervention</h3>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Libellé de l'intervention *</label>
                                <input type="text" id="new_intervention_type_libelle" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" placeholder="Ex: Vidange moteur">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Anomalie associée *</label>
                                <select id="new_intervention_type_id_anomalie" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" data-skip-tom-select="true" required>
                                    <option value="">Choisir une anomalie...</option>
                                    <?php foreach ($allAnomalies as $an): ?>
                                        <option value="<?= $an['id'] ?>"><?= htmlspecialchars($an['designation']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <p id="new-int-modal-error" class="mt-4 text-sm text-red-600 font-medium bg-red-50 p-3 rounded-lg hidden"></p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50/50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-gray-100">
                <button type="button" id="saveNewInterventionTypeBtn" class="btn-primary px-6">
                    Enregistrer
                </button>
                <button type="button" onclick="closeNewInterventionTypeModal()" class="btn-default px-6">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Realiser Intervention Modal -->
<div id="realiserInterventionModal" class="fixed inset-0 hidden overflow-y-auto" style="z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <div class="relative flex flex-col bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-4xl sm:w-full border border-gray-100 h-[70vh]">
            <form method="POST" class="flex flex-col h-full w-full">
                <input type="hidden" name="realiser_intervention" value="1">
                <input type="hidden" name="id_ordre_intervention" id="realiser_id_ordre_intervention" value="">
                
                <div class="bg-white px-6 pt-6 pb-8 flex-grow overflow-y-auto">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-green-50 text-green-600">
                            <span class="iconify h-6 w-6" data-icon="mdi:check-circle"></span>
                        </div>
                        <div class="flex-grow">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Réaliser l'intervention</h3>
                            
                            <div class="space-y-5">
                                <div id="planned_techs_section" class="bg-blue-50 p-3 rounded-xl border border-blue-100 hidden">
                                    <span class="text-[10px] font-bold text-blue-400 uppercase block mb-1">Techniciens Prévus</span>
                                    <div id="planned_techs_list" class="flex flex-wrap gap-1"></div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Techniciens Réalisés</label>

                                    <select name="techniciens[]" id="realiser_id_technicien" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" required multiple data-skip-tom-select="true">
                                        <?php foreach ($listTechniciens as $tech): ?>
                                            <option value="<?= $tech['id'] ?>"><?= htmlspecialchars(($tech['matricule'] ? $tech['matricule'] . ' - ' : '') . $tech['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Articles utilisés</label>
                                    <select name="articles[]" id="realiser_id_article" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" multiple data-skip-tom-select="true">
                                        <option value="">Choisir...</option>
                                        <?php foreach ($listArticles as $art): ?>
                                            <option value="<?= $art['id'] ?>"><?= htmlspecialchars($art['code'] . ' - ' . $art['designiation']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Date & Heure</label>
                                    <input type="datetime-local" name="realised_at" id="realiser_realised_at" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Description (Optionnel)</label>
                                    <textarea name="description" id="realiser_description" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" placeholder="Commentaires..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50/50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-gray-100">
                    <button type="submit" class="btn-primary px-6 bg-green-600 hover:bg-green-700 border-green-600">
                        Enregistrer
                    </button>
                    <button type="button" onclick="closeRealiserModal()" class="btn-default px-6">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Global error handler for debugging
window.onerror = function(msg, url, lineNo, columnNo, error) {
    // showToast("JS Error: " + msg, "error");
    console.error("JS Error:", msg, url, lineNo, error);
    return false;
};
// Pre-selected anomalies
const selectedAnomalies = <?= json_encode($anomalies_ids ?? []) ?>;
const currentSystemId = <?= json_encode($ordre['id_system'] ?? null) ?>;
const allInterventions = <?= json_encode($listInterventions) ?>;

function openEditGeneralInfoModal() {
    const modal = document.getElementById('editGeneralInfoModal');
    modal.classList.remove('hidden');
    modal.style.display = 'block';
    modal.style.zIndex = '9999';
}

function closeEditGeneralInfoModal() {
    const modal = document.getElementById('editGeneralInfoModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
}

function openSyncAnomaliesModal() {
    try {
        const modal = document.getElementById('syncAnomaliesModal_Final');
        if (!modal) {
            console.error("Erreur: Modal anomalies introuvable");
            return;
        }
        modal.classList.remove('hidden');
        modal.style.display = 'block'; // Force display
        modal.style.zIndex = '9999'; // Force z-index
        
        // Force visibility on inner content in case of transform issues
        const inner = modal.querySelector('.relative');
        if (inner) {
            inner.style.opacity = '1';
            inner.style.transform = 'none';
        }
        
        // Initialize
        if (typeof loadSystems === 'function') {
            loadSystems(currentSystemId);
        } else {
            console.error("loadSystems is not defined");
        }
    } catch (e) {
        console.error("Error in openSyncAnomaliesModal:", e);
    }
}

function closeSyncAnomaliesModal() {
    const modal = document.getElementById('syncAnomaliesModal_Final');
    modal.classList.add('hidden');
    modal.style.display = 'none';
}

function loadSystems(preselectedSystemId = null) {
    const atelierId = document.getElementById('modal_id_atelier').value;
    const systemSelect = document.getElementById('modal_id_system');
    const anomaliesContainer = document.getElementById('anomalies_list');
    
    // Reset downstream
    if (systemChoices) {
        systemChoices.clearChoices();
        systemChoices.setChoices([{ value: '', label: 'Chargement...', selected: true, disabled: true }], 'value', 'label', true);
    }
    anomaliesContainer.innerHTML = '<p class="text-gray-400 italic text-center py-2">Sélectionnez un système...</p>';

    if (!atelierId) {
        if (systemChoices) {
            systemChoices.clearChoices();
            systemChoices.setChoices([{ value: '', label: 'Choisir un atelier d\'abord...', selected: true, disabled: true }], 'value', 'label', true);
        }
        return;
    }

    fetch(`<?= url('api/get_systems_by_atelier.php') ?>?id_atelier=${atelierId}`)
        .then(response => response.json())
        .then(data => {
            if (systemChoices) {
                systemChoices.clearChoices();
                const choices = [
                    { value: '', label: 'Choisir un système...', selected: !preselectedSystemId, disabled: true },
                    ...data.map(sys => ({
                        value: sys.id.toString(),
                        label: sys.designation,
                        selected: preselectedSystemId && sys.id == preselectedSystemId
                    }))
                ];
                systemChoices.setChoices(choices, 'value', 'label', true);
            }

            // If we have a preselected system (or user just selected one), load anomalies
            if (preselectedSystemId) {
                loadAnomalies();
            }
        })
        .catch(err => {
            console.error(err);
            if (systemChoices) {
                systemChoices.clearChoices();
                systemChoices.setChoices([{ value: '', label: 'Erreur de chargement', selected: true, disabled: true }], 'value', 'label', true);
            }
        });
}

function loadAnomalies() {
    const systemId = document.getElementById('modal_id_system').value;
    const container = document.getElementById('anomalies_list');
    
    if (!systemId) {
        container.innerHTML = '<p class="text-gray-400 italic text-center py-2">Sélectionnez un système...</p>';
        return;
    }

    container.innerHTML = '<p class="text-gray-400 italic text-center py-2">Chargement...</p>';

    fetch(`<?= url('api/get_anomalies_by_system.php') ?>?id_system=${systemId}`)
        .then(response => response.json())
        .then(anomalies => {
            if (anomalies.length === 0) {
                container.innerHTML = '<p class="text-gray-400 italic text-center py-2">Aucune anomalie trouvée pour ce système.</p>';
                return;
            }

            let html = '<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">';
            anomalies.forEach(an => {
                const isChecked = selectedAnomalies.includes(an.id) ? 'checked' : '';
                html += `<label class="inline-flex items-center">
                    <input type="checkbox" name="anomalies[]" value="${an.id}" ${isChecked} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-gray-600">${an.designation}</span>
                </label>`;
            });
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<p class="text-red-500 italic text-center py-2">Erreur lors du chargement des données.</p>';
        });
}



// Intervention Modal Functions
function openAddInterventionModal() {
    const modal = document.getElementById('addInterventionModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.style.display = 'block'; // Force display
    modal.style.zIndex = '9999'; // Force z-index
    
    // Filter options based on selectedAnomalies (which reflects the saved state)
    if (typeof allInterventions !== 'undefined' && Array.isArray(allInterventions)) {
        const filtered = allInterventions.filter(item => {
            if (!item.id_anomalie) return true;
            const anomalyId = parseInt(item.id_anomalie);
            return (typeof selectedAnomalies !== 'undefined' && Array.isArray(selectedAnomalies)) 
                ? selectedAnomalies.map(Number).includes(anomalyId) 
                : true;
        });
        
        if (typeof addIntChoices !== 'undefined' && addIntChoices) {
            try {
                addIntChoices.clearChoices();
                addIntChoices.setChoices([
                    { value: '', label: 'Choisir...', selected: true, disabled: true },
                    ...filtered.map(item => ({
                        value: item.id.toString(),
                        label: item.libelle,
                        selected: false,
                        disabled: false
                    }))
                ], 'value', 'label', true);
            } catch (e) {
                console.error("Choices error:", e);
            }
        }
    }
}

function closeAddInterventionModal() {
    const modal = document.getElementById('addInterventionModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
}

function openNewInterventionTypeModal() {
    const modal = document.getElementById('newInterventionTypeModal');
    modal.classList.remove('hidden');
    modal.style.display = 'block'; // Force display
    modal.style.zIndex = '10000'; // Force higher z-index than addInterventionModal
    
    document.getElementById('new_intervention_type_libelle').value = '';
    if (newIntAnomalieChoices) {
        newIntAnomalieChoices.setChoiceByValue('');
    }
    document.getElementById('new-int-modal-error').classList.add('hidden');
    document.getElementById('new_intervention_type_libelle').focus();
}

function closeNewInterventionTypeModal() {
    const modal = document.getElementById('newInterventionTypeModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
}

document.getElementById('saveNewInterventionTypeBtn').addEventListener('click', function() {
    const libelle = document.getElementById('new_intervention_type_libelle').value.trim();
    const id_anomalie = document.getElementById('new_intervention_type_id_anomalie').value;
    const errorEl = document.getElementById('new-int-modal-error');
    
    if (!libelle) {
        errorEl.textContent = "Le libellé est requis.";
        errorEl.classList.remove('hidden');
        return;
    }

    if (!id_anomalie) {
        errorEl.textContent = "L'anomalie est requise.";
        errorEl.classList.remove('hidden');
        return;
    }

    fetch('<?= url('api/ajouter_intervention_ajax.php') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            libelle: libelle,
            id_anomalie: id_anomalie || null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            errorEl.textContent = data.error;
            errorEl.classList.remove('hidden');
        } else {
            // Add to allInterventions global array
            allInterventions.push({
                id: data.id,
                libelle: data.libelle,
                id_anomalie: data.id_anomalie
            });
            
            // Refresh the main intervention select and select the new item
            openAddInterventionModal();
            
            if (typeof addIntChoices !== 'undefined' && addIntChoices) {
                try {
                    // Add the new choice and select it
                    addIntChoices.setChoices([
                        { value: data.id.toString(), label: data.libelle, selected: true, disabled: false }
                    ], 'value', 'label', false); // false = append, not replace
                    
                    addIntChoices.setChoiceByValue(data.id.toString());
                } catch (e) {
                    console.error("Error updating choices:", e);
                }
            }

            closeNewInterventionTypeModal();
            showToast("Nouveau type d'intervention ajouté.");
        }
    })
    .catch(err => {
        console.error(err);
        errorEl.textContent = "Erreur lors de l'enregistrement.";
        errorEl.classList.remove('hidden');
    });
});

// Initialize Choices.js for Realiser Modal
let techChoices, artChoices, addIntChoices, newIntAnomalieChoices, atelierChoices, systemChoices, demandChoices;

document.addEventListener('DOMContentLoaded', function() {
    try {
        const techSelect = document.getElementById('realiser_id_technicien');
        const artSelect = document.getElementById('realiser_id_article');
        const addIntSelect = document.getElementById('modal_add_id_intervention');

        if (techSelect) {
            techChoices = new Choices(techSelect, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Choisir des techniciens...',
                removeItemButton: true
            });
        }

        if (artSelect) {
            artChoices = new Choices(artSelect, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Choisir des articles...',
                removeItemButton: true
            });
        }

        if (addIntSelect) {
            addIntChoices = new Choices(addIntSelect, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Choisir...',
                position: 'auto'
            });
        }

        const newIntAnomalieSelect = document.getElementById('new_intervention_type_id_anomalie');
        if (newIntAnomalieSelect) {
            newIntAnomalieChoices = new Choices(newIntAnomalieSelect, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Choisir...',
                position: 'auto'
            });
        }
        const atelierSelect = document.getElementById('modal_id_atelier');
        if (atelierSelect) {
            atelierChoices = new Choices(atelierSelect, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Choisir...',
                position: 'auto'
            });
        }

        const systemSelect = document.getElementById('modal_id_system');
        if (systemSelect) {
            systemChoices = new Choices(systemSelect, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Choisir...',
                position: 'auto'
            });
        }

        const demandSelect = document.getElementById('modal_id_demande');
        if (demandSelect) {
            demandChoices = new Choices(demandSelect, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Sélectionner une demande',
                position: 'auto'
            });
        }

        // Search filtering for interventions
        const searchInput = document.getElementById('interventionSearch');
        const noResults = document.getElementById('noResultsMessage');
        const cards = document.querySelectorAll('.intervention-card');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                let hasVisible = false;

                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if (text.includes(query)) {
                        card.classList.remove('hidden');
                        hasVisible = true;
                    } else {
                        card.classList.add('hidden');
                    }
                });

                if (noResults) {
                    if (!hasVisible && query !== '') {
                        noResults.classList.remove('hidden');
                    } else {
                        noResults.classList.add('hidden');
                    }
                }
            });
        }

        // Move modals to body to avoid z-index/transform issues
        const modals = [
            'addInterventionModal', 
            'newInterventionTypeModal', 
            'realiserInterventionModal', 
            'editGeneralInfoModal', 
            'syncAnomaliesModal_Final'
        ];
        modals.forEach(id => {
            const el = document.getElementById(id);
            if (el) document.body.appendChild(el);
        });
    } catch (e) {
        console.error("Error in DOMContentLoaded:", e);
        // alert("Erreur JS (Init): " + e.message);
    }
});

function openRealiserModal(int) {
    const modal = document.getElementById('realiserInterventionModal');
    modal.classList.remove('hidden');
    modal.style.display = 'block';
    modal.style.zIndex = '9999';
    document.getElementById('realiser_id_ordre_intervention').value = int.id;
    
    // Set values using Choices.js
    if (techChoices) {
        techChoices.removeActiveItems();
        if (int.technicien_ids_realise && int.technicien_ids_realise.length > 0) {
            techChoices.setChoiceByValue(int.technicien_ids_realise.map(String));
        }
    }

    // Show planned technicians
    const plannedSection = document.getElementById('planned_techs_section');
    const plannedList = document.getElementById('planned_techs_list');
    if (int.technicien_noms_prevu && int.technicien_noms_prevu.length > 0) {
        plannedSection.classList.remove('hidden');
        plannedList.innerHTML = int.technicien_noms_prevu.map(nom => 
            `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200">${nom}</span>`
        ).join('');
    } else {
        plannedSection.classList.add('hidden');
    }

    
    if (artChoices) {
        artChoices.removeActiveItems();
        if (int.article_ids && int.article_ids.length > 0) {
            artChoices.setChoiceByValue(int.article_ids.map(String));
        }
    }
    
    document.getElementById('realiser_description').value = int.description || '';
    
    // Set date (default to now if empty, or existing date)
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const defaultDate = now.toISOString().slice(0, 16);
    
    document.getElementById('realiser_realised_at').value = int.realised_at ? int.realised_at.replace(' ', 'T') : defaultDate;
}

function closeRealiserModal() {
    const modal = document.getElementById('realiserInterventionModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
}
</script>
