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
                <h1 class="text-3xl font-bold text-gray-900">Enrégistrer Carburant</h1>
                <p class="mt-2 text-sm text-gray-600">Ajouter une ligne de consommation de carburant</p>
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


            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <form class="space-y-8" enctype="multipart/form-data" method="post" action="#">
                    
                    <!-- Document Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="document" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Document</label>
                        <div class="md:col-span-8">
                            <select id="document" name="id_doc_carburant" disabled
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                <?php
                                $id = $_GET["id"];
                                $liste_station = $db->query("SELECT * FROM doc_carburant ORDER BY id_doc_carburant DESC");
                                foreach ($liste_station as $row) {
                                    $originalDate = $row["date"];
                                    $newDate = date("d/m/Y", strtotime($originalDate));
                                    if ($row["id_doc_carburant"] == $id) {
                                        $type = $row["type"];
                                        echo '<option value="' . $row["id_doc_carburant"] . '">';
                                        echo $row["num_doc_carburant"] . " : " . $newDate;
                                        echo '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Véhicule Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="bus" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Véhicule</label>
                        <div class="md:col-span-8">
                            <select id="bus" name="bus" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="Choisir le véhicule">
                                <option value="">Choisir le véhicule</option>
                                <?php
                                $buses = $db->query("SELECT id_bus, matricule_interne, carburant_type FROM bus ORDER BY matricule_interne ASC")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($buses as $row) {
                                    echo '<option value="' . $row["id_bus"] . '">';
                                    echo $row["matricule_interne"];
                                    echo '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <!-- Numéro Field (Carte only) -->
                    <?php
                    if ($type == 'Carte') {
                        ?>
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="ref" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Numéro</label>
                            <div class="md:col-span-8">
                                <input type="number" id="ref" name="ref"
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Entrez le numéro">
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                    <!-- Quantité GO Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="qte_go" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Qté GO</label>
                        <div class="md:col-span-8">
                            <input type="number" id="qte_go" name="qte_go" step="0.01" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="Entrez la quantité en litres">
                        </div>
                    </div>

                    <!-- Index Km Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="index_km" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Index Km</label>
                        <div class="md:col-span-8">
                            <input type="number" id="index_km" name="index_km"
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="Entrez l'index kilométrique">
                        </div>
                    </div>

                    <!-- Heure Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="heure" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Heure de Rav</label>
                        <div class="md:col-span-8">
                            <input type="time" id="heure" name="heure" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        </div>
                    </div>



                    <!-- Chauffeur Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="chauffeur" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Chauffeur</label>
                        <div class="md:col-span-8">
                            <select id="chauffeur" name="chauffeur" required
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="Choisir le chauffeur">
                                <option value="">Choisir le chauffeur</option>
                                <?php
                                $liste_chauffeur = $db->query("SELECT * FROM chauffeur ORDER BY nom_prenom ASC");
                                foreach ($liste_chauffeur as $row) {
                                    echo '<option value="' . $row["id_chauffeur"] . '">';
                                    echo $row["matricule"] . " : " . $row["nom_prenom"];
                                    echo '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>


                    <!-- Type Field -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                        <label for="type" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Type</label>
                        <div class="md:col-span-8">
                            <?php
                            if ($type == 'Carte') {
                                ?>
                                <select id="type" name="type" required
                                    class="w-20 px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                    <option value="GO">GO</option>
                                    <option value="GSS">GSS</option>
                                    <option value="ESP">ESP</option>
                                </select>
                                <?php
                            } else {
                                ?>
                                <select id="type" name="type" disabled
                                    class="w-20 px-4 py-3 text-lg border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed transition duration-200">
                                    <option value="GO">GO</option>
                                </select>
                                <?php
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="/consulter-doc-carburant?id=<?= $id ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 12H5M12 19l-7-7 7-7"/>
                            </svg>
                            Annuler
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Enrégistrer Carburant
                        </button>
                    </div>
                </form>
            </div>

                    <?php
                    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                        $id_doc_carburant = $id;
                        $select = $db->query("SELECT * FROM doc_carburant WHERE id_doc_carburant = '$id_doc_carburant'");
                        foreach ($select as $item) {
                            $dateDoc = $item["date"];
                        }
                        $bus = $_POST["bus"];
                        $chauffeur = $_POST["chauffeur"];
                        $date = $dateDoc;
                        $heure = $_POST["heure"];
                        $date_saisie = date('Y-m-d H:i:s');
                        $qte_go = $_POST["qte_go"];
                        $index_km = $_POST["index_km"];

                        if ($type == 'Carte') {
                            $ref = $_POST["ref"];
                            $type = $_POST["type"];
                        } else {
                            $ref = 0;
                            $type = 'GO';
                        }

                        $sth = $db->prepare('INSERT INTO carburant VALUES(NULL, :type , :ref ,:date_saisie,:qte_go,:index_km,:date,:heure,:bus,:chauffeur,:id_doc_carburant)');
                        $sth->bindParam(':type', $type);
                        $sth->bindParam(':ref', $ref);
                        $sth->bindParam(':date_saisie', $date_saisie);
                        $sth->bindParam(':qte_go', $qte_go);
                        $sth->bindParam(':index_km', $index_km);
                        $sth->bindParam(':heure', $heure);
                        $sth->bindParam(':date', $date);
                        $sth->bindParam(':bus', $bus);
                        $sth->bindParam(':chauffeur', $chauffeur);
                        $sth->bindParam(':id_doc_carburant', $id_doc_carburant);
                        $sth->execute();

                        setFlashMessage("Enrégistrement avec succées");
                        echo "<script> window.location.replace('/consulter-doc-carburant?id=" . $id . "')</script>";
                    }
                    ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Expose buses data to JS
        const busesData = <?= json_encode(array_column($buses, 'carburant_type', 'id_bus')); ?>;
        
        const busSelect = document.getElementById('bus');
        const typeSelect = document.getElementById('type');
        const chauffeurSelect = document.getElementById('chauffeur');

        if (typeof Choices !== 'undefined') {
            new Choices(busSelect, { searchEnabled: true, itemSelectText: '', shouldSort: false });
            new Choices(chauffeurSelect, { searchEnabled: true, itemSelectText: '', shouldSort: false });
        }

        busSelect.addEventListener('change', function() {
            const val = this.value;
            if (typeSelect) {
                typeSelect.value = busesData[val] || 'GO';
            }
        });

        // Also handle Choices.js event
        busSelect.addEventListener('addItem', function(e) {
            if (typeSelect) {
                typeSelect.value = busesData[e.detail.value] || 'GO';
            }
        });
    });
</script>