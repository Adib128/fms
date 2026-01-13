<?php
require_once 'BD.php';

class Utilisateur extends BD {

    public $id;
    public $nom;
    public $prenom;
    public $email;
    public $login;
    public $pass;
    public $etat;

    function getId() {
        return $this->id;
    }

    function getNom() {
        return $this->nom;
    }

    function getPrenom() {
        return $this->prenom;
    }

    function getEmail() {
        return $this->email;
    }

    function getLogin() {
        return $this->login;
    }

    function getPass() {
        return $this->pass;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setNom($nom) {
        $this->nom = $nom;
    }

    function setPrenom($prenom) {
        $this->prenom = $prenom;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    function setLogin($login) {
        $this->login = $login;
    }

    function setPass($pass) {
        $this->pass = $pass;
    }

    function getEtat() {
        return $this->etat;
    }

    function setEtat($etat) {
        $this->etat = $etat;
    }

    public function ajouter() {
        $params = array(
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'login' => $this->login,
            'pass' => $this->pass,
            'etat' => $this->etat
        );
        try {
            $req = $this->cnn->prepare('INSERT INTO utilisateur '
                    . 'VALUES(NULL,:nom,:prenom,:email,:login,:pass,:etat)');
            $exec = $req->execute($params);
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
        }
        return true;
    }

    public function liste() {
        try {
            $req = $this->cnn->prepare('SELECT * FROM utilisateur ORDER BY id_ustilisateur DESC');
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
            $req = $this->cnn->prepare('SELECT * FROM utilisateur WHERE id_cour=:id');
            $exec = $req->execute($params);
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
        }

        $tab = $req->fetchAll(PDO::FETCH_ASSOC);
        return $tab;
    }

    public function bloquer() {
        $params = array(
            'id' => $this->id,
            'etat' => $this->etat
        );
        try {
            $req = $this->cnn->prepare('UPDATE utilisateur SET etat=:etat WHERE id_ustilisateur=:id');
            $exec = $req->execute($params);
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
        }
        return true;
    }



}
