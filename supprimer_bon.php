<?php
require_once "config.php" ; 
session_start();
$id = $_GET["id"];

$query = $db->query("DELETE FROM bon WHERE id_bon='$id'");

$_SESSION["message"] = "L'enrégistrement supprimmer avec succées";
header('location:liste_bon.php');
?>