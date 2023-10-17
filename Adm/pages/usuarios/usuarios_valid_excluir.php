<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tem_cadastro_conta = false;
if(isset($_POST["cod_usuario"])){
    $std = new stdClass();
    $cod_usuario = $_POST["cod_usuario"];

    $sql = "SELECT conta.associado, conta.valor, usuarios.nome, conta.lancamento, conta.data
            FROM sind.usuarios INNER JOIN sind.conta ON usuarios.codigo = conta.funcionario
            WHERE conta.funcionario = ".$cod_usuario;
    $statment = $pdo->prepare($sql);
    $statment->execute();
    $result = $statment->fetchAll();
    $tem_conta = count($result);
    if ($tem_conta > 0){
        $tem_conta = true;
        $msg = "existe conta";
        $arr = array('Resultado'=>$msg);
    }else{
        $tem_conta = false;
        $msg = "nao existe conta";
        $arr = array('Resultado'=>$msg);
    }

    $someArray = array_map("utf8_encode",$arr);

    echo json_encode($someArray);
}