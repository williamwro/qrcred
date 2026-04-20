<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
class Banco extends PDO{

    static protected $_instance;
// odin
    private static $host = "216.245.210.4";
    private static $database = "qrcred";
    private static $username = "postgres";
    private static $password = "@Mak&#CARD#2024";

    public function __construct($host,$database,$username,$password){
        return parent::__construct($host,$database,$username,$password);
    }
 // Métodos para obter configurações de conexão (se necessário)
    public static function getHost() {
        return self::$host;
    }
    public static function getDatabase() {
        return self::$database;
    }
    public static function getUserName() {
        return self::$username;
    }
    public static function getPassWord() {
        return self::$password;
    }


    static public function getInstancePostgresql($host,$database,$username,$password){
        if(!isset(self::$_instance)){
            self::$_instance = new PDO("pgsql:dbname=".$database.";host=".$host.";port=5432;user=".$username.";password=".$password);
        }
        return self::$_instance;
    }

    static public function conectar_postgres(){
        $username = "postgres";
        $password = "@Mak&#CARD#2024";
        $host     = "216.245.210.4"; // iphosting
        $database = "qrcred"; 
        if(!isset(self::$_instance)){
            self::$_instance = new PDO("pgsql:dbname=".$database.";host=".$host.";port=5432;user=".$username.";password=".$password);        }
        return self::$_instance;
    }
}