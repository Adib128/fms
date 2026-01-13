<?php
require 'header.php';

$debugQueries = [];

try {
    $stationOptionsStmt = $db->query('SELECT id_station, lib FROM station ORDER BY lib ASC');
    $stationOptions = $stationOptionsStmt->fetchAll(PDO::FETCH_ASSOC);
    $debugQueries[] = [
        'label' => 'Stations for filter',
        'sql' => 'SELECT id_station, lib FROM station ORDER BY lib ASC',
        'params' => [],
        'rows' => count($stationOptions)
    ];
} catch (PDOException $e) {
    $stationOptions = [];
    $debugQueries[] = [
        'label' => 'Stations for filter',
        'sql' => 'SELECT id_station, lib FROM station ORDER BY lib ASC',
        'params' => [],
        'error' => $e->getMessage()
    ];
}

if (!function_exists('format_report_value')) {
    function format_report_value($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_float($value) || is_int($value)) {
            return (string) $value;
        }

        return (string) $value;
    }
}

$reportDate = null;
$selectedStation = null;
$selectedDocuments = [];
$stationLabel = '';
$reportRows = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedDocuments = filter_input(INPUT_POST, 'document', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
    $reportDate = filter_input(INPUT_POST, 'date', FILTER_DEFAULT);
    $selectedStation = filter_input(INPUT_POST, 'station', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

    $reportDate = $reportDate !== null ? trim($reportDate) : '';

    if ($selectedStation) {
        try {
            $stationLabelStmt = $db->prepare('SELECT lib FROM station WHERE id_station = :station LIMIT 1');
            $stationLabelStmt->execute([':station' => $selectedStation]);
            $stationLabel = (string) $stationLabelStmt->fetchColumn();
            $debugQueries[] = [
                'label' => 'Station label',
                'sql' => 'SELECT lib FROM station WHERE id_station = :station LIMIT 1',
                'params' => [':station' => $selectedStation],
                'rows' => $stationLabel === '' ? 0 : 1
            ];
        } catch (PDOException $e) {
            $debugQueries[] = [
                'label' => 'Station label',
                'sql' => 'SELECT lib FROM station WHERE id_station = :station LIMIT 1',
                'params' => [':station' => $selectedStation],
                'error' => $e->getMessage()
            ];
        }
    }

    if (!empty($selectedDocuments)) {
        $carburantData = [];

        try {
            $placeholders = str_repeat('?,', count($selectedDocuments) - 1) . '?';
            $carburantStmt = $db->prepare("SELECT id_carburant, id_bus, qte_go, index_km, date, id_doc_carburant FROM carburant WHERE id_doc_carburant IN ($placeholders) ORDER BY id_carburant ASC");
            $carburantStmt->execute($selectedDocuments);
            $carburantData = $carburantStmt->fetchAll(PDO::FETCH_ASSOC);
            $debugQueries[] = [
                'label' => 'Carburant rows for multiple documents',
                'sql' => "SELECT id_carburant, id_bus, qte_go, index_km, date, id_doc_carburant FROM carburant WHERE id_doc_carburant IN ($placeholders) ORDER BY id_carburant ASC",
                'params' => $selectedDocuments,
                'rows' => count($carburantData)
            ];
        } catch (PDOException $e) {
            $debugQueries[] = [
                'label' => 'Carburant rows for multiple documents',
                'sql' => "SELECT id_carburant, id_bus, qte_go, index_km, date, id_doc_carburant FROM carburant WHERE id_doc_carburant IN ($placeholders) ORDER BY id_carburant ASC",
                'params' => $selectedDocuments,
                'error' => $e->getMessage()
            ];
            $carburantData = [];
        }

        if (!empty($carburantData)) {
            $prevCarburantStmt = $db->prepare('SELECT qte_go, index_km, date FROM carburant WHERE date < :currentDate AND id_bus = :bus ORDER BY date DESC LIMIT 1');
            $busStmt = $db->prepare('SELECT matricule_interne, conso FROM bus WHERE id_bus = :bus LIMIT 1');
            $kmStmt = $db->prepare('SELECT SUM(kilometrage) AS sm FROM kilometrage WHERE date_kilometrage BETWEEN :start AND :end AND id_bus = :bus');

            foreach ($carburantData as $carRow) {
                $busId = isset($carRow['id_bus']) ? (int) $carRow['id_bus'] : null;
                $currentDateRaw = $carRow['date'] ?? $reportDate;
                $currentIndexKm = isset($carRow['index_km']) ? (float) $carRow['index_km'] : null;
                $qteGo = isset($carRow['qte_go']) ? (float) $carRow['qte_go'] : 0.0;

                $busInfo = ['matricule_interne' => 'N/A', 'conso' => null];
                if ($busId) {
                    try {
                        $busStmt->execute([':bus' => $busId]);
                        $busData = $busStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                        if (!empty($busData)) {
                            $busInfo['matricule_interne'] = $busData['matricule_interne'] ?? 'N/A';
                            $busInfo['conso'] = isset($busData['conso']) ? (float) $busData['conso'] : null;
                        }
                        $debugQueries[] = [
                            'label' => 'Bus info',
                            'sql' => 'SELECT matricule_interne, conso FROM bus WHERE id_bus = :bus LIMIT 1',
                            'params' => [':bus' => $busId],
                            'rows' => empty($busData) ? 0 : 1
                        ];
                    } catch (PDOException $e) {
                        $debugQueries[] = [
                            'label' => 'Bus info',
                            'sql' => 'SELECT matricule_interne, conso FROM bus WHERE id_bus = :bus LIMIT 1',
                            'params' => [':bus' => $busId],
                            'error' => $e->getMessage()
                        ];
                    }
                }

                $prevIndexKm = null;
                $prevDate = null;
                if ($busId && $currentDateRaw) {
                    try {
                        $prevCarburantStmt->execute([
                            ':currentDate' => $currentDateRaw,
                            ':bus' => $busId
                        ]);
                        $prevRow = $prevCarburantStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                        $debugQueries[] = [
                            'label' => 'Previous carburant entry',
                            'sql' => 'SELECT qte_go, index_km, date FROM carburant WHERE date < :currentDate AND id_bus = :bus ORDER BY date DESC LIMIT 1',
                            'params' => [':currentDate' => $currentDateRaw, ':bus' => $busId],
                            'rows' => $prevRow ? 1 : 0
                        ];
                        if ($prevRow) {
                            $prevIndexKm = isset($prevRow['index_km']) ? (float) $prevRow['index_km'] : null;
                            $prevDate = $prevRow['date'] ?? null;
                        }
                    } catch (PDOException $e) {
                        $debugQueries[] = [
                            'label' => 'Previous carburant entry',
                            'sql' => 'SELECT qte_go, index_km, date FROM carburant WHERE date < :currentDate AND id_bus = :bus ORDER BY date DESC LIMIT 1',
                            'params' => [':currentDate' => $currentDateRaw, ':bus' => $busId],
                            'error' => $e->getMessage()
                        ];
                    }
                }

                $kmSum = null;
                if ($busId && $prevDate && $currentDateRaw) {
                    try {
                        $kmStmt->execute([
                            ':start' => $prevDate,
                            ':end' => $currentDateRaw,
                            ':bus' => $busId
                        ]);
                        $kmSumRaw = $kmStmt->fetchColumn();
                        $kmSum = $kmSumRaw !== false ? (float) $kmSumRaw : null;
                        $debugQueries[] = [
                            'label' => 'Kilometrage sum',
                            'sql' => 'SELECT SUM(kilometrage) AS sm FROM kilometrage WHERE date_kilometrage BETWEEN :start AND :end AND id_bus = :bus',
                            'params' => [':start' => $prevDate, ':end' => $currentDateRaw, ':bus' => $busId],
                            'rows' => $kmSumRaw !== false ? 1 : 0
                        ];
                    } catch (PDOException $e) {
                        $debugQueries[] = [
                            'label' => 'Kilometrage sum',
                            'sql' => 'SELECT SUM(kilometrage) AS sm FROM kilometrage WHERE date_kilometrage BETWEEN :start AND :end AND id_bus = :bus',
                            'params' => [':start' => $prevDate, ':end' => $currentDateRaw, ':bus' => $busId],
                            'error' => $e->getMessage()
                        ];
                    }
                }

                $kmDiff = null;
                if ($currentIndexKm !== null && $prevIndexKm !== null) {
                    $kmDiff = $currentIndexKm - $prevIndexKm;
                }

                $moyenne = null;
                if ($kmDiff !== null) {
                    if ($kmDiff == 0.0) {
                        $moyenne = 0.0;
                    } elseif ($kmDiff != 0.0) {
                        $moyenne = round(($qteGo / $kmDiff) * 100, 2);
                    }
                }

                $reportRows[] = [
                    'matricule' => $busInfo['matricule_interne'] ?? 'N/A',
                    'qte_go' => $qteGo,
                    'index_km' => $currentIndexKm,
                    'index_prev' => $prevIndexKm,
                    'km_diff' => $kmDiff,
                    'km_exp' => $kmSum,
                    'moy' => $moyenne,
                    'conso' => $busInfo['conso'],
                    'commentaire' => ''
                ];
            }
        }
    }
}
?>
<style>
    input[type="date"]:before {
        content: attr(placeholder) !important;
        color: #aaa;
        margin-right: 0.5em;
    }
    input[type="date"]:focus:before,
    input[type="date"]:valid:before {
        content: "";
    }
    tfoot {
        display: table-header-group;
    }
    tfoot input {
        width: 100%;
        padding: 3px;
        box-sizing: border-box;
    }
    @media print {
               .noprint {
                  display: none;
               }
               .print {
                    visibility: visible;
               }
            }
    .hide{
          display: none;
    }        
</style>
<div id="page-wrapper">
    <div class="mx-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Rapport journalier de consommation carburant</h1>
            </div>
        </div>

        <!-- Search Panel -->
        <div class="panel">
            <div class="panel-heading">
                <span>Critères de recherche</span>
                <span class="badge">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    Filtres
                </span>
            </div>
            <div class="panel-body">
                <form method="POST" action="<?= url('rapport-jour') ?>" role="form" id="search_form" class="search-form">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Date Field -->
                        <div class="space-y-2">
                            <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" name="date" id="date" 
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                value="<?php echo isset($_POST['date']) ? $_POST['date'] : ''; ?>">
                        </div>
                        
                        <!-- Station Field -->
                        <div class="space-y-2">
                            <label for="station" class="block text-sm font-medium text-gray-700">Agence</label>
                            <select
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                name="station"
                                id="station"
                                data-skip-tom-select="true"
                            >
                                <option value="">Sélectionner une agence</option>
                                <?php
                                $liste_station = $db->query("SELECT * FROM station ORDER BY id_station ASC");
                                foreach ($liste_station as $row) {
                                    echo '<option value="' . $row["id_station"] . '" ' . ((isset($_POST['station']) && $_POST['station'] == $row["id_station"]) ? 'selected' : '') . '>';
                                    echo htmlspecialchars($row["lib"], ENT_QUOTES, 'UTF-8');
                                    echo '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Document Field -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Document</label>
                            <div class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                <div id="document-checkboxes" class="max-h-32 overflow-y-auto space-y-2">
                                    <div class="text-gray-500">Sélectionner une date et une agence pour voir les documents</div>
                                </div>
                            </div>
                            <input type="hidden" name="document[]" id="document-hidden" value="">
                        </div>
                    </div>
                    
                    <!-- Search Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 mt-6">
                        <button type="button" id="reset_search" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                <path d="M3 3v5h5"/>
                            </svg>
                            Réinitialiser
                        </button>
                        <button type="button" id="search" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            Chercher
                        </button>
                    </div>
                </form>
            </div>
        </div>

         

                <?php
                if ($_SERVER["REQUEST_METHOD"] == 'POST' && !empty($reportDate)) {
                    ?> 

                    <table class="table table-striped table-bordered" style="width:100%" id="tab">
                        <thead>
                        <th>Véhicule</th>
                        <th>Q té GO (l)</th>
                        <th>Ravitaillement</th>
                        <th>Ravitaillement précédent</th>
                        <th>Km Index  </th>
                        <th>Km Exp  </th>
                        <th>Klm Estimé</th>
                        <th>Moy </th>
                        <th>STD Cons</th>
                        <th>Commentaire</th>
                        </thead>
                        <tbody>
                            <?php
                            
                            if (!empty($carburantData)) {
                                foreach ($carburantData as $row) {
                                ?>
                                <tr>
                                    <td>
                                        <?php
                                        $id_bus = $row["id_bus"];
                                        $id_carburant = $row["id_carburant"] ?? null;
                                        $current_date = $row["date"] ?? $reportDate;
                                        
                                        // Initialize variables
                                        $index_km_j = null;
                                        $dernier_date = null;
                                        $matricule_interne = 'N/A';
                                        $conso = null;
                                        
                                        // Get previous ravitaillement (carburant entry) for this bus by id_carburant
                                        // This handles cases where there are multiple refills on the same day
                                        if ($id_carburant !== null) {
                                            $prevStmt = $db->prepare("SELECT id_carburant, index_km, date FROM carburant WHERE id_carburant < :id_carburant AND id_bus = :id_bus ORDER BY id_carburant DESC LIMIT 1");
                                            $prevStmt->execute([':id_carburant' => $id_carburant, ':id_bus' => $id_bus]);
                                            $prevRow = $prevStmt->fetch(PDO::FETCH_ASSOC);
                                            
                                            if ($prevRow) {
                                                $index_km_j = $prevRow["index_km"] ?? null;
                                                $dernier_date = $prevRow["date"] ?? null;
                                            }
                                        }

                                        // Get bus info
                                        $item_bus = $db->prepare("SELECT * FROM bus WHERE id_bus = :id_bus LIMIT 1");
                                        $item_bus->execute([':id_bus' => $id_bus]);
                                        $busRow = $item_bus->fetch(PDO::FETCH_ASSOC);
                                        
                                        if ($busRow) {
                                            $matricule_interne = $busRow["matricule_interne"] ?? 'N/A';
                                            $conso = $busRow["conso"] ?? null;
                                        }
                                        
                                        echo htmlspecialchars($matricule_interne, ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        echo htmlspecialchars($row["qte_go"] ?? '', ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Ravitaillement - current ravitaillement index
                                        echo htmlspecialchars($row["index_km"] ?? '', ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Ravitaillement précédent - previous ravitaillement index
                                        echo $index_km_j !== null ? htmlspecialchars($index_km_j, ENT_QUOTES, 'UTF-8') : '';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Km Index - difference between current and previous index
                                        $current_index_km = $row["index_km"] ?? null;
                                        if ($current_index_km !== null && $index_km_j !== null) {
                                            $diff = $current_index_km - $index_km_j;
                                            echo htmlspecialchars($diff, ENT_QUOTES, 'UTF-8');
                                        } else {
                                            echo '';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Km Exp - sum of kilometrage between previous date and current date
                                        $somme = null;
                                        if ($dernier_date && $current_date) {
                                            $kmStmt = $db->prepare("SELECT SUM(kilometrage) as sm FROM kilometrage WHERE date_kilometrage BETWEEN :start_date AND :end_date AND id_bus = :id_bus");
                                            $kmStmt->execute([
                                                ':start_date' => $dernier_date,
                                                ':end_date' => $current_date,
                                                ':id_bus' => $id_bus
                                            ]);
                                            $kmRow = $kmStmt->fetch(PDO::FETCH_ASSOC);
                                            $somme = $kmRow["sm"] ?? null;
                                        }
                                        echo $somme !== null ? htmlspecialchars($somme, ENT_QUOTES, 'UTF-8') : '';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Klm Estimé - estimated kilometers = (quantity * 100) / std consumption
                                        $qte_go = $row["qte_go"] ?? 0;
                                        $km_estime = null;
                                        
                                        if ($conso !== null && $conso > 0) {
                                            $km_estime = ($qte_go * 100) / $conso;
                                            $km_estime = round($km_estime);
                                            echo htmlspecialchars($km_estime, ENT_QUOTES, 'UTF-8');
                                        } else {
                                            echo '';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Moy - average consumption
                                        $current_index_km = $row["index_km"] ?? null;
                                        $qte_go = $row["qte_go"] ?? 0;
                                        
                                        if ($current_index_km !== null && $index_km_j !== null) {
                                            $diff = $current_index_km - $index_km_j;
                                            if ($diff != 0) {
                                                $mo = ($qte_go / $diff) * 100;
                                                $mo = round($mo, 2);
                                                echo htmlspecialchars($mo, ENT_QUOTES, 'UTF-8');
                                            } else {
                                                echo '0';
                                            }
                                        } else {
                                            echo '';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        echo $conso !== null ? htmlspecialchars($conso, ENT_QUOTES, 'UTF-8') : '';
                                        ?>
                                    </td>
                                    <td></td>
                                </tr>
                                <?php }
                            } else {
                                echo '<tr><td colspan="10" class="text-center">Aucune donnée trouvée pour les documents sélectionnés</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>

                    <?php
                }
                ?>
                <div style="float: right; display: none;" class="print-only">
                    Visa chef de service
                </div>

<style>
@media print {
    .print-only {
        display: block !important;
    }
    
    .no-print {
        display: none !important;
    }
}
</style>

            </form>

            <br>
        </div>
    </div>
</div>
</div>

<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script>
<script>
    $(document).ready(function () {

        $("#date, #station").on("change", function () {
    loadDocuments();
});

// Initialize summary on page load if there are selected documents
function initializeSummary() {
    const selectedDocs = <?= isset($_POST['document']) ? json_encode($_POST['document']) : '[]' ?>;
    if (selectedDocs.length > 0) {
        // Show summary with POST data
        const summaryDiv = $('#selected-documents-summary');
        const listDiv = $('#selected-documents-list');
        
        // Get document labels from the loaded checkboxes
        setTimeout(function() {
            updateDocumentSelection();
        }, 500); // Increased timeout to ensure checkboxes are loaded
    }
}

// Call initialization after page loads
initializeSummary();

// If there's POST data, load documents automatically
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['date']) && !empty($_POST['station'])): ?>
    loadDocuments();
<?php endif; ?>

function loadDocuments() {
    const date = $("#date").val();
    const station = $("#station").val();
    const selectedDocs = <?= isset($_POST['document']) ? json_encode($_POST['document']) : '[]' ?>;

    if (!date || !station) {
        $("#document-checkboxes").html('<div class="text-gray-500">Sélectionner une date et une agence pour voir les documents</div>');
        $("#document-hidden").val('');
        return;
    }

    $.get("/api/get_documents_by_date_station.php", { date: date, station_id: station }, function (response) {

        let documents = [];

        // Handle response
        if (response && Array.isArray(response)) {
            documents = response;
        }

        // Build checkboxes
        let checkboxes = '';
        
        if (documents.length === 0) {
            checkboxes = '<div class="text-gray-500">Aucun document trouvé pour cette date et cette agence</div>';
        } else {
            checkboxes = '<div class="space-y-2">';
            documents.forEach(doc => {
                const isChecked = selectedDocs.includes(doc.id_doc_carburant.toString()) ? 'checked' : '';
                checkboxes += `
                    <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                        <input type="checkbox" name="document_check[]" value="${doc.id_doc_carburant}" 
                               class="document-checkbox rounded text-blue-600 focus:ring-blue-500" 
                               ${isChecked} onchange="updateDocumentSelection()">
                        <span class="text-sm">${doc.num_doc_carburant} - ${doc.type}</span>
                    </label>
                `;
            });
            checkboxes += '</div>';
        }

        $("#document-checkboxes").html(checkboxes);
        
        // Initialize selection after checkboxes are added to DOM
        setTimeout(function() {
            updateDocumentSelection();
        }, 100);
    }, "json").fail(function() {
        $("#document-checkboxes").html('<div class="text-red-500">Erreur de chargement des documents</div>');
        $("#document-hidden").val('');
    });
}

function updateDocumentSelection() {
    const selectedValues = [];
    const selectedLabels = [];
    $('.document-checkbox:checked').each(function() {
        selectedValues.push($(this).val());
        selectedLabels.push($(this).next('span').text());
    });
    $('#document-hidden').val(selectedValues.join(','));
    
    // Update selected documents summary
    const summaryDiv = $('#selected-documents-summary');
    const listDiv = $('#selected-documents-list');
    
    if (selectedValues.length > 0) {
        summaryDiv.removeClass('hidden');
        listDiv.html(selectedLabels.map(label => `<div class="flex items-center space-x-1">
            <svg class="w-3 h-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
            </svg>
            <span>${label}</span>
        </div>`).join(''));
    } else {
        summaryDiv.addClass('hidden');
        listDiv.empty();
    }
}


        /*************************************************************************************************/
        $("#search").click(function () {
            var date = $("#date").val();
            var station = $("#station").val();
            var checkedBoxes = $('.document-checkbox:checked');
            
            if (date == "" || station == "" || checkedBoxes.length == 0) {
                alert("Vueillez verifier les critére de votre recherche");
            } else {
                // Update hidden field with selected values
                updateDocumentSelection();
                var documents = $("#document-hidden").val();
                
                // Convert comma-separated values to array for form submission
                var docArray = documents.split(',');
                // Create hidden inputs for each selected document
                var form = $("#search_form");
                form.find('input[name="document[]"]').remove();
                docArray.forEach(function(docId) {
                    if(docId.trim() !== '') {
                        form.append('<input type="hidden" name="document[]" value="' + docId.trim() + '">');
                    }
                });
                form.submit();
            }
        });

        /*************************************************************************************************/
        $("#reset_search").click(function () {
            // Clear all form fields
            $("#date").val("");
            $("#station").val("");
            $("#document-checkboxes").html('<div class="text-gray-500">Sélectionner une date et une agence pour voir les documents</div>');
            $("#document-hidden").val("");
            $("#selected-documents-summary").addClass('hidden');
            $("#selected-documents-list").empty();
            // Submit form to reset results
            $("#search_form").submit();
        });

        // ************************************************************************************************/
        $('tr.Entries').each(function () {
            var $this = $(this),
                    t = this.cells[1].textContent.split('-');
            $this.data('_ts', new Date(t[2], t[1] - 1, t[0]).getTime());
        }).sort(function (a, b) {
            return $(a).data('_ts') > $(b).data('_ts');
        }).appendTo('tbody');

        // ************************************************************************************************/

        // Setup - add a text input to each footer cell
        $('#tab tfoot th').each(function (i) {
            var title = $('#example thead th').eq($(this).index()).text();
            $(this).html('<input type="text" placeholder="" data-index="' + i + '" />');
        });

        var ch = "Rapport Journalier le: " + "<?php echo $reportDate ? date('d/m/Y', strtotime($reportDate)) : ''; ?>";
        var table = $('#tab').DataTable({
            "bSort": false,
            dom: 'Bfrtip',
            bPaginate: false,
            bLengthChange: false,
            buttons: [
                {
                    extend: 'print',
                    title: ch,
                    text: 'Imprimmer',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                },
                {
                    extend: 'excelHtml5',
                    title: ch,
                    text: 'Exporter Excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    title: ch,
                    text: 'Exporter PDF',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                }
            ],

            initComplete: function () {
                this.api().columns('.search_select').every(function () {
                    var column = this;
                    var select = $('<select><option value=""></option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                        $(this).val()
                                        );

                                column
                                        .search(val ? '^' + val + '$' : '', true, false)
                                        .draw();
                            });

                    column.data().unique().sort().each(function (d, j) {
                        select.append('<option value="' + d + '">' + d + '</option>')
                    });
                });
            }
        });

        // Filter event handler
        $(table.table().container()).on('keyup', 'tfoot input', function () {
            table
                    .column($(this).data('index'))
                    .search(this.value)
                    .draw();
        });
    });
</script>
</body>
</html>