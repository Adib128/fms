<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$db = new PDO('mysql:host=localhost;dbname=energie', 'root', '');
$db->exec("SET CHARACTER SET utf8");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

require_once __DIR__ . '/app/helpers.php';
$tab_json = array();
$liste = $db->query(
        "SELECT * FROM  bon ORDER BY id_bon DESC"
);
$i = 0;
foreach ($liste as $row) {
    $tab = array();
    $tab["num"] = $row["num"];

    $id_bus = $row["id_bus"];
    $item = $db->query("SELECT * FROM bus WHERE id_bus='$id_bus'");
    foreach ($item as $cr) {
        $matricule_interne = $cr["matricule_interne"];
    }

    $tab["bus"] = $matricule_interne;
    
    $date = $row["date"];
    $date = date_create($date);
    $date = date_format($date, "d/m/Y");
    $tab["date"] = $date;

    $tab["heure"] = $row["heure"];
    
    $tab["qte_go"] = formatQuantity($row["qte_go"]);
    
    $tab["type"] = $row["type_co"];

    $tab["index_km"] = $row["index_km"];
   
    $tab["id"] = $row["id_bon"];

    $tab_json[$i] = $tab;
    $i++;
}
$data = array('data' => $tab_json);

echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>