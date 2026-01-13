<?php
// Only include header.php if not already routed
if (!defined('ROUTED')) {
    require 'header.php';
} ?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Modifier Carburant</h1>
                <p class="mt-2 text-sm text-gray-600">Modifier les informations de consommation de carburant</p>
            </div>

            <!-- Flash Message -->
            <?php if (hasFlashMessage()) : ?>
                <div class="alert-success flex items-start gap-3 mb-6">
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

            <?php
            $id = $_GET["id"];

            $select = $db->query("SELECT * FROM carburant WHERE id_carburant = '$id'");
            foreach ($select as $item) {
                ?>
                <!-- Form Card -->
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <form class="space-y-8" enctype="multipart/form-data" method="post" action="#">
                        
                        <?php
                        $id_doc_carburant = $item["id_doc_carburant"];
                        $liste_doc = $db->query("SELECT * FROM doc_carburant WHERE id_doc_carburant='$id_doc_carburant'");
                        foreach ($liste_doc as $row) {
                            $type = $row["type"];
                        }
                        ?>

                        <!-- Type Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="type" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Type</label>
                            <div class="md:col-span-8">
                                <?php
                                if ($type == 'Carte') {
                                    ?>
                                    <select id="type" name="type" required
                                        class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                        <?php
                                        $tab = array(
                                            'GO',
                                            'GSS',
                                            'ESP'
                                        );
                                        foreach ($tab as $cls) {
                                            if ($cls == $item["type"]) {
                                                echo '<option selected value="' . $cls . '">' . $cls . '</option>';
                                            } else {
                                                echo '<option value="' . $cls . '">' . $cls . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <?php
                                } else {
                                    ?>
                                    <select id="type" name="type" disabled
                                        class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed transition duration-200">
                                        <option value="GO">GO</option>
                                    </select>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Numéro Field (Carte only) -->
                        <?php
                        if ($type == 'Carte') {
                            ?>
                            <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                                <label for="ref" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Numéro</label>
                                <div class="md:col-span-8">
                                    <input type="number" id="ref" name="ref" value="<?php echo $item["ref"] ?>"
                                        class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                        placeholder="Entrez le numéro">
                                </div>
                            </div>
                            <?php
                        }
                        ?>

                        <!-- Hidden ID Field -->
                        <input type="hidden" name="id_doc_carburant" value="<?php echo $item["id_doc_carburant"] ?>">

                        <!-- Véhicule Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="bus" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Véhicule</label>
                            <div class="md:col-span-8">
                                <select id="bus" name="bus" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Choisir le véhicule">
                                    <?php
                                    $liste_bus = $db->query("SELECT * FROM bus ORDER BY id_bus DESC");
                                    foreach ($liste_bus as $row) {
                                        if ($row["id_bus"] == $item["id_bus"]) {
                                            echo '<option selected value="' . $row["id_bus"] . '">';
                                            echo $row["matricule_interne"] . " : " . $row["type"];
                                            echo '</option>';
                                        } else {
                                            echo '<option value="' . $row["id_bus"] . '">';
                                            echo $row["matricule_interne"] . " : " . $row["type"];
                                            echo '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Chauffeur Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="chauffeur" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Chauffeur</label>
                            <div class="md:col-span-8">
                                <select id="chauffeur" name="chauffeur" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Choisir le chauffeur">
                                    <?php
                                    $liste_station = $db->query("SELECT * FROM chauffeur ORDER BY id_chauffeur DESC");
                                    foreach ($liste_station as $row) {
                                        if ($row["id_chauffeur"] == $item["id_chauffeur"]) {
                                            echo '<option selected value="' . $row["id_chauffeur"] . '">';
                                            echo $row["matricule"] . " : " . $row["nom_prenom"];
                                            echo '</option>';
                                        } else {
                                            echo '<option value="' . $row["id_chauffeur"] . '">';
                                            echo $row["matricule"] . " : " . $row["nom_prenom"];
                                            echo '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Date Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="date" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Date de Rav</label>
                            <div class="md:col-span-8">
                                <input type="date" id="date" name="date" value="<?php echo $item["date"] ?>" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                            </div>
                        </div>

                        <!-- Heure Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="heure" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Heure de Rav</label>
                            <div class="md:col-span-8">
                                <input type="time" id="heure" name="heure" value="<?php echo $item["heure"] ?>" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                            </div>
                        </div>

                        <!-- Quantité GO Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="qte_go" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Qté GO</label>
                            <div class="md:col-span-8">
                                <input type="number" id="qte_go" name="qte_go" step="0.01" value="<?php echo (float)$item["qte_go"] ?>" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Entrez la quantité en litres">
                            </div>
                        </div>

                        <!-- Index Km Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="index_km" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Index Km</label>
                            <div class="md:col-span-8">
                                <input type="number" id="index_km" name="index_km" value="<?php echo $item["index_km"] ?>"
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Entrez l'index kilométrique">
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                            <a href="/consulter-doc-carburant?id=<?= $id_doc_carburant ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                                </svg>
                                Annuler
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                </svg>
                                Modifier Carburant
                            </button>
                        </div>
                    </form>
                </div>

                <?php
                if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                    $id_doc_carburant = $_POST["id_doc_carburant"];
                    $bus = $_POST["bus"];
                    $chauffeur = $_POST["chauffeur"];
                    $heure = $_POST["heure"];
                    $date = $_POST["date"];
                    $qte_go = $_POST["qte_go"];
                    $index_km = $_POST["index_km"];

                    if ($type == 'Carte') {
                        $ref = $_POST["ref"];
                        $type = $_POST["type"];
                    } else {
                        $ref = 0;
                        $type = 'GO';
                    }

                    $sth = $db->prepare('UPDATE carburant SET id_bus=:id_bus, ref=:ref , type=:type, id_chauffeur=:id_chauffeur , heure=:heure , date=:date ,  qte_go=:qte_go , index_km=:index_km  WHERE id_carburant=:id_carburant');
                    $sth->bindParam(':ref', $ref);
                    $sth->bindParam(':type', $type);
                    $sth->bindParam(':id_bus', $bus);
                    $sth->bindParam(':id_chauffeur', $chauffeur);
                    $sth->bindParam(':heure', $heure);
                    $sth->bindParam(':date', $date);
                    $sth->bindParam(':qte_go', $qte_go);
                    $sth->bindParam(':index_km', $index_km);
                    $sth->bindParam(':id_carburant', $id);
                    $sth->execute();

                    setFlashMessage("Modification avec succées");
                    echo "<script> window.location.replace('/consulter-doc-carburant?id=" . $id_doc_carburant . "')</script>";
                }
                ?>
            <?php } ?>
        </div>
    </div>
</div>