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
                self::$connection = new PDO('mysql:host=localhost;dbname=energie', 'root', 'YourStrongPassword');
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