<?php
class BD {

    protected static $connection;
    protected $cnn;

    public function __construct() {
        $this->cnn = self::getConnection();
    }

    public static function getConnection() {
        if (!isset(self::$connection)) {
            try {
                self::$connection = new PDO('mysql:host=82.197.82.142;dbname=u734071230_fms', 'u734071230_fms_user', 'DAASCloud1;;');
                self::$connection->exec("SET CHARACTER SET utf8");
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }
        }

        return self::$connection;
    }

}

?>