<?PHP
    ini_set('display_errors', true);
    error_reporting(E_ALL);
   
    include "../../php/banco.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $someArray = array();
    $i=1;
    $_codigo_convenio = (int)$_POST['codigo_convenio'];

    $sql = "SELECT nomefantasia FROM sind.convenio WHERE codigo = :codigo_convenio";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':codigo_convenio',  $_codigo_convenio, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll();
    foreach ($result as $row){
        $_nome_fantasia = $row['nomefantasia'];
    }


    $sql2 = "SELECT * FROM sind.banco_convenio WHERE cod_convenio=".$_codigo_convenio;
    $statment = $pdo->prepare($sql2);

    $statment->execute();

    $result = $statment->fetchAll();

    $sub_array = array();

    $sub_array["nome_fantasia"] = $_nome_fantasia;
    $sub_array["cod_convenio"]  = $_codigo_convenio;

    foreach ($result as $row){

        $sub_array["id"]            = $row["id"];
        $sub_array["cod_banco"]     = $row["cod_banco"];
        $sub_array["agencia"]       = $row["agencia"];
        $sub_array["conta"]         = $row["conta"];
        $sub_array["cod_tipo"]      = $row["cod_tipo"];
        $sub_array["id_chave_pix"]  = $row["id_chave_pix"];
        $sub_array["chave_pix"]     = $row["chave_pix"];

    }
    $someArray[0] = $sub_array;
    $pp = json_encode($someArray);
    echo json_encode($someArray);