<?php
require_once "config.php" ;  

session_start();
$id = $_GET["id"];

// Select kilometrage
$select = $db->query("SELECT * FROM kilometrage WHERE id_kilometrage='$id'");
foreach ($select as $row) {
    $kilometrage = $row["kilometrage"];
    $id_bus = $row["id_bus"];
}

// Select total kilometrage
$select = $db->query("SELECT * FROM total_kilometrage WHERE id_bus='$id_bus'");
foreach ($select as $row) {
    $total_kilometrage = $row["kilometrage"];
}

//Convertion
$total_kilometrage = (int)$total_kilometrage ; 
$kilometrage = (int)$kilometrage ; 

//Modification de valeur de kilomètrage
$total_kilometrage = $total_kilometrage - $kilometrage ; 

//Update Kilomètrage totale
$sth = $db->prepare('UPDATE total_kilometrage SET kilometrage=:total_kilometrage WHERE id_bus=:id_bus');
$sth->bindParam(':total_kilometrage', $total_kilometrage);
$sth->bindParam(':id_bus', $id_bus);
$sth->execute();

$query = $db->query("DELETE  FROM kilometrage WHERE id_kilometrage='$id'");


$_SESSION["message"] = "Succées de suppression de kilomètrage";
header('location:liste_kilometrage.php');
?>