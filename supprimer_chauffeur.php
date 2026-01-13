<?php
require_once "config.php" ; 

session_start() ;    
$id = $_GET["id"] ;  

$query = $db->query("DELETE  FROM chauffeur WHERE id_chauffeur='$id'");

$_SESSION["message"] = "Succées de suppression de chauffeur";
           header('location:liste_chauffeur.php') ; 



?>