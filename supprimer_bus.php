<?php
require_once "config.php" ; 
require_once __DIR__ . '/app/helpers.php';
session_start();
$id = $_GET["id"];

$query = $db->query("DELETE FROM total_kilometrage WHERE id_bus='$id'");

$query = $db->query("DELETE FROM bus WHERE id_bus='$id'");

$_SESSION["message"] = "Succées de suppression de bus";
header('Location: ' . url('liste-vehicule'));
exit;
?>