<?php
//ini_set('display_errors', true);
//error_reporting(E_ALL);
/* if($conexao=pg_connect("host=34.95.183.208 dbname=qrcred port=5432 user=postgres password=Abc25149076")){
    pg_close ($conexao);
    print "Conexão OK!"; 
} else {
    print "Não foi possível estabelecer uma conexão com o banco de dados.";
} */

echo extension_loaded('pgsql');


/*class Banco extends PDO{

    static protected $_instance;

    public function __construct($host,$database,$username,$password){
        return parent::__construct($host,$database,$username,$password);
    }


    static public function getInstancePostgresql($host,$database,$username,$password){
        if(!isset(self::$_instance)){
            self::$_instance = new PDO("pgsql:dbname=".$database.";host=".$host.";port=5432;user=".$username.";password=".$password);
        }
        return self::$_instance;
    }
*/
    //static public function conectar_postgres(){
        //$username = "postgres";
        //$password = "Abc25149076";
        //$host     = "34.95.183.208"; // google cloud
        //$host     = "74.63.238.118"; // iphosting
        //$database = "makecard";
        //$schema   = "sind";
        //if(!isset(self::$_instance)){
            //self::$_instance = new PDO("pgsql:dbname=".$database.";host=".$host.";port=5432;user=".$username.";password=".$password);        }
        //$wpdb2 = new PDO('pgsql:host='.$host.';port=5432;dbname='.$database.';user='.$username.';password='.$password);
        //return self::$_instance;
    //}
// $username = 'your_db_user';
// $password = 'yoursupersecretpassword';
// $schema = 'your_db_name';
// $cloud_sql_connection_name = 'Your Cloud SQL Connection name';

   // if ($cloud_sql_connection_name) {
        // Connect using UNIX sockets
   //     $dsn = sprintf(
   //         'pgsql:dbname=%s;host=/cloudsql/%s',
  //          $schema,
  //          $cloud_sql_connection_name
  //      );
  //  } else {
        // Connect using TCP
//echo phpinfo();
    /*     $username = "postgres";
        $password = "Abc25149076";
        $host     = "34.95.183.208"; // google cloud
        $host     = "74.63.238.118"; // iphosting
        $database = "makecard";
        //$hostname = '34.95.183.208';
        $dsn = sprintf("pgsql:dbname=".$database.";host=".$host.";port=5432;user=".$username.";password=".$password);
        $cnn = new  PDO($dsn, $username, $password);
  //  }
    if($cnn){
        echo "conectou.";
    }else{
        echo "falhou.";
    }
 */