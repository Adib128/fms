<?php
class Controle {

    public function Vide($sValue) {
        if (!isset($sValue) || empty($sValue)) {
            return true;
        } else {
            return false;
        }
    }

    public function noVide($sValue) {

        if (!isset($sValue) || empty($sValue)) {
            return false;
        } else {
            return true;
        }
    }

    public function verif_file($filename) {
        $allowed = array('docx', 'doc', 'pdf', 'html', 'htm');
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (in_array($ext, $allowed)) {
            return true;
        } else {
            return false;
        }
    }

}

?>
