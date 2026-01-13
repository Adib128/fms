<?php
require_once "config.php" ; 

session_start() ;    
$id = $_GET["id"] ; 

$query = $db->query("DELETE  FROM station WHERE id_station='$id'");


$_SESSION["message"] = "Succées de suppression de station";
           header('location:liste_station.php') ; 



?>