<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeDocument = 'Carte';
    $numDoc = filter_input(INPUT_POST, 'num_doc', FILTER_SANITIZE_NUMBER_INT);
    $dateDoc = filter_input(INPUT_POST, 'date_doc', FILTER_DEFAULT);
    $stationId = filter_input(INPUT_POST, 'station', FILTER_VALIDATE_INT);

    if ($numDoc && $dateDoc && $stationId) {
        $insertDoc = $db->prepare('INSERT INTO doc_carburant (type, num_doc_carburant, date, index_debut, index_fin, id_station)
            VALUES (:type, :num_doc, :date, :index_debut, :index_fin, :station)');
        $insertDoc->execute([
            ':type' => $typeDocument,
            ':num_doc' => $numDoc,
            ':date' => $dateDoc,
            ':index_debut' => 0,
            ':index_fin' => 0,
            ':station' => $stationId
        ]);

        $docId = (int) $db->lastInsertId();
        $dateSaisie = date('Y-m-d H:i:s');

        $insertLigne = $db->prepare('INSERT INTO carburant (type, ref, date_saisie, qte_go, index_km, date, heure, id_bus, id_chauffeur, id_doc_carburant)
            VALUES (:type, :ref, :date_saisie, :qte_go, :index_km, :date, :heure, :bus, :chauffeur, :doc)');

        for ($i = 1; $i <= 30; $i++) {
            $busId = filter_input(INPUT_POST, 'bus-' . $i, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            if (!$busId) {
                continue;
            }

            $typeCarburant = filter_input(INPUT_POST, 'type_carburant-' . $i, FILTER_DEFAULT);
            $chauffeurId = filter_input(INPUT_POST, 'chauffeur-' . $i, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?: null;
            $qteGo = filter_input(INPUT_POST, 'qte_go-' . $i, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
            $indexKm = filter_input(INPUT_POST, 'index_km-' . $i, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);

            $qteGoValue = $qteGo !== null ? (float) str_replace(',', '.', $qteGo) : 0;
            $indexKmValue = $indexKm !== null ? (float) str_replace(',', '.', $indexKm) : 0;

            $insertLigne->execute([
                ':type' => $typeCarburant ?: 'GO',
                ':ref' => 0,
                ':date_saisie' => $dateSaisie,
                ':qte_go' => $qteGoValue,
                ':index_km' => $indexKmValue,
                ':date' => $dateDoc,
                ':heure' => '00:00',
                ':bus' => $busId,
                ':chauffeur' => $chauffeurId,
                ':doc' => $docId
            ]);
        }

        header('Location: ' . url('liste-doc-carburant') . '?success=' . urlencode('Enregistrement avec succès'));
        exit;
    }
}

// Only include header.php if not already routed and not processing POST
if (!defined('ROUTED')) {
    require 'header.php';
}

$stationsStmt = $db->query("SELECT id_station, lib FROM station ORDER BY id_station ASC");
$stations = $stationsStmt ? $stationsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$busesStmt = $db->query("SELECT id_bus, matricule_interne, carburant_type FROM bus ORDER BY matricule_interne ASC");
$buses = $busesStmt ? $busesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$chauffeursStmt = $db->query("SELECT id_chauffeur, matricule, nom_prenom FROM chauffeur ORDER BY nom_prenom ASC");
$chauffeurs = $chauffeursStmt ? $chauffeursStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Enregistrer Document Carte</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= url('liste-doc-carburant') ?>" class="btn-default">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                    Retour à la liste
                </a>
                <button type="submit" form="sub-form" class="btn-primary inline-flex items-center gap-2" id="btn-sub">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    Enregistrer le document
                </button>
            </div>
        </div>

        <?php if (hasFlashMessage()) : ?>
            <div class="alert-success flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9z" />
                </svg>
                <div class="flex-1 text-sm">
                    <?= getFlashMessage() ?>
                </div>
            </div>
        <?php endif; ?>

        <form id="sub-form" method="post" class="flex flex-col gap-6">
            <div class="panel">
                <div class="panel-heading">
                    <span>Informations document</span>
                    <span class="badge">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                    </span>
                </div>
                <div class="panel-body">
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
                        .input-with-icon {
                            position: relative;
                            display: flex;
                            align-items: center;
                        }
                        .input-icon {
                            position: absolute;
                            left: 0.75rem;
                            width: 1.25rem;
                            height: 1.25rem;
                            color: #6b7280;
                            pointer-events: none;
                            z-index: 1;
                        }
                        .input--with-icon {
                            width: 100%;
                            padding: 0.625rem 0.75rem 0.625rem 2.75rem;
                            border: 1px solid #d1d5db;
                            border-radius: 0.5rem;
                            font-size: 0.875rem;
                            line-height: 1.25rem;
                            color: #111827;
                            background-color: #ffffff;
                            transition: all 0.15s ease-in-out;
                        }
                        .input--with-icon:focus {
                            outline: none;
                            border-color: #3b82f6;
                            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                        }
                        .input--with-icon::placeholder {
                            color: #9ca3af;
                        }
                        select:not(.tom-select) {
                            width: 100%;
                            padding: 0.625rem 0.75rem;
                            border: 1px solid #d1d5db;
                            border-radius: 0.5rem;
                            font-size: 0.875rem;
                            line-height: 1.25rem;
                            color: #111827;
                            background-color: #ffffff;
                            transition: all 0.15s ease-in-out;
                            cursor: pointer;
                        }
                        select:not(.tom-select):focus {
                            outline: none;
                            border-color: #3b82f6;
                            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                        }
                        /* Choices.js Custom Styles */
                        .choices {
                            margin-bottom: 0;
                            width: 100%;
                        }
                        .choices__inner {
                            padding: 0.375rem 0.75rem !important;
                            border: 1px solid #d1d5db !important;
                            border-radius: 0.5rem !important;
                            font-size: 0.875rem !important;
                            line-height: 1.25rem !important;
                            color: #111827 !important;
                            background-color: #ffffff !important;
                            min-height: 42px !important;
                        }
                        .choices__list--single {
                            padding: 0 !important;
                        }
                        .choices__input {
                            background-color: transparent !important;
                            font-size: 0.875rem !important;
                        }
                        .choices__list--dropdown {
                            z-index: 9999 !important;
                            border-radius: 0.5rem !important;
                            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
                        }
                        .choices__item--selectable {
                            font-size: 0.875rem !important;
                        }
                        .choices__item--highlighted {
                            background-color: #3b82f6 !important;
                        }
                        .panel-body {
                            overflow: visible !important;
                            position: relative !important;
                        }
                        .panel {
                            overflow: visible !important;
                            position: relative !important;
                        }
                        .table {
                            overflow: visible !important;
                            position: relative !important;
                        }
                        .hidden-column {
                            display: none !important;
                        }
                    </style>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="form-control form-control--enhanced">
                            <span>Numéro du document</span>
                            <div class="input-with-icon">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 7h14" />
                                    <path d="M5 15h14" />
                                    <path d="M10 3l-2 18" />
                                    <path d="M16 3l-2 18" />
                                </svg>
                                <input type="number" name="num_doc" id="num_doc" required class="input input--with-icon" placeholder="N° doc">
                            </div>
                        </label>
                        <label class="form-control form-control--enhanced">
                            <span>Date</span>
                            <div class="input-with-icon">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" />
                                    <path d="M16 2v4" />
                                    <path d="M8 2v4" />
                                    <path d="M3 10h18" />
                                </svg>
                                <input type="date" name="date_doc" required class="input input--with-icon">
                            </div>
                        </label>
                        <label class="form-control form-control--enhanced">
                            <span>Agence</span>
                            <select
                                name="station"
                                required
                                class=""
                                data-skip-tom-select="true"
                            >
                                <option value="">Choisir une agence</option>
                                <?php foreach ($stations as $station) : ?>
                                    <option value="<?= (int) $station['id_station']; ?>"><?= htmlspecialchars($station['lib'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-heading">
                    <span>Détails du bon</span>
                    <span class="badge">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </span>
                </div>
                <div class="panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="dynamic_field">
                            <thead>
                                <tr>
                                    <th class="w-[200px]">Véhicule</th>
                                    <th class="w-[120px]">Quantité (L)</th>
                                    <th class="w-[120px]">Index km</th>
                                    <th class="w-[200px]">Chauffeur</th>
                                    <th class="w-[120px] hidden-column">Type Carburant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 1; $i <= 30; $i++) : ?>
                                    <tr>
                                        <td>
                                            <select
                                                name="bus-<?= $i ?>"
                                                class="input choices-select bus-select"
                                                data-skip-tom-select="true"
                                                <?= $i === 1 ? 'required' : '' ?>
                                            >
                                                <option value="">Choisir véhicule</option>
                                                <?php foreach ($buses as $bus) : ?>
                                                    <option value="<?= (int) $bus['id_bus'] ?>" data-carburant-type="<?= htmlspecialchars($bus['carburant_type'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($bus['matricule_interne'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                name="qte_go-<?= $i ?>"
                                                class="input"
                                                placeholder="Quantité"
                                                step="0.01"
                                                min="0"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                name="index_km-<?= $i ?>"
                                                class="input"
                                                placeholder="Index km"
                                                step="1"
                                                min="0"
                                            >
                                        </td>
                                        <td>
                                            <select
                                                name="chauffeur-<?= $i ?>"
                                                class="input choices-select chauffeur-select"
                                                data-skip-tom-select="true"
                                            >
                                                <option value="">Choisir le chauffeur</option>
                                                <?php foreach ($chauffeurs as $chauffeur) : ?>
                                                    <option value="<?= (int) $chauffeur['id_chauffeur'] ?>">
                                                        <?= htmlspecialchars($chauffeur['matricule'] . ' : ' . $chauffeur['nom_prenom'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="hidden-column">
                                            <input
                                                type="text"
                                                name="type_carburant-<?= $i ?>"
                                                class="input bg-gray-50 cursor-not-allowed w-20"
                                                readonly
                                                placeholder="Type"
                                            >
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="js/jquery.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Expose buses data to JS
        const busesData = <?= json_encode(array_column($buses, 'carburant_type', 'id_bus')); ?>;

        if (typeof Choices === 'undefined') {
            return;
        }
        const choicesSelects = document.querySelectorAll('.choices-select');
        
        choicesSelects.forEach((el) => {
            try {
                new Choices(el, {
                    searchEnabled: true,
                    itemSelectText: '',
                    removeItemButton: false,
                    shouldSort: false,
                    placeholder: true,
                    placeholderValue: el.getAttribute('placeholder') || 'Sélectionner...',
                    searchResultLimit: 10000,  // Show all items without limiting search results
                    searchFields: ['label', 'value'],  // Search in both label and value fields
                    searchFloor: 0,  // Show results from the first character typed
                    fuseOptions: {  // Configure fuzzy search
                        threshold: 0.3,  // Lower = stricter matching, higher = more fuzzy
                        distance: 1000   // Maximum distance for fuzzy matching
                    }
                });
            } catch (err) {
                // Silently fail or handle error
            }
        });

        const updateRowState = (select, value) => {
            const row = select.closest('tr');
            if (!row) return;

            const fuelInput = row.querySelector('input[name^="type_carburant-"]');
            
            if (fuelInput) {
                const fuelType = busesData[value] || '';
                fuelInput.value = fuelType;
                fuelInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        const busSelects = document.querySelectorAll('.bus-select');
        busSelects.forEach((select) => {
            select.addEventListener('change', () => {
                updateRowState(select, select.value);
            });

            select.addEventListener('addItem', (e) => {
                updateRowState(select, e.detail.value);
            });

            select.addEventListener('removeItem', () => {
                updateRowState(select, '');
            });
        });

        // Real-time document number validation on blur
        const numDocInput = document.getElementById('num_doc');

        numDocInput.addEventListener('blur', function() {
            const numDoc = this.value.trim();
            
            // Remove error styling
            this.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            
            if (!numDoc) {
                return; // Don't check if empty
            }
            
            // Check if document exists
            $.post('check_exist_api.php', { num_doc: numDoc }, function(data) {
                if (data.exist === '1') {
                    // Add error styling
                    numDocInput.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                    
                    // Show alert
                    alert('Ce numéro de document existe déjà');
                    
                    // Focus back on the input
                    numDocInput.focus();
                    numDocInput.select();
                }
            }, 'json').fail(() => {
                console.error('Erreur lors de la vérification du numéro de document');
            });
        });

        document.addEventListener('keypress', function (event) {
            if (event.which === 13) {
                event.preventDefault();
            }
        });
    });
</script>
</body>
</html>
