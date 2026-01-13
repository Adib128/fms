<?php
require_once 'BD.php';

class Admin extends BD {

    public $id;
    public $login;
    public $pass;
    public $profile;

    function getId() {
        return $this->id;
    }

    function getLogin() {
        return $this->login;
    }

    function getPass() {
        return $this->pass;
    }

    function getProfile() {
        return $this->profile;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setLogin($login) {
        $this->login = $login;
    }

    function setPass($pass) {
        $this->pass = $pass;
    }

    function setProfile($profile) {
        $this->profile = $profile;
    }

    public function login() {
        $params = array(
            'login' => $this->login,
            'pass' => $this->pass
        );
        $select = $this->cnn->prepare("SELECT * FROM admin WHERE login=:login AND pass=:pass");
        $select->execute($params);
        $e = $select->fetch(PDO::FETCH_ASSOC);
        return $e;
    }

    public function ancien_pass() {
        try {
            $select = $this->cnn->prepare("SELECT pass FROM admin WHERE id_admin = :id");
            $select->execute(['id' => $this->id]);
            $result = $select->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['pass'] : null;
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
            return null;
        }
    }

    public function changer() {
        $params = array(
            'pass' => $this->pass,
            'id' => $this->id
        );
        try {
            $select = $this->cnn->prepare("UPDATE admin SET pass=:pass WHERE id_admin=:id");
            $select->execute($params);
            return true;
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
            return false;
        }
    }

    public function getAllUsers() {
        try {
            $select = $this->cnn->prepare("SELECT id_admin, login, profile FROM admin ORDER BY login");
            $select->execute();
            return $select->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
            return [];
        }
    }

    public function getUserById($id) {
        try {
            $select = $this->cnn->prepare("SELECT id_admin, login, profile FROM admin WHERE id_admin=:id");
            $select->execute(['id' => $id]);
            return $select->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
            return null;
        }
    }

    public function addUser() {
        try {
            $select = $this->cnn->prepare("INSERT INTO admin (login, pass, profile) VALUES (:login, :pass, :profile)");
            $params = array(
                'login' => $this->login,
                'pass' => $this->pass,
                'profile' => $this->profile
            );
            $select->execute($params);
            return true;
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
            return false;
        }
    }

    public function updateUser($id) {
        try {
            $sql = "UPDATE admin SET login=:login, profile=:profile";
            $params = array(
                'login' => $this->login,
                'profile' => $this->profile,
                'id' => $id
            );
            
            // Only update password if provided
            if (!empty($this->pass)) {
                $sql .= ", pass=:pass";
                $params['pass'] = $this->pass;
            }
            
            $sql .= " WHERE id_admin=:id";
            $select = $this->cnn->prepare($sql);
            $select->execute($params);
            return true;
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
            return false;
        }
    }

    public function deleteUser($id) {
        try {
            // Prevent deletion of the current user
            if ($id == $_SESSION['user_id']) {
                return false;
            }
            
            $select = $this->cnn->prepare("DELETE FROM admin WHERE id_admin=:id");
            $select->execute(['id' => $id]);
            return true;
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
            return false;
        }
    }

    public function isLoginExists($login, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM admin WHERE login=:login";
            $params = ['login' => $login];
            
            if ($excludeId) {
                $sql .= " AND id_admin != :excludeId";
                $params['excludeId'] = $excludeId;
            }
            
            $select = $this->cnn->prepare($sql);
            $select->execute($params);
            $result = $select->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $ex) {
            echo "Erreur :" . $ex->getMessage();
            return false;
        }
    }

}
