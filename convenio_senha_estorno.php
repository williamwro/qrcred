<?PHP
header("Content-type: application/json");
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_convenio"])){
    $std = new stdClass();
    $cod_convenio = (int)$_POST["cod_convenio"];
    $someArray = array();
    $query = "SELECT * FROM sind.convenio WHERE codigo = ".$cod_convenio;
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();
    foreach ($result as $row){
        $sub_array = array();
        $std->codigo        = (int)$row["codigo"];
        $std->senha_estorno = $row["senha_estorno"];
    }
    echo json_encode($std);
}