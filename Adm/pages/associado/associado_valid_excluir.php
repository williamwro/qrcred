<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tem_cadastro_conta = false;
if(isset($_POST["cod_associado"])){
    $std = new stdClass();
    $cod_associado = $_POST["cod_associado"];
    $empregador = $_POST["id_empregador"];

    $sql = "SELECT conta.associado, conta.valor, empregador.abreviacao, conta.lancamento, conta.data
            FROM empregador INNER JOIN conta ON empregador.Id = conta.empregador
            WHERE conta.associado = '".$cod_associado."' AND conta.empregador = ".$empregador.";";
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