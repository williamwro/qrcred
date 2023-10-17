<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tem_cadastro_conta = false;
if(isset($_POST["cod_divisao"])){
    $std = new stdClass();
    $cod_divisao = $_POST["cod_divisao"];

    $sql = "SELECT associado.codigo, empregador.divisao
              FROM sind.associado INNER JOIN sind.empregador ON associado.empregador = empregador.id
             WHERE empregador.divisao =  = ".$cod_divisao;
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