<?php
require 'header.php' ?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="max-w-9xl mx-auto px-4 py-1">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Modifier Agence</h1>
                <p class="mt-2 text-sm text-gray-600">Mettre à jour les informations de l'agence</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <form class="space-y-8" enctype="multipart/form-data" method="post" action="#">
                    
                    <?php
                    $id = $_GET["id"];

                    $select = $db->query("SELECT * FROM station WHERE id_station = '$id'");
                    foreach ($select as $item) {
                        ?>
                        
                        <!-- Libellé Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="lib" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Libellé</label>
                            <div class="md:col-span-8">
                                <input type="text" id="lib" name="lib" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["lib"] ?>" placeholder="Entrez le libellé de l'agence">
                            </div>
                        </div>
                        
                        <!-- Quantité Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="qte" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Quantité</label>
                            <div class="md:col-span-8">
                                <input type="number" id="qte" name="qte" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["qte"] ?>" placeholder="Entrez la quantité">
                            </div>
                        </div>
                        
                        <!-- Index Field -->
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-6 items-center">
                            <label for="ind" class="text-sm font-medium text-gray-700 text-right md:col-span-2">Index</label>
                            <div class="md:col-span-8">
                                <input type="number" id="ind" name="ind" required
                                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    value="<?php echo $item["ind"] ?>" placeholder="Entrez l'index">
                            </div>
                        </div>

                        <?php
                        if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                            $lib = $_POST["lib"];
                            $qte = $_POST["qte"];
                            $ind = $_POST["ind"];
                            
                            $sth = $db->prepare('UPDATE station SET lib=:lib, qte=:qte, ind=:ind WHERE id_station=:id_station');
                            $sth->bindParam(':lib', $lib);
                            $sth->bindParam(':qte', $qte);
                            $sth->bindParam(':ind', $ind);
                            $sth->bindParam(':id_station', $id);
                            $sth->execute();

                            setFlashMessage("Succées de modification de agence");
                            echo "<script> window.location.replace('liste_station.php')</script>";
                        }
                        ?>
                        
                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                            <a href="liste_station.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                                </svg>
                                Annuler
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Modifier Agence
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
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
</body>
</html>