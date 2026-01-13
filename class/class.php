<?php
require_once 'BD.php' ; ;
require_once 'ctrl.php' ;
require_once 'notification.php' ;
require_once 'utilisateur.php' ;
require_once 'admin.php' ;

$db = new BD() ; 
$ctrl = new Controle() ; 
$notification = new Notification() ; 
$utilisateur = new Utilisateur() ;
$admin = new Admin() ;