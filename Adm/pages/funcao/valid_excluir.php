<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tem_cadastro_conta = false;
if(isset($_POST["cod_categoria"])){
    $std = new stdClass();
    $cod_categoria = $_POST["cod_categoria"];

    $sql = "SELECT associado.codigo, empregador.id_divisao
              FROM sind.associado INNER JOIN sind.empregador ON associado.empregador = empregador.id
             WHERE empregador.id =  = ".$cod_empregador;
    $statment = $database->prepare($sql);
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

    $someArray = array_map(function($value) {
        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
    }, $arr);

    echo json_encode($someArray);
}