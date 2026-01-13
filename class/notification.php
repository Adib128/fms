<?php
require_once 'BD.php';

class Notification extends BD {

    public $id;
    public $date;
    public $nom;
    public $contenue;
    public $fichier;

    function getId() {
        return $this->id;
    }

    function getDate() {
        return $this->date;
    }

    function getNom() {
        return $this->nom;
    }

    function getContenue() {
        return $this->contenue;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setDate($date) {
        $this->date = $date;
    }

    function setNom($nom) {
        $this->nom = $nom;
    }

    function setContenue($contenue) {
        $this->contenue = $contenue;
    }

    function getFichier() {
        return $this->fichier;
    }

    function setFichier($fichier) {
        $this->fichier = $fichier;
    }

    public function ajouter() {
        $params = array(
            'date' => $this->date,
            'nom' => $this->nom,
            'contenue' => $this->contenue
        );
        try {
            $req = $this->cnn->prepare('INSERT INTO notification '
                    . 'VALUES(NULL,:date,:nom,:contenue)');
            $exec = $req->execute($params);
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
        }
        return true;
    }

    public function modifier() {
        $params = array(
            'id' => $this->id,
            'date' => $this->date,
            'nom' => $this->nom,
            'contenue' => $this->contenue
        );
        try {
            $req = $this->cnn->prepare('UPDATE notification SET '
                    . 'date=:date,nom=:nom,contenue=:contenue where id_notification=:id');
            $exec = $req->execute($params);
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
        }
        return true;
    }

    public function liste() {
        try {
            $req = $this->cnn->prepare('SELECT * FROM notification ORDER BY id_notification DESC');
            $exec = $req->execute();
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
        }

        $tab = $req->fetchAll(PDO::FETCH_ASSOC);
        return $tab;
    }

    public function consulter() {
        $params = array(
            'id' => $this->id
        );
        try {
            $req = $this->cnn->prepare('SELECT * FROM notification WHERE id_notification=:id');
            $exec = $req->execute($params);
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
        }

        $tab = $req->fetchAll(PDO::FETCH_ASSOC);
        return $tab;
    }

    public function supprimer() {
        $params = array(
            'id' => $this->id
        );
        try {
            $req = $this->cnn->prepare('DELETE FROM notification WHERE id_notification=:id');
            $exec = $req->execute($params);
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
        }
        return true;
    }

}
