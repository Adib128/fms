<?php
require 'header.php' ?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Modifier approvisionnement</h1>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <form class="space-y-8" enctype="multipart/form-data" method="post" action="#">

                    <?php
                    $id = $_GET["id"];

                    $select = $db->query("SELECT * FROM achat WHERE id_achat = '$id'");
                    foreach ($select as $item) {
                        ?>
                        
                        <!-- Agence Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="station" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Agence</label>
                            <div class="md:col-span-8">
                                <select id="station" name="station" readonly
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 tom-select"
                                    data-placeholder="Choisir une agence" data-search-placeholder="Rechercher une agence" data-icon="station">
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
                        
                        <!-- Date Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="date" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Date</label>
                            <div class="md:col-span-8">
                                <input type="date" id="date" name="date" value="<?php echo $item["date"] ?>" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Entrez la date">
                            </div>
                        </div>
                        
                        <!-- Quantité Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="qte" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Quantité</label>
                            <div class="md:col-span-8">
                                <input type="number" id="qte" name="qte" step="0.01" value="<?php echo $item["qte_achat"] ?>" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Entrez la quantité">
                            </div>
                        </div>
                        
                        <!-- Numéro Bon de Livraison Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="num" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Numéro bon de livraison</label>
                            <div class="md:col-span-8">
                                <input type="number" id="num" name="num" value="<?php echo $item["num"] ?>" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Entrez le numéro de bon de livraison">
                            </div>
                        </div>

                        <?php
                        if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                            $id_achat = $item["id_achat"];
                            $id_station = $item["id_station"];
                            $date = $_POST["date"];
                            $qte = $_POST["qte"];
                            $num = $_POST["num"];

                            $sth = $db->prepare('UPDATE achat SET date=:date, qte_achat=:qte_achat, num=:num WHERE id_achat=:id_achat');
                            $sth->bindParam(':date', $date);
                            $sth->bindParam(':qte_achat', $qte);
                            $sth->bindParam(':num', $num);
                            $sth->bindParam(':id_achat', $id_achat);
                            $sth->execute();

                            $liste = $db->query("SELECT * FROM station WHERE id_station='$id_station'");
                            foreach ($liste as $row) {
                                $qte_actuel = $row["qte"];
                            }

                            $last_qte = $item["qte_achat"];

                            $qte_actuel = $qte_actuel - $last_qte;

                            $qte_actuel = $qte_actuel + $qte;

                            $sth = $db->prepare('UPDATE station SET qte=:qte WHERE id_station=:id_station');
                            $sth->bindParam(':qte', $qte_actuel);
                            $sth->bindParam(':id_station', $id_station);
                            $sth->execute();

                            $_SESSION["message"] = "Succées de modification d'achat";
                            echo "<script> window.location.replace('" . url('liste-achat') . "')</script>";
                        }
                        ?>
                        
                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                            <a href="<?= url('liste-achat') ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                                </svg>
                                Annuler
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Modifier Approvisionnement
                            </button>
                        </div>

                        <?php
                    }
                    ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const date = document.querySelector('input[name="date"]').value.trim();
        const qte = document.querySelector('input[name="qte"]').value.trim();
        const num = document.querySelector('input[name="num"]').value.trim();

        if (!date || !qte || !num) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires.');
            return false;
        }

        if (isNaN(qte) || parseFloat(qte) <= 0) {
            e.preventDefault();
            alert('La quantité doit être un nombre positif.');
            return false;
        }

        if (isNaN(num) || parseFloat(num) <= 0) {
            e.preventDefault();
            alert('Le numéro de bon de livraison doit être un nombre positif.');
            return false;
        }
    });
});
</script>
</body>
</html>