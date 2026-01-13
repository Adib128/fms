<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once 'class/class.php';

$login = $_POST["login"];
$pass = $_POST["pass"];

$utilisateur->setLogin($login);
$utilisateur->setPass($pass);
$e = $utilisateur->login();
$data = array();

if ($e > 0) {
    $data["result"] = 1;
} else {
    $data["result"] = 0;
}

echo json_encode($data);