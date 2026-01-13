<?php
require 'header.php';
require_once 'config/constants.php';
?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Modifier Huile</h1>
                <p class="mt-2 text-sm text-gray-600">Mettre à jour les informations de l'huile</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <?php
                $id = $_GET["id"];
                $select = $db->query("SELECT * FROM oil_types WHERE id = '$id'");
                foreach ($select as $item) {
                    ?>
                    <form class="space-y-8" enctype="multipart/form-data" method="post" action="#">
                        
                        <!-- Libellé Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="libelle" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Libellé</label>
                            <div class="md:col-span-8">
                                <input type="text" id="libelle" name="libelle" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["name"] ?>">
                            </div>
                        </div>
                        
                        <!-- Désignation Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="designation" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Désignation</label>
                            <div class="md:col-span-8">
                                <input type="text" id="designation" name="designation" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["designation"] ?>">
                            </div>
                        </div>
                        
                        <!-- Usage Oil Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="usageOil" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Usage</label>
                            <div class="md:col-span-8">
                                <select id="usageOil" name="usageOil" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 tom-select"
                                    data-placeholder="Choisir l'usage" data-search-placeholder="Rechercher un usage" data-icon="oil">
                                    <?php
                                    foreach (HuileType::getOptions() as $value => $label) {
                                        $selected = ($value === $item["usageOil"]) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>';
                                        echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
                                        echo '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <?php
                        if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                            $name = $_POST["libelle"];
                            $designation = $_POST["designation"];
                            $usageOil = $_POST["usageOil"];

                            // Validate that the usageOil is from our enum
                            if (!HuileType::isValid($usageOil)) {
                                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>Usage d'huile invalide</div>";
                            } else {
                                $select = $db->query("SELECT * FROM oil_types WHERE name='$name' AND id!='$id'");
                                $e = $select->fetch(PDO::FETCH_NUM);
                                if ($e > 0) {
                                    echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>Libellé déjà enregistré pour une autre huile</div>";
                                } else {
                                    $sth = $db->prepare('UPDATE oil_types SET name=:name, designation=:designation, usageOil=:usageOil WHERE id=:id');
                                    $sth->bindParam(':name', $name);
                                    $sth->bindParam(':designation', $designation);
                                    $sth->bindParam(':usageOil', $usageOil);
                                    $sth->bindParam(':id', $id);
                                    $sth->execute();

                                    $_SESSION["message"] = "Succées de modification d'huile";
                                    echo "<script> window.location.replace('" . url('liste-huile') . "')</script>";
                                }
                            }
                        }
                        ?>
                        
                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                            <a href="<?= url('liste-huile') ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
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
        const libelle = document.querySelector('input[name="libelle"]').value.trim();
        const designation = document.querySelector('input[name="designation"]').value.trim();
        const usageOil = document.querySelector('select[name="usageOil"]').value;

        if (!libelle || !designation || !usageOil) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires.');
            return false;
        }
    });
});
</script>
</body>
</html>
