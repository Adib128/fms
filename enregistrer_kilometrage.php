<?php
require 'header.php';

// Security check
require_once __DIR__ . '/helpers/security.php';
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());
?>

<div id="page-wrapper">
    <div class="mx-auto flex max-w-7xl flex-col gap-6">
        <?php if (isset($_SESSION["message"])) : ?>
            <div class="alert-success flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9z" />
                </svg>
                <div class="flex-1 text-sm">
                    <?= $_SESSION["message"]; unset($_SESSION["message"]); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION["error"])) : ?>
            <div class="border-l-4 border-red-500 bg-red-50 p-4 rounded">
                
                <div class="flex-1 text-sm">
                    <?= $_SESSION["error"]; unset($_SESSION["error"]); ?>
                </div>
            </div>
        <?php endif; ?>

        <form id="sub-form" method="post" class="flex flex-col gap-6" onsubmit="return validateForm()">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="page-title">Enregistrer kilométrage</h1>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="liste_kilometrage.php" class="btn-default">
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
                        Enregistrer le kilométrage
                    </button>
                </div>
            </div>

            <div class="panel">
                <div class="panel-heading">
                    <span>Informations générales</span>
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
                        .select--with-icon {
                            width: 100%;
                            padding: 0.625rem 0.75rem 0.625rem 2.75rem;
                            border: 1px solid #d1d5db;
                            border-radius: 0.5rem;
                            font-size: 0.875rem;
                            line-height: 1.25rem;
                            color: #111827;
                            background-color: #ffffff;
                            transition: all 0.15s ease-in-out;
                            appearance: none;
                            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                            background-position: right 0.5rem center;
                            background-repeat: no-repeat;
                            background-size: 1.5em 1.5em;
                            padding-right: 2.5rem;
                        }
                        .select--with-icon:focus {
                            outline: none;
                            border-color: #3b82f6;
                            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                        }
                        .select--basic {
                            width: 100%;
                            padding: 0.625rem 0.75rem;
                            border: 1px solid #d1d5db;
                            border-radius: 0.5rem;
                            font-size: 0.875rem;
                            line-height: 1.25rem;
                            color: #111827;
                            background-color: #ffffff;
                            transition: all 0.15s ease-in-out;
                            appearance: none;
                            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                            background-position: right 0.5rem center;
                            background-repeat: no-repeat;
                            background-size: 1.5em 1.5em;
                            padding-right: 2.5rem;
                        }
                        .select--basic:focus {
                            outline: none;
                            border-color: #3b82f6;
                            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                        }
                        .table select {
                            width: 100%;
                            padding: 0.5rem 0.75rem;
                            border: 1px solid #d1d5db;
                            border-radius: 0.375rem;
                            font-size: 0.875rem;
                            line-height: 1.25rem;
                            color: #111827;
                            background-color: #ffffff;
                            transition: all 0.15s ease-in-out;
                            appearance: none;
                            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                            background-position: right 0.5rem center;
                            background-repeat: no-repeat;
                            background-size: 1.5em 1.5em;
                            padding-right: 2.5rem;
                        }
                        .table select:focus {
                            outline: none;
                            border-color: #3b82f6;
                            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                        }
                        
                        /* Alert styles */
                        .alert-error {
                            @apply rounded-lg border border-red-300 bg-red-50 px-4 py-3 shadow-md;
                        }
                        .alert-error .flex-1 {
                            color: #dc2626 !important;
                            font-weight: 500 !important;
                            font-size: 0.875rem !important;
                        }
                        .alert-error svg {
                            color: #dc2626 !important;
                            flex-shrink: 0 !important;
                        }
                        .alert-success {
                            @apply rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800;
                        }
                    </style>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="form-control form-control--enhanced">
                            <span>Agence</span>
                            <select
                                name="station"
                                required
                                class=""
                                data-skip-tom-select="true"
                            >
                                <option value="">Choisir une agence</option>
                                <?php
                                $liste_station = $db->query("SELECT * FROM station ORDER BY id_station ASC");
                                foreach ($liste_station as $row) {
                                    echo '<option value="' . (int) $row["id_station"] . '">';
                                    echo htmlspecialchars($row["lib"], ENT_QUOTES, 'UTF-8');
                                    echo '</option>';
                                }
                                ?>
                            </select>
                        </label>
                        <label class="form-control form-control--enhanced">
                            <span>Date de parcours</span>
                            <div class="input-with-icon">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" />
                                    <path d="M16 2v4" />
                                    <path d="M8 2v4" />
                                    <path d="M3 10h18" />
                                </svg>
                                <input type="date" name="date_parcours" value="<?php echo date('Y-m-d'); ?>" required class="input input--with-icon">
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-heading">
                    <span>Kilométrage par véhicule</span>
                    <span class="badge">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 16L5 12l4-4" />
                            <path d="M15 8l4 4-4 4" />
                        </svg>
                    </span>
                </div>
                <div class="panel-body">
                    <div class="overflow-x-auto">
                        <table class="table table-bordered" id="dynamic_field">
                            <thead>
                                <tr>
                                    <th class="w-1/2">Véhicule</th>
                                    <th class="w-1/2">Kilométrage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                for ($i = 1; $i < 80; $i++) {
                                    ?> 
                                    <tr>  
                                        <td>
                                            <select class="bus-select" name="bus-<?php echo $i; ?>" id="bus-<?php echo $i; ?>" <?php if ($i == 1) { echo "required"; } ?>>
                                                <option value=""></option>
                                                <?php
                                                $liste_bus = $db->query("SELECT * FROM bus ORDER BY id_bus ASC");
                                                foreach ($liste_bus as $row) {
                                                    echo '<option value="' . $row["id_bus"] . '">';
                                                    echo $row["matricule_interne"];
                                                    echo '</option>';
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="kilometrage-<?php echo $i; ?>" id="kilometrage-<?php echo $i; ?>" class="input--with-icon" placeholder="Kilométrage"> 
                                        </td> 
                                    </tr>  
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
if ($_SERVER["REQUEST_METHOD"] == 'POST') {
    $station = $_POST["station"];
    $date_parcours = $_POST["date_parcours"];
    
    // Debug: Log the values we're checking
    error_log("Checking for duplicates - Station: $station, Date: $date_parcours");
    
    // Convert date to YYYY-MM-DD format to match database
    $formatted_date = date('Y-m-d', strtotime($date_parcours));
    error_log("Formatted date for database: $formatted_date");
    
    // Check if this agency and date combination already exists
    $checkQuery = $db->prepare("SELECT COUNT(*) as count FROM kilometrage k 
                                INNER JOIN bus b ON k.id_bus = b.id_bus 
                                WHERE b.id_station = :station AND k.date_kilometrage = :formatted_date");
    $checkQuery->bindParam(':station', $station);
    $checkQuery->bindParam(':formatted_date', $formatted_date);
    $checkQuery->execute();
    $result = $checkQuery->fetch(PDO::FETCH_ASSOC);
    
    // Debug: Log the result
    error_log("Duplicate check result: " . $result['count']);
    
    if ($result['count'] > 0) {
        // Get agency name for the error message
        $stationQuery = $db->prepare("SELECT lib FROM station WHERE id_station = :station");
        $stationQuery->bindParam(':station', $station);
        $stationQuery->execute();
        $stationData = $stationQuery->fetch(PDO::FETCH_ASSOC);
        $stationName = $stationData['lib'];
        
        // Format date for display
        $displayDate = date('d/m/Y', strtotime($date_parcours));
        
        $_SESSION["error"] = "Kilométrage de l'agence \"$stationName\" du $displayDate est déjà enregistré";
        echo "<script> window.location.replace('enregistrer_kilometrage.php')</script>";
        exit;
    }
    
    $date_saisie = date("Y-m-d");
    $num_doc = 0000;
    $j = 1;
    while ($j < 80) {
        $bus = "bus-" . $j;
        $bus = $_POST[$bus];
        $bus = (int) $bus;

        if ($bus != 0) {
            $kilometrage = "kilometrage-" . $j;
            $kilometrage = $_POST[$kilometrage];

            $select = $db->query("SELECT * FROM total_kilometrage WHERE id_bus='$bus'");
            $total_kilometrage = 0;
            foreach ($select as $row) {
                $total_kilometrage = $row["kilometrage"];
            }

            $total_kilometrage = (int) $total_kilometrage;
            $kilometrage = (int) $kilometrage;
            $kilometrage_actuel = 0;
            $kilometrage_actuel = $total_kilometrage + $kilometrage;

            // Insert kilomètrage
            $sth = $db->prepare('INSERT INTO kilometrage VALUES(NULL,:date_parcours,:kilometrage,:num_doc,:id_bus)');
            $sth->bindParam(':date_parcours', $date_parcours);
            $sth->bindParam(':num_doc', $num_doc);
            $sth->bindParam(':kilometrage', $kilometrage);
            $sth->bindParam(':id_bus', $bus);
            $sth->execute();
            
            //Update Kilomètrage totale
            $sth = $db->prepare('UPDATE total_kilometrage SET kilometrage=:total_kilometrage WHERE id_bus=:id_bus');
            $sth->bindParam(':total_kilometrage', $kilometrage_actuel);
            $sth->bindParam(':id_bus', $bus);
            $sth->execute();
        }
        $j++;
    }

    $_SESSION["message"] = "Succées d'ajout de kilomètrage";
    echo "<script> window.location.replace('enregistrer_kilometrage.php')</script>";
}
?>

<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script>
    // Form validation function
    function validateForm() {
        // Check if agency is selected
        const station = document.querySelector('select[name="station"]');
        if (!station.value || station.value === "") {
            alert('Veuillez sélectionner une agence');
            station.focus();
            return false;
        }

        // Check if date is selected
        const date = document.querySelector('input[name="date_parcours"]');
        if (!date.value || date.value === "") {
            alert('Veuillez sélectionner une date de parcours');
            date.focus();
            return false;
        }

        // Check if at least one vehicle is selected
        let hasVehicle = false;
        let hasValidKilometrage = true;
        
        // Check all vehicle rows
        for (let i = 1; i < 80; i++) {
            const busSelect = document.getElementById('bus-' + i);
            const kilometrageInput = document.getElementById('kilometrage-' + i);
            
            if (busSelect && kilometrageInput) {
                const busValue = busSelect.value;
                const kilometrageValue = kilometrageInput.value;
                
                if (busValue && busValue !== "") {
                    hasVehicle = true;
                    
                    // If vehicle is selected, kilometrage is required
                    if (!kilometrageValue || kilometrageValue === "" || parseFloat(kilometrageValue) <= 0) {
                        alert('Veuillez saisir un kilométrage valide pour le véhicule sélectionné à la ligne ' + i);
                        kilometrageInput.focus();
                        hasValidKilometrage = false;
                        break;
                    }
                }
            }
        }

        if (!hasVehicle) {
            alert('Veuillez sélectionner au moins un véhicule');
            return false;
        }

        if (!hasValidKilometrage) {
            return false;
        }

        return true;
    }

    // Dynamic validation when vehicle selection changes
    document.querySelectorAll('.bus-select').forEach(function(select) {
        select.addEventListener('change', function() {
            const rowId = this.id.replace('bus-', '');
            const kilometrageInput = document.getElementById('kilometrage-' + rowId);
            
            if (this.value && this.value !== "") {
                kilometrageInput.setAttribute('required', 'required');
                kilometrageInput.setAttribute('placeholder', 'Kilométrage requis');
            } else {
                kilometrageInput.removeAttribute('required');
                kilometrageInput.setAttribute('placeholder', 'Kilométrage');
                kilometrageInput.value = '';
            }
        });
    });

    // Prevent form submission on Enter key
    $(document).keypress(function (event) {
        if (event.which == '13') {
            event.preventDefault();
        }
    });
</script>
</body>
</html>