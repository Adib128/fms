<?php
require 'header.php' ?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Modifier Chauffeur</h1>
                <p class="mt-2 text-sm text-gray-600">Mettre à jour les informations du chauffeur</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <?php
                $id = $_GET["id"];
                $select = $db->query("SELECT * FROM chauffeur WHERE id_chauffeur = '$id'");
                foreach ($select as $item) {
                    ?>
                    <form class="space-y-8" enctype="multipart/form-data" method="post" action="#">
                        
                        <!-- Matricule Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="matricule" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Matricule</label>
                            <div class="md:col-span-8">
                                <input type="text" id="matricule" name="matricule" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["matricule"] ?>">
                            </div>
                        </div>
                        
                        <!-- Nom & Prénom Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="nom_prenom" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Nom & Prénom</label>
                            <div class="md:col-span-8">
                                <input type="text" id="nom_prenom" name="nom_prenom" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["nom_prenom"] ?>">
                            </div>
                        </div>
                        
                        <!-- Agence Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="station" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Agence</label>
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

                        <?php
                        if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                            $matricule = $_POST["matricule"];
                            $nom_prenom = $_POST["nom_prenom"];
                            $station = $_POST["station"];

                            $select = $db->query("SELECT * FROM chauffeur WHERE matricule='$matricule' AND id_chauffeur!='$id'");
                            $e = $select->fetch(PDO::FETCH_NUM);
                            if ($e > 0) {
                                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>Matricule déjà enregistré pour un autre chauffeur</div>";
                            } else {
                                $sth = $db->prepare('UPDATE chauffeur SET matricule=:matricule , nom_prenom=:nom_prenom , id_station=:id_station WHERE id_chauffeur=:id_chauffeur');
                                $sth->bindParam(':id_station', $station);
                                $sth->bindParam(':matricule', $matricule);
                                $sth->bindParam(':nom_prenom', $nom_prenom);
                                $sth->bindParam(':id_chauffeur', $id);
                                $sth->execute();

                                $_SESSION["message"] = "Succées de modification de chauffeur";
                                echo "<script> window.location.replace('" . url('liste-chauffeur') . "')</script>";
                            }
                        }
                        ?>
                        
                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                            <a href="<?= url('liste-chauffeur') ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
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
        const nomPrenom = document.querySelector('input[name="nom_prenom"]').value.trim();
        const station = document.querySelector('select[name="station"]').value;

        if (!matricule || !nomPrenom || !station) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires.');
            return false;
        }
    });

    // Auto-format matricule field
    document.querySelector('input[name="matricule"]').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });
});
</script>
</body>
</html>
