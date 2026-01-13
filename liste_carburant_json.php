<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$db = new PDO('mysql:host=localhost;dbname=energie', 'root', '');
$db->exec("SET CHARACTER SET utf8");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$tab_json = array();
$liste = $db->query(
        "SELECT * FROM  carburant ORDER BY id_carburant DESC"
);

$i = 0;
foreach ($liste as $row) {

    $id_doc_carburant = $row["id_doc_carburant"];

    $query = $db->query(
            "SELECT * FROM  doc_carburant WHERE id_doc_carburant='$id_doc_carburant'"
    );

    foreach ($query as $it) {
        $num_doc = $it["num_doc_carburant"];
    }
    $tab = array();
    $tab["num_doc"] = $num_doc;

    $date = $row["date"];
    $date = date_create($date);
    $date = date_format($date, "d/m/Y");
    $tab["date"] = $date;

    $id_bus = $row["id_bus"];
    $item = $db->query("SELECT * FROM bus WHERE id_bus='$id_bus'");
    foreach ($item as $cr) {
        $matricule_interne = $cr["matricule_interne"];
    }
    $tab["bus"] = $matricule_interne;

    $tab["heure"] = $row["heure"];

    $tab["qte_go"] = $row["qte_go"];

    $tab["index_km"] = $row["index_km"];

    $id_chauffeur = $row["id_chauffeur"];
    $item = $db->query("SELECT * FROM chauffeur WHERE id_chauffeur='$id_chauffeur'");
    foreach ($item as $cr) {
        $matricule_interne = $cr["nom_prenom"];
    }
    $tab["chauffeur"] = $matricule_interne;

    $tab["id"] = $row["id_carburant"];

    $tab_json[$i] = $tab;
    $i++;
}
$data = array('data' => $tab_json);

echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>