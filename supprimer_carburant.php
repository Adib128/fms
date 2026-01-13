<?php
require_once "config.php";
session_start();
$id = $_GET["id"];

$select = $db->query("SELECT * FROM carburant WHERE id_carburant = '$id'");
foreach ($select as $item) {
    $id_doc_carburant = $item["id_doc_carburant"] ;
    $qte_doc = $item["qte_go"] ;
}

$liste = $db->query("SELECT * FROM doc_carburant WHERE id_doc_carburant='$id_doc_carburant'");
foreach ($liste as $row) {
    $id_station = $row["id_station"];
}

$liste = $db->query("SELECT * FROM station WHERE id_station='$id_station'");
foreach ($liste as $row) {
    $qte = $row["qte"];
}
$qte = $qte - $qte_doc;

$sth = $db->prepare('UPDATE station SET qte=:qte WHERE id_station=:id_station');
$sth->bindParam(':qte', $qte);
$sth->bindParam(':id_station', $id_station);
$sth->execute();

$query = $db->query("DELETE FROM carburant WHERE id_carburant='$id'");

$_SESSION["message"] = "L'enrégistrement supprimmer avec succées";
header("Location: consulter_doc_carburant.php?id=".$id_doc_carburant."");        
?>