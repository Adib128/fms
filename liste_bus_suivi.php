<?php
require 'header.php' ?>
<div id="page-wrapper">

    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h3>
                    Suivi kilomètrage 
                </h3>
                <hr>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-lg-12">
            	
            	<?php 
            	
            	$today = date('Y-m-d'); 
            	
            
						       	
            	?>
            	
            	<div class="row">
            	  <div class="col-lg-12">
            	  		<form class="form-horizontal" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">

                <div class="form-group">
                    <label for="firstname" class="col-sm-1 control-label">De : </label>
                    <div class="col-sm-4">
                        <input type="date" name="date_de" class="form-control" required>
                    </div>
                    <label for="firstname" class="col-sm-1 control-label">À : </label>
                    <div class="col-sm-4">
                        <input type="date" name="date_a" class="form-control"  required>
                    </div>
                    
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-primary"> Chercher </button>
                    </div>
                </div>
                </form>
            	  </div>
            	</div>
            	
            	<?php
            		
                    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                    	$date_de = $_POST["date_de"] ; 
                     $date_a = $_POST["date_a"] ;
                     ?>
                     
                     Total kilomètrage par Véhicule &nbsp; &nbsp; De : <strong> <?php echo date("d/m/Y", strtotime($date_de));  ?>  </strong> 
            		&nbsp; &nbsp; 
            		  À :   <strong> <?php echo date("d/m/Y", strtotime($date_a));  ?>  </strong>
            		  <?php
                    }else{
                    	 $date_de = $today; 
                      $date_a = $today;
                      ?>
                       Total kilomètrage par Véhicule  &nbsp; jusqu'a : <strong> <?php echo date("d/m/Y", strtotime($date_de));  ?>  </strong> 
                      <?php
                    }
                    ?>
          
            		
            
            	<br><br>
               
                
                
            	<table class="table table-striped table-bordered" style="width:100%" id="tab">
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Type</th>
                        <th>Fré. Vidange</th>
                        <th>Agence</th>
                        <th>Type Carburant</th>
                        <th>Kilométrage</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    
                    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
                    	$date_de = $_POST["date_de"] ; 
                     $date_a = $_POST["date_a"] ;
                     $is_searched = 1;
                    }else{
                    	 $date_de = $today; 
                      $date_a = $today;
                      $is_searched = 0 ;
                    }
                    
                    $liste = $db->query("SELECT * FROM bus ORDER BY bus.id_bus DESC");
                    foreach ($liste as $row) {
                        ?>
                        <tr>
                            <td><?php echo $row["matricule_interne"]; ?></td>
                            <td><?php echo $row["type"]; ?></td>
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
                            <td>
                                <?php echo $row["carburant_type"]; ?>
                            </td>
                            <td>
                                <?php
                                $id_bus = $row["id_bus"];
                                $params = array(
                                    'id_bus' => $id_bus
                                );
                                $select = $db->prepare("SELECT * FROM total_kilometrage WHERE id_bus=:id_bus");
                                $select->execute($params);
                                $e = $select->fetch(PDO::FETCH_NUM);

                                if ($e == true) {
                                	   if($is_searched == 0){
                                	   	$liste_kilometrage = $db->query("SELECT * FROM kilometrage WHERE id_bus='$id_bus'");
                                	   }else{
                                	   	$liste_kilometrage = $db->query("SELECT * FROM kilometrage WHERE id_bus='$id_bus' AND  date_kilometrage >= '$date_de' AND date_kilometrage <= '$date_a'  ");
                                	   }
                                    $kilometrage = 0 ; 
                                    foreach ($liste_kilometrage as $item) {
                                        $kilometrage = $kilometrage + $item["kilometrage"];
                                    }
                                    echo $kilometrage . " Km";
                                }
                                ?>
                            </td>
                               <td align="center"> 
                                   <a href="liste_kilometrage_bus.php?id=<?php echo $row["id_bus"] ?>" class="btn-info py-1 px-2" title="Historique">
                                       <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                           <circle cx="12" cy="12" r="10"></circle>
                                           <polyline points="12 6 12 12 16 14"></polyline>
                                       </svg>
                                   </a> 
                               </td>
                        </tr>
                        <?php
                    }
                    ?>
                      
                </tbody>
            </table>
            
            <br><br>
      
				</div>
		   </div>
    </div>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
    $(document).ready(function () {
    var table = $('#tab').DataTable({
        /*"ordering": false,*/
        "bSort" : false,
        "language": {
            "sProcessing": "Traitement en cours...",
            "sSearch": "Saisir numéro de bus&nbsp;: ",
            "sLengthMenu": "Afficher _MENU_ &eacute;l&eacute;ments",
            "sInfo": "Affichage de _TOTAL_ &eacute;l&eacute;ments<br><br>",
            "sInfoEmpty": "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ment",
            "sInfoFiltered": "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
            "sInfoPostFix": "",
            "sLoadingRecords": "Chargement en cours...",
            "sZeroRecords": "Aucun &eacute;l&eacute;ment &agrave; afficher",
            "sEmptyTable": "Aucune donn&eacute;e disponible dans le tableau",
            "oPaginate": {
                "sFirst": "Premier",
                "sPrevious": "Pr&eacute;c&eacute;dent",
                "sNext": "Suivant",
                "sLast": "Dernier"
            },
            "oAria": {
                "sSortAscending": ": activer pour trier la colonne par ordre croissant",
                "sSortDescending": ": activer pour trier la colonne par ordre d&eacute;croissant"
            },
            "select": {
                "rows": {
                    _: "%d lignes séléctionnées",
                    0: "Aucune ligne séléctionnée",
                    1: "1 ligne séléctionnée"
                }

            }
        },
        "paging":   false,
        "info":     true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'print',
                title: '' ,
                text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Imprimer',
                className: 'btn btn-indigo btn-sm',
                exportOptions: { columns: ':visible:not(.text-right)' }
            },
            {
                extend: 'excelHtml5',
                title: '' ,
                text: '<svg class="h-4 w-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg> Excel' ,
                className: 'btn btn-emerald btn-sm',
                exportOptions: { columns: ':visible:not(.text-right)' }
            },
            {
                extend: 'pdfHtml5',
                title: '' ,
                text: 'Exporter PDF' ,
                exportOptions: { columns: ':visible:not(.text-right)' }
            }
        ],
        "columnDefs": [
    			{ "searchable": false, "targets": 1 },
    			{ "searchable": false, "targets": 2 },
    			{ "searchable": false, "targets": 3 }
    			
  			]
    });
 });

</script>
</body>
</html>