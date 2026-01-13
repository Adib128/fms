<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container-fluid">
        <div class="container">
            <h3>
                Total kilomètrage
            </h3>
            <hr>


            <br>
            <table class="table table-striped table-bordered" style="width:100%" id="tab">
                <thead>
                    <tr>
                        <th>Agence</th>
                        <th>Numéro Parc</th>
                        <th>Kilométrage</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $liste = $db->query(
                            "SELECT * FROM total_kilometrage "
                            . "INNER JOIN bus ON  total_kilometrage.id_bus=bus.id_bus ORDER BY id_total_kilometrage DESC"
                    );
                    foreach ($liste as $row) {
                        ?>
                        <tr style="cursor: pointer;">

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
                            <td><?php echo $row["matricule_interne"]; ?></td>
                            <td><?php echo $row["kilometrage"] ."  Km"; ?></td>
                            <td align="center"> <a href="liste_kilometrage_bus.php?id=<?php echo $row["id_bus"] ?>"><img src="img/show.png" width="35" height="35"></a></td>
                            
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>

            </table>
           <br><br><br>
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
<script>
    /*
    jQuery(document).ready(function ($) {
        $(".clickable-row").click(function () {
            window.location = $(this).data("href");
        });
    });
    */
</script>
</body>
</html>