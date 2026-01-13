<?php
require_once "config.php";
session_start();
$id = $_GET["id"];



$liste = $db->query("SELECT * FROM doc_carburant WHERE id_doc_carburant='$id'");
foreach ($liste as $row) {
    $id_station = $row["id_station"];
    $index_debut = $row["index_debut"];
}

$liste = $db->query("SELECT * FROM carburant WHERE id_doc_carburant='$id'");
$qte_total_document = 0;
foreach ($liste as $row) {
    $qte_total_document = $qte_total_document + $row["qte_go"];
}

$liste = $db->query("SELECT * FROM station WHERE id_station='$id_station'");
foreach ($liste as $row) {
    $qte = $row["qte"];
}
$qte = $qte - $qte_total_document;

$query = $db->query("DELETE  FROM carburant WHERE id_doc_carburant='$id'");

$query = $db->query("DELETE  FROM doc_carburant WHERE id_doc_carburant='$id'");

$sth = $db->prepare('UPDATE station SET qte=:qte WHERE id_station=:id_station');
$sth->bindParam(':qte', $qte);
$sth->bindParam(':id_station', $id_station);
$sth->execute();

$_SESSION["message"] = "Suppression avec succées";
header('location:liste_doc_carburant.php');
?>