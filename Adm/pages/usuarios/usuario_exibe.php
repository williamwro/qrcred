<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//$tem_cadastro_conta = false;
if(isset($_POST["codigo_usuario"])){
    $std = new stdClass();
    $codigo_usuario = $_POST["codigo_usuario"];
    $query = "SELECT *
            FROM sind.qusuarios
           WHERE qusuarios.codigo = ".$codigo_usuario;

    $statment = $pdo->prepare($query);

    $statment->execute();
    $result = $statment->fetchAll();
    $salario='';
    $linha = array();

    foreach ($result as $row){

        $std->codigo          = $row["codigo"];
        $std->username        = $row["username"];
        //$std->password        = $row["password"];
        //$std->senha           = $row["senha"];
        $std->email           = $row["email"];
        $std->lastname        = htmlspecialchars($row["lastname"]);
        $std->situacao        = (int)$row["situacao"];
        $std->nome            = htmlspecialchars($row["nome"]);
        $std->divisao         = (int)$row["divisao"];
        $std->descri_situacao = $row["descri_situacao"];
        $std->nome_divisao    = $row["nome_divisao"];
    }
    $resultado = json_encode($std);

    echo $resultado;
}