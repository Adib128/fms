<?php
$db = new PDO('mysql:host=localhost;dbname=energie', 'root', '');
$db->exec("SET CHARACTER SET utf8");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);



$liste = $db->query("SELECT * FROM bus ORDER BY bus.id_bus DESC");
foreach ($liste as $row) {
   
}
?>