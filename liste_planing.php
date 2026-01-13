<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Planing vidange
            </h3>
            <hr>
            <br>
            <!--
            <a href="ajouter_bus.php" class="btn btn-default">Ajouter bus</a>
            <br> 
            <?php
            if (isset($_SESSION["message"])) {
                ?>
                <br>
                <div class='alert alert-success alert-dismissable'>
                    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times; </button>
                    <?php
                    echo $_SESSION["message"];
                    session_destroy();
                    ?>
                </div>



            <?php } ?>
            <br>
            
            <table class="table table-striped table-bordered" style="width:100%" id="tab">
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Numéro Parc</th>
                        <th>Type</th>
                        <th>Marque</th>
                        <th>Kilo Vidange</th>
                        <th>Agence</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $liste = $db->query("SELECT * FROM bus ORDER BY id_bus DESC");
                    foreach ($liste as $row) {
                        ?>
                        <tr>
                            <td><?php echo $row["matricule"]; ?></td>
                            <td><?php echo $row["matricule_interne"]; ?></td>
                            <td><?php echo $row["type"]; ?></td>
                            <td><?php echo $row["marque"]; ?></td>
                            <td><?php echo $row["vidange"]; ?></td>
                            <td>
                                <?php
                                $id_station = $row["id_station"];
                                $item = $db->query("SELECT * FROM station WHERE id_station='$id_station'");
                                foreach ($item as $cr) {
                                    $lib = $cr["lib"];
                                }
                                echo $lib;
                                ?>
                            </td>
                            <td align="center">
                                <a
                                    href="modifier_bus.php?id=<?php echo $row["id_bus"] ?>"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-brand-600"
                                    aria-label="Modifier le bus"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m16.862 3.487 3.651 3.651m-2.036-1.615-9.193 9.193a4.5 4.5 0 0 1-1.984 1.144l-3.18.795.795-3.18a4.5 4.5 0 0 1 1.144-1.984l9.193-9.193a1.5 1.5 0 0 1 2.09 0Z" />
                                    </svg>
                                </a>
                            </td>
                            <td align="center">
                                <a
                                    href="supprimer_bus.php?id=<?php echo $row["id_bus"] ?>"
                                    onclick="return confirm('Voulez vous vraiment supprimer le bus ?');"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-red-200 bg-white text-red-600 shadow-sm transition hover:bg-red-50"
                                    aria-label="Supprimer le bus"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 7v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7" />
                                        <path d="M9 7V5a3 3 0 0 1 3-3 3 3 0 0 1 3 3v2" />
                                        <path d="M4 7h16" />
                                    </svg>
                                </a>
                            </td> 

                        </tr>
                        <?php
                    }
                    ?>
                </tbody>

            </table>
            -->
            <br><br>
        </div>


    </div>

</div>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="js/data-script.js"></script>
</body>
</html>