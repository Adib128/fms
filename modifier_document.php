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

// Get fiche ID from URL
$id_fiche = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_fiche > 0) {
    $numero = trim($_POST['numero'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $station = isset($_POST['station']) ? (int)$_POST['station'] : 0;
    
    // Validation
    $errors = [];
    if ($numero === '') {
        $errors[] = "Le numéro de fiche est requis.";
    }
    if ($date === '') {
        $errors[] = "La date est requise.";
    }
    if ($station === 0) {
        $errors[] = "L'atelier est requis.";
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Get current fiche data to check if date changed
            $currentFicheStmt = $db->prepare('SELECT date FROM fiche_entretien WHERE id_fiche = ?');
            $currentFicheStmt->execute([$id_fiche]);
            $currentFiche = $currentFicheStmt->fetch(PDO::FETCH_ASSOC);
            
            $old_date = $currentFiche['date'] ?? '';
            
            // Normalize dates for comparison
            $old_date_normalized = $old_date ? date('Y-m-d', strtotime($old_date)) : '';
            $new_date_normalized = $date ? date('Y-m-d', strtotime($date)) : '';
            
            // Check if date has changed
            $date_changed = ($new_date_normalized !== $old_date_normalized);
            
            // Update fiche_entretien
            $stmt = $db->prepare('UPDATE fiche_entretien SET numd_doc = :numero, date = :date, id_station = :station WHERE id_fiche = :id_fiche');
            $stmt->execute([
                ':numero' => $numero,
                ':date' => $date,
                ':station' => $station,
                ':id_fiche' => $id_fiche,
            ]);
            
            // If date changed, update all maintenance_records for this fiche
            if ($date_changed) {
                $updateRecordsStmt = $db->prepare('UPDATE maintenance_records SET date = :date WHERE fiche_id = :fiche_id');
                $updateRecordsStmt->execute([
                    ':date' => $date,
                    ':fiche_id' => $id_fiche
                ]);
            }
            
            $db->commit();
            
            $message = "Fiche d'entretien modifiée avec succès.";
            if ($date_changed) {
                $message .= " La date de toutes les opérations associées a été mise à jour.";
            }
            $_SESSION['success_message'] = $message;
            header('Location: /liste-fiche-entretien');
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Erreur lors de la modification : " . $e->getMessage();
        }
    }
}

// Only include header.php if not already routed
if (!defined('ROUTED')) {
    require 'header.php';
}

// Enforce route access
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

$errors = $errors ?? [];

if ($id_fiche === 0) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>ID de fiche d'entretien invalide</div>";
    echo "<script>setTimeout(() => window.location.replace('/liste-fiche-entretien'), 2000)</script>";
    exit;
}

// Load stations list
$stations = [];
try {
    $stationStmt = $db->query('SELECT id_station, lib FROM station ORDER BY id_station ASC');
    $stations = $stationStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Impossible de charger la liste des ateliers : " . $e->getMessage();
}

// Get fiche details
$fiche_query = $db->prepare("SELECT id_fiche, numd_doc, date, id_station FROM fiche_entretien WHERE id_fiche = ?");
$fiche_query->execute([$id_fiche]);
$fiche = $fiche_query->fetch(PDO::FETCH_ASSOC);

if (!$fiche) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>Fiche d'entretien non trouvée</div>";
    echo "<script>setTimeout(() => window.location.replace('/liste-fiche-entretien'), 2000)</script>";
    exit;
}

// Format date for input field - use POST value if available (for error display), otherwise use DB value
$date_value = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date'])) {
    $date_value = htmlspecialchars($_POST['date'], ENT_QUOTES, 'UTF-8');
} else {
    $date_value = $fiche['date'] ? date('Y-m-d', strtotime($fiche['date'])) : '';
}

// Get numero value - use POST value if available (for error display), otherwise use DB value
$numero_value = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero'])) {
    $numero_value = htmlspecialchars($_POST['numero'], ENT_QUOTES, 'UTF-8');
} else {
    $numero_value = htmlspecialchars($fiche['numd_doc'] ?? '', ENT_QUOTES, 'UTF-8');
}

// Get station value - use POST value if available (for error display), otherwise use DB value
$station_value = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['station'])) {
    $station_value = (int)$_POST['station'];
} else {
    $station_value = (int)($fiche['id_station'] ?? 0);
}
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Modifier fiche d'entretien</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= url('liste-fiche-entretien') ?>" class="btn-default">
                    Annuler
                </a>
                <button type="submit" form="modifier-document-form" class="btn-primary">
                    Enregistrer les modifications
                </button>
            </div>
        </div>

        <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger">
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-heading">
                <span>Modifier les informations du document</span>
            </div>
            <div class="panel-body">
                <form id="modifier-document-form" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <label class="form-control form-control--enhanced">
                            <span>Numéro de fiche</span>
                            <input
                                type="text"
                                id="numero"
                                name="numero"
                                class="input input--standard"
                                value="<?= $numero_value; ?>"
                                placeholder="Numéro de fiche"
                                required
                            >
                        </label>
                        <label class="form-control form-control--enhanced">
                            <span>Date</span>
                            <input
                                type="date"
                                id="date"
                                name="date"
                                class="input input--standard"
                                value="<?= $date_value; ?>"
                                required
                            >
                        </label>
                        <label class="form-control form-control--enhanced">
                            <span>Atelier</span>
                            <select
                                name="station"
                                id="station"
                                class="input input--standard"
                                required
                                data-skip-tom-select="true"
                                placeholder="Atelier"
                            >
                                <option value="">Atelier</option>
                                <?php foreach ($stations as $station) : ?>
                                    <option value="<?= (int) $station['id_station']; ?>" <?= ($station_value == $station['id_station']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($station['lib'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>


                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control--enhanced {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .form-control--enhanced span {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
    }
    .input--standard {
        width: 100%;
        padding: 0.625rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        min-height: 2.5rem;
        color: #111827;
        background-color: #ffffff;
        transition: all 0.15s ease-in-out;
    }
    .input--standard:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    select.input--standard {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        padding-right: 2.5rem;
    }
</style>
</body>
</html>

