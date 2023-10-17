<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cartao = $_POST['numerocartao'];
$someArray = array();

    $query = "SELECT ASSOCIADO.Codigo, ASSOCIADO.Nome, C_CARTAOASSOCIADO.cod_verificacao, C_CARTAOASSOCIADO.data_pedido, C_CARTAOASSOCIADO.data_entrega, C_CARTAOASSOCIADO.id
              FROM ASSOCIADO 
              INNER JOIN C_CARTAOASSOCIADO 
              ON (C_CARTAOASSOCIADO.empregador = ASSOCIADO.Empregador) 
              AND (ASSOCIADO.Codigo = C_CARTAOASSOCIADO.cod_associado)
              WHERE C_CARTAOASSOCIADO.cod_verificacao = '".$cartao."';";

    $someArray = array();
    $resultado="";
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();
    $linhas_filtradas = count($result);

    if($linhas_filtradas > 0){
        $someArray = array('resultado'=>"repetido");
    }else{
        $someArray = array('resultado'=>"nao repetido");
    }
    echo json_encode($someArray);