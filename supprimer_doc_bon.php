<?php
require_once "config.php" ; 

session_start();
$id = $_GET["id"];

$query = $db->query("DELETE  FROM doc_bon WHERE id_doc_bon='$id'");


$_SESSION["message"] = "Suppression avec succées";
header('location:liste_doc_bon.php');
?>