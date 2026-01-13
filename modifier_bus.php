<?php
// Process form submission BEFORE any HTML output
if ($_SERVER["REQUEST_METHOD"] == 'POST') {
    require_once "config.php";
    require_once __DIR__ . '/app/helpers.php';
    session_start();
    
    $id = $_POST["id"];
    $matricule = $_POST["matricule"];
    $matricule_interne = $_POST["matricule_interne"];
    $type = $_POST["type"];
    $marque = $_POST["marque"];
    $station = $_POST["station"];
    $freq_vidange_moteur = $_POST["freq_vidange_moteur"];
    $freq_vidange_boite = $_POST["freq_vidange_boite"];
    $freq_vidange_pont = $_POST["freq_vidange_pont"];
    $conso = $_POST["conso"];
    $conso_huile = $_POST["conso_huile"];
    $contenance_reservoir = !empty($_POST["contenance_reservoir"]) ? $_POST["contenance_reservoir"] : null;
    $date_mise_circulation = !empty($_POST["date_mise_circulation"]) ? $_POST["date_mise_circulation"] : null;
    $etat = $_POST["etat"];
    $carburant_type = $_POST["carburant_type"];
    $huile_moteur = $_POST["huile_moteur"];
    $huile_boite_vitesse = $_POST["huile_boite_vitesse"];
    $huile_pont = $_POST["huile_pont"];

    $sth = $db->prepare('UPDATE bus SET matricule=:matricule,matricule_interne=:matricule_interne,type=:type,marque=:marque,freq_vidange_moteur=:freq_vidange_moteur,freq_vidange_boite=:freq_vidange_boite,freq_vidange_pont=:freq_vidange_pont,conso=:conso,conso_huile=:conso_huile,contenance_reservoir=:contenance_reservoir,date_mise_circulation=:date_mise_circulation,id_station=:station,etat=:etat,carburant_type=:carburant_type,huile_moteur=:huile_moteur,huile_boite_vitesse=:huile_boite_vitesse,huile_pont=:huile_pont WHERE id_bus=:id');
    $sth->bindParam(':matricule', $matricule);
    $sth->bindParam(':matricule_interne', $matricule_interne);
    $sth->bindParam(':type', $type);
    $sth->bindParam(':marque', $marque);
    $sth->bindParam(':freq_vidange_moteur', $freq_vidange_moteur);
    $sth->bindParam(':freq_vidange_boite', $freq_vidange_boite);
    $sth->bindParam(':freq_vidange_pont', $freq_vidange_pont);
    $sth->bindParam(':conso', $conso);
    $sth->bindParam(':conso_huile', $conso_huile);
    $sth->bindParam(':contenance_reservoir', $contenance_reservoir);
    $sth->bindParam(':date_mise_circulation', $date_mise_circulation);
    $sth->bindParam(':station', $station);
    $sth->bindParam(':etat', $etat);
    $sth->bindParam(':carburant_type', $carburant_type);
    $sth->bindParam(':huile_moteur', $huile_moteur);
    $sth->bindParam(':huile_boite_vitesse', $huile_boite_vitesse);
    $sth->bindParam(':huile_pont', $huile_pont);
    $sth->bindParam(':id', $id);
    $sth->execute();

    $_SESSION["message"] = "Succées de modification de véhicule";
    header('Location: ' . url('liste-vehicule'));
    exit;
}

require 'header.php' ?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Modifier Véhicule</h1>
                <p class="mt-2 text-sm text-gray-600">Mettre à jour les informations du véhicule</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <?php
                $id = $_GET["id"];
                $select = $db->query("SELECT * FROM bus WHERE id_bus = '$id'");
                foreach ($select as $item) {
                    ?>
                    <form class="space-y-8" enctype="multipart/form-data" method="post" action="#">
                        <input type="hidden" name="id" value="<?php echo $item["id_bus"] ?>">
                        
                        <!-- Matricule Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="matricule" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Matricule</label>
                            <div class="md:col-span-8">
                                <input type="text" id="matricule" name="matricule"
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["matricule"] ?>">
                            </div>
                        </div>
                        
                        <!-- Numéro Parc Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="matricule_interne" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Numéro Parc</label>
                            <div class="md:col-span-8">
                                <input type="text" id="matricule_interne" name="matricule_interne" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["matricule_interne"] ?>">
                            </div>
                        </div>
                        
                        <!-- Genre Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="type" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Genre</label>
                            <div class="md:col-span-8">
                                <select id="type" name="type" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 tom-select"
                                    data-placeholder="Choisir le genre" data-search-placeholder="Rechercher un genre" data-icon="bus">
                                    <option value=""></option>
                                    <?php
                                    $tab = array(
                                        'MICRO-BUS',
                                        'AUTOCAR',
                                        'AUTOBUS',
                                        'ARTICULE',
                                        'CONFORT',
                                        'VL',
                                        'CTTE',
                                        'Engin MNT'
                                    );
                                    foreach ($tab as $key => $value) {
                                        if ($value == $item["type"]) {
                                            echo '<option selected value="' . $value . '">' . $value . '</option>';
                                        } else {
                                            echo '<option value="' . $value . '">' . $value . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Marque Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="marque" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Marque</label>
                            <div class="md:col-span-8">
                                <input type="text" id="marque" name="marque" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["marque"] ?>">
                            </div>
                        </div>
                        
                        <!-- Agence Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="station" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Affectation / Agence</label>
                            <div class="md:col-span-8">
                                <select id="station" name="station" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 tom-select"
                                    data-placeholder="Choisir une agence" data-search-placeholder="Rechercher une agence" data-icon="station">
                                    <option value=""></option>
                                    <?php
                                    $liste_station = $db->query("SELECT * FROM station ORDER BY id_station ASC");
                                    foreach ($liste_station as $row) {
                                        if ($item["id_station"] == $row["id_station"]) {
                                            echo '<option value="' . $row["id_station"] . '" selected>';
                                            echo htmlspecialchars($row["lib"], ENT_QUOTES, 'UTF-8');
                                            echo '</option>';
                                        } else {
                                            echo '<option value="' . $row["id_station"] . '">';
                                            echo htmlspecialchars($row["lib"], ENT_QUOTES, 'UTF-8');
                                            echo '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Fréquence Vidange Moteur Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="freq_vidange_moteur" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Fréquence vidange moteur</label>
                            <div class="md:col-span-8">
                                <input type="number" id="freq_vidange_moteur" name="freq_vidange_moteur" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["freq_vidange_moteur"] ?>">
                            </div>
                        </div>
                        
                        <!-- Fréquence Vidange Boite Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="freq_vidange_boite" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Fréquence vidange boite</label>
                            <div class="md:col-span-8">
                                <input type="number" id="freq_vidange_boite" name="freq_vidange_boite" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["freq_vidange_boite"] ?>">
                            </div>
                        </div>
                        
                        <!-- Fréquence Vidange Pont Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="freq_vidange_pont" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Fréquence vidange pont</label>
                            <div class="md:col-span-8">
                                <input type="number" id="freq_vidange_pont" name="freq_vidange_pont" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["freq_vidange_pont"] ?>">
                            </div>
                        </div>
                        
                        <!-- Standard Consommation Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="conso" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Standard Consommation</label>
                            <div class="md:col-span-8">
                                <input type="number" id="conso" name="conso" step="0.01" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["conso"] ?>">
                            </div>
                        </div>
                        
                        <!-- Standard Consommation Huile Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="conso_huile" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Standard Consommation Huile</label>
                            <div class="md:col-span-8">
                                <input type="number" id="conso_huile" name="conso_huile" step="0.01" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["conso_huile"] ?>">
                            </div>
                        </div>

                        <!-- Contenance Réservoir Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="contenance_reservoir" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Contenance Réservoir</label>
                            <div class="md:col-span-8">
                                <input type="number" id="contenance_reservoir" name="contenance_reservoir"
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["contenance_reservoir"] ?>"
                                    placeholder="Entrez la contenance du réservoir (L)">
                            </div>
                        </div>

                        <!-- Date de Mise en Circulation Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="date_mise_circulation" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Date de mise en circulation</label>
                            <div class="md:col-span-8">
                                <input type="date" id="date_mise_circulation" name="date_mise_circulation"
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["date_mise_circulation"] ?>">
                            </div>
                        </div>

                        <!-- État Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="etat" class="text-sm font-medium text-gray-700 text-right md:col-span-2">État</label>
                            <div class="md:col-span-8">
                                <select id="etat" name="etat" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                    <option value="Disponible" <?= $item['etat'] === 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                                    <option value="En réparation" <?= $item['etat'] === 'En réparation' ? 'selected' : '' ?>>En réparation</option>
                                    <option value="Immobiliser" <?= $item['etat'] === 'Immobiliser' ? 'selected' : '' ?>>Immobiliser</option>
                                    <option value="Réformé" <?= $item['etat'] === 'Réformé' ? 'selected' : '' ?>>Réformé</option>
                            </select>
                        </div>
                    </div>

                    <!-- Type Carburant Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="carburant_type" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Type Carburant</label>
                        <div class="md:col-span-8">
                            <select id="carburant_type" name="carburant_type" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                <option value="GO" <?= $item['carburant_type'] === 'GO' ? 'selected' : '' ?>>GO</option>
                                <option value="GSS" <?= $item['carburant_type'] === 'GSS' ? 'selected' : '' ?>>GSS</option>
                                <option value="ESP" <?= $item['carburant_type'] === 'ESP' ? 'selected' : '' ?>>ESP</option>
                            </select>
                        </div>
                    </div>

                    <?php
                    $oil_types = $db->query("SELECT * FROM oil_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <!-- Huile Moteur Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="huile_moteur" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Huile Moteur</label>
                        <div class="md:col-span-8">
                            <select id="huile_moteur" name="huile_moteur" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 tom-select"
                                data-placeholder="Choisir l'huile moteur">
                                <option value=""></option>
                                <?php foreach ($oil_types as $oil) : ?>
                                    <option value="<?= $oil['id'] ?>" <?= $oil['id'] == $item['huile_moteur'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($oil['name'] . ' (' . $oil['designation'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Huile Boite Vitesse Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="huile_boite_vitesse" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Huile Boite Vitesse</label>
                        <div class="md:col-span-8">
                            <select id="huile_boite_vitesse" name="huile_boite_vitesse" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 tom-select"
                                data-placeholder="Choisir l'huile boite vitesse">
                                <option value=""></option>
                                <?php foreach ($oil_types as $oil) : ?>
                                    <option value="<?= $oil['id'] ?>" <?= $oil['id'] == $item['huile_boite_vitesse'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($oil['name'] . ' (' . $oil['designation'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Huile Pont Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="huile_pont" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Huile Pont</label>
                        <div class="md:col-span-8">
                            <select id="huile_pont" name="huile_pont" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 tom-select"
                                data-placeholder="Choisir l'huile pont">
                                <option value=""></option>
                                <?php foreach ($oil_types as $oil) : ?>
                                    <option value="<?= $oil['id'] ?>" <?= $oil['id'] == $item['huile_pont'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($oil['name'] . ' (' . $oil['designation'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                        <!-- Kilométrage Field (Read-only) -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="kilometrage" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Kilométrage</label>
                            <?php
                            $id_bus = $item["id_bus"];
                            $params = array(
                                'id_bus' => $id_bus
                            );
                            $select = $db->prepare("SELECT * FROM total_kilometrage WHERE id_bus=:id_bus");
                            $select->execute($params);
                            $e = $select->fetch(PDO::FETCH_NUM);
                            $kilometrage = 0;
                            if ($e == true) {
                                $liste_kilometrage = $db->query("SELECT * FROM total_kilometrage WHERE id_bus='$id_bus'");
                                foreach ($liste_kilometrage as $item_km) {
                                    $kilometrage = $item_km["kilometrage"];
                                }
                            }
                            ?>
                            <div class="md:col-span-8">
                                <input type="text" id="kilometrage" name="kilometrage" disabled
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg bg-gray-50 text-gray-600"
                                    value="<?php echo $kilometrage ?>">
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                            <a href="<?= url('liste-vehicule') ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                                </svg>
                                Annuler
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 13l4 4L19 7"/>
                                </svg>
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                    <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const matricule = document.querySelector('input[name="matricule"]').value.trim();
        const matriculeInterne = document.querySelector('input[name="matricule_interne"]').value.trim();
        const marque = document.querySelector('input[name="marque"]').value.trim();
        const type = document.querySelector('select[name="type"]').value;
        const station = document.querySelector('select[name="station"]').value;
        const freqVidangeMoteur = document.querySelector('input[name="freq_vidange_moteur"]').value.trim();
        const freqVidangeBoite = document.querySelector('input[name="freq_vidange_boite"]').value.trim();
        const freqVidangePont = document.querySelector('input[name="freq_vidange_pont"]').value.trim();
        const conso = document.querySelector('input[name="conso"]').value.trim();
        const consoHuile = document.querySelector('input[name="conso_huile"]').value.trim();
        const carburantType = document.querySelector('select[name="carburant_type"]').value;
        const huileMoteur = document.querySelector('select[name="huile_moteur"]').value;
        const huileBoite = document.querySelector('select[name="huile_boite_vitesse"]').value;
        const huilePont = document.querySelector('select[name="huile_pont"]').value;

        if (!matriculeInterne || !marque || !type || !station || !freqVidangeMoteur || !freqVidangeBoite || !freqVidangePont || !conso || !consoHuile || !carburantType || !huileMoteur || !huileBoite || !huilePont) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires.');
            return false;
        }

        if (isNaN(conso) || parseFloat(conso) <= 0) {
            e.preventDefault();
            alert('La consommation standard doit être un nombre positif.');
            return false;
        }
        
        if (isNaN(consoHuile) || parseFloat(consoHuile) <= 0) {
            e.preventDefault();
            alert('La consommation standard d\'huile doit être un nombre positif.');
            return false;
        }
        
        if (isNaN(freqVidangeMoteur) || parseFloat(freqVidangeMoteur) <= 0) {
            e.preventDefault();
            alert('La fréquence de vidange moteur doit être un nombre positif.');
            return false;
        }
        
        if (isNaN(freqVidangeBoite) || parseFloat(freqVidangeBoite) <= 0) {
            e.preventDefault();
            alert('La fréquence de vidange boite doit être un nombre positif.');
            return false;
        }
        
        if (isNaN(freqVidangePont) || parseFloat(freqVidangePont) <= 0) {
            e.preventDefault();
            alert('La fréquence de vidange pont doit être un nombre positif.');
            return false;
        }
    });

    // Auto-format matricule fields
    document.querySelector('input[name="matricule"]').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });

    document.querySelector('input[name="matricule_interne"]').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });
});
</script>
</body>
</html>
