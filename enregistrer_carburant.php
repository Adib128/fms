<?php
require_once __DIR__ . '/config.php';

// Security check
require_once __DIR__ . '/helpers/security.php';
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeDocument = 'Vrac';
    $numDoc = filter_input(INPUT_POST, 'num_doc', FILTER_SANITIZE_NUMBER_INT);
    $dateDoc = filter_input(INPUT_POST, 'date_doc', FILTER_DEFAULT);
    $indexDebut = filter_input(INPUT_POST, 'index_debut', FILTER_VALIDATE_INT);
    $indexFin = filter_input(INPUT_POST, 'index_fin', FILTER_VALIDATE_INT);
    $stationId = filter_input(INPUT_POST, 'station', FILTER_VALIDATE_INT);

    if ($numDoc && $dateDoc && $stationId) {
        $insertDoc = $db->prepare('INSERT INTO doc_carburant (type, num_doc_carburant, date, index_debut, index_fin, id_station)
            VALUES (:type, :num_doc, :date, :index_debut, :index_fin, :station)');
        $insertDoc->execute([
            ':type' => $typeDocument,
            ':num_doc' => $numDoc,
            ':date' => $dateDoc,
            ':index_debut' => $indexDebut ?: 0,
            ':index_fin' => $indexFin ?: 0,
            ':station' => $stationId
        ]);

        $docId = (int) $db->lastInsertId();
        $dateSaisie = date('Y-m-d H:i:s');
        $totalQte = 0;

        $insertLigne = $db->prepare('INSERT INTO carburant (type, ref, date_saisie, qte_go, index_km, date, heure, id_bus, id_chauffeur, id_doc_carburant)
            VALUES (:type, :ref, :date_saisie, :qte_go, :index_km, :date, :heure, :bus, :chauffeur, :doc)');

        for ($i = 1; $i <= 30; $i++) {
            $busId = filter_input(INPUT_POST, 'bus-' . $i, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            if (!$busId) {
                continue;
            }

            $typeCarburant = filter_input(INPUT_POST, 'type_carburant-' . $i, FILTER_DEFAULT);
            $chauffeurId = filter_input(INPUT_POST, 'chauffeur-' . $i, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?: null;
            $heure = filter_input(INPUT_POST, 'heure-' . $i, FILTER_DEFAULT);
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
                ':heure' => $heure ?: null,
                ':bus' => $busId,
                ':chauffeur' => $chauffeurId,
                ':doc' => $docId
            ]);

            $totalQte += $qteGoValue;
        }

        $stationInfos = $db->prepare('SELECT qte FROM station WHERE id_station = :station');
        $stationInfos->execute([':station' => $stationId]);
        $stationRow = $stationInfos->fetch(PDO::FETCH_ASSOC);

        if ($stationRow) {
            $newStock = (float) $stationRow['qte'] - $totalQte;
            $updateStation = $db->prepare('UPDATE station SET qte = :qte, ind = :index WHERE id_station = :station');
            $updateStation->execute([
                ':qte' => $newStock,
                ':index' => $indexFin ?: 0,
                ':station' => $stationId
            ]);
        }

        $_SESSION['message'] = 'Enrégistrement avec succès';
        header('Location: ' . url('liste-doc-carburant'));
        exit;
    }
}

$stations = $db->query('SELECT id_station, lib FROM station ORDER BY id_station ASC')->fetchAll(PDO::FETCH_ASSOC);


// Debug: Output station order
error_log('Station order by ID ASC:');
foreach ($stations as $station) {
    error_log('ID: ' . $station['id_station'] . ' - Name: ' . $station['lib']);
}
$buses = $db->query('SELECT id_bus, matricule_interne, carburant_type FROM bus ORDER BY matricule_interne ASC')->fetchAll(PDO::FETCH_ASSOC);
$chauffeurs = $db->query('SELECT id_chauffeur, matricule, nom_prenom FROM chauffeur ORDER BY nom_prenom ASC')->fetchAll(PDO::FETCH_ASSOC);

require 'header.php';
?>
<!-- Cache busting: v20241130-2130 -->

<div id="page-wrapper">
    <div class="mx-auto flex max-w-7xl flex-col gap-6">
        <?php if (isset($_SESSION['message'])) : ?>
            <div class="alert-success flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9z" />
                </svg>
                <div class="flex-1 text-sm">
                    <?= $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            </div>
        <?php endif; ?>

        <form id="sub-form" method="post" class="flex flex-col gap-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="page-title">Enregistrer Document Vrac</h1>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="liste_doc_carburant.php" class="btn-default">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                        Retour à la liste
                    </a>
                    <button type="submit" class="btn-primary inline-flex items-center gap-2" id="btn-sub">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14" />
                            <path d="M5 12h14" />
                        </svg>
                        Enregistrer le document
                    </button>
                </div>
            </div>

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
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
                            <span>Index début</span>
                            <div class="input-with-icon">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 7v5l3 3" />
                                    <path d="M20.39 18.39A9 9 0 1 0 5.61 4.61" />
                                </svg>
                                <input type="number" name="index_debut" required class="input input--with-icon" placeholder="Début">
                            </div>
                        </label>
                        <label class="form-control form-control--enhanced">
                            <span>Index fin</span>
                            <div class="input-with-icon">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 7v5l3 3" />
                                    <path d="M20.39 18.39A9 9 0 1 0 5.61 4.61" />
                                </svg>
                                <input type="number" name="index_fin" required class="input input--with-icon" placeholder="Fin">
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
                    <span>Lignes de ravitaillement</span>
                </div>
                <div class="panel-body overflow-x-auto">
                    <table class="table w-full" id="dynamic_field">
                        <thead>
                            <tr>
                                <th>Véhicule</th>
                                <th>Qté GO (L)</th>
                                <th>Index (Km)</th>
                                <th>Heure</th>
                                <th class="hidden-column">Type Carburant</th>
                                <th>Chauffeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 1; $i <= 30; $i++) : ?>
                                <tr>
                                    <td class="min-w-[16rem]">
                                        <select name="bus-<?= $i; ?>" id="bus-<?= $i; ?>" class="input choices-select" data-skip-tom-select="true" placeholder="Choisir véhicule" <?= $i === 1 ? 'required' : ''; ?>>
                                            <option value="">Choisir véhicule</option>
                                            <?php foreach ($buses as $bus) : ?>
                                                <option value="<?= (int) $bus['id_bus']; ?>" data-carburant-type="<?= htmlspecialchars($bus['carburant_type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($bus['matricule_interne'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="qte_go-<?= $i; ?>" id="qte_go-<?= $i; ?>" class="input" step="0.01" placeholder="Quantité" data-row-field>
                                    </td>
                                    <td>
                                        <input type="number" name="index_km-<?= $i; ?>" id="index_km-<?= $i; ?>" class="input" step="0.01" placeholder="Index" data-row-field>
                                    </td>
                                    <td>
                                        <input type="time" name="heure-<?= $i; ?>" id="heure-<?= $i; ?>" class="input" data-row-field>
                                    </td>
                                    <td class="hidden-column">
                                        <input type="text" name="type_carburant-<?= $i; ?>" id="type_carburant-<?= $i; ?>" class="input bg-gray-50 cursor-not-allowed w-20" readonly data-row-field placeholder="Type">
                                    </td>
                                    <td class="min-w-[18rem]">
                                        <select name="chauffeur-<?= $i; ?>" id="chauffeur-<?= $i; ?>" class="input choices-select" data-skip-tom-select="true" data-row-field>
                                            <option value="">Choisir un chauffeur</option>
                                            <?php foreach ($chauffeurs as $chauffeur) : ?>
                                                <option value="<?= (int) $chauffeur['id_chauffeur']; ?>">
                                                    <?= htmlspecialchars($chauffeur['matricule'] . ' · ' . $chauffeur['nom_prenom'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
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
            const fields = row.querySelectorAll('[data-row-field]');
            
            if (fuelInput) {
                const fuelType = busesData[value] || '';
                fuelInput.value = fuelType;
                fuelInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            const isRequired = value !== '' && value !== null;
            fields.forEach((field) => {
                if (field.tagName === 'SELECT' || (field.tagName === 'INPUT' && !field.readOnly)) {
                    field.required = isRequired;
                }
            });
        };

        const busSelects = document.querySelectorAll('select[name^="bus-"]');
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

        const overlay = document.createElement('div');
        overlay.id = 'overlay';
        overlay.className = 'fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm';

        const loader = document.createElement('div');
        loader.id = 'PleaseWait';
        loader.className = 'fixed inset-0 z-50 hidden flex items-center justify-center';
        loader.innerHTML = '<div class="rounded-2xl bg-white px-6 py-4 shadow-xl">Chargement...</div>';

        document.body.appendChild(overlay);
        document.body.appendChild(loader);

        const showLoader = () => {
            overlay.classList.remove('hidden');
            loader.classList.remove('hidden');
        };

        const hideLoader = () => {
            overlay.classList.add('hidden');
            loader.classList.add('hidden');
        };

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

        $('#sub-form').on('submit', function (e) {
            e.preventDefault();
            const form = this;
            const numDoc = $('#num_doc').val();

            showLoader();

            $.post('check_exist_api.php', { num_doc: numDoc }, function (data) {
                if (data.exist === '0') {
                    form.submit();
                } else {
                    alert('Le document existe déjà');
                    hideLoader();
                }
            }, 'json').fail(() => {
                hideLoader();
                alert('Une erreur est survenue lors de la vérification.');
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