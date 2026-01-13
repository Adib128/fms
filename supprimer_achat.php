<?php
require_once "config.php";
session_start();
$id = $_GET["id"];
$select = $db->query("SELECT * FROM achat WHERE id_achat ='$id'");
foreach ($select as $item) {
    $id_station = $item["id_station"];
    $qte_achat = $item["qte_achat"];
}
$liste = $db->query("SELECT * FROM station WHERE id_station='$id_station'");
foreach ($liste as $row) {
    $qte_actuel = $row["qte"];
}

$qte_actuel = $qte_actuel - $qte_achat;

$sth = $db->prepare('UPDATE station SET qte=:qte WHERE id_station=:id_station');
$sth->bindParam(':qte', $qte_actuel);
$sth->bindParam(':id_station', $id_station);
$sth->execute();

$query = $db->query("DELETE FROM achat WHERE id_achat='$id'");

$_SESSION["message"] = "L'achat supprimmer avec succées";
header('location:liste_achat.php');
?>