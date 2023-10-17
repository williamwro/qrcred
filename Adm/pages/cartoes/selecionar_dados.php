<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST['divisao'];
$lote = $_POST['lote'];
$card1 = $_POST["card1"];
$card2 = $_POST["card2"];
$card3 = $_POST["card3"];
$card4 = $_POST["card4"];
$card5 = $_POST["card5"];
$card6 = $_POST["card6"];

if($lote == "aberto") {
    $sql = " WHERE associado.id_divisao = ".$divisao." AND c_cartaoassociado.cod_situacaocartao = 4 AND associado.codigo <> '".$card1."' AND associado.codigo <> '".$card2."' AND associado.codigo <> '".$card3."' AND associado.codigo <> '".$card4."' AND associado.codigo <> '".$card5."' AND associado.codigo <> '".$card6."' AND lote isnull ORDER BY nome";
}else{
    $sql = " WHERE associado.id_divisao = ".$divisao." AND lote=".$lote." AND associado.codigo <> '".$card1."' AND associado.codigo <> '".$card2."' AND associado.codigo <> '".$card3."' AND associado.codigo <> '".$card4."' AND associado.codigo <> '".$card5."' AND associado.codigo <> '".$card6."' ORDER BY nome";
}
$query = "SELECT c_cartaoassociado.cod_verificacao, associado.nome,
                 associado.codigo, divisao.id_divisao, empregador.abreviacao
            FROM sind.associado
	  INNER JOIN sind.empregador 
  			  ON associado.empregador = empregador.id 
      INNER JOIN sind.c_cartaoassociado 
              ON associado.codigo = c_cartaoassociado.cod_associado and associado.empregador = c_cartaoassociado.empregador
      INNER JOIN sind.divisao 
              ON associado.id_divisao = divisao.id_divisao".$sql;

$someArray = array();
$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();
foreach ($result as $row){
    $sub_array = array();

    $sub_array["cartao"]       = $row['cod_verificacao'];
    $sub_array["codigo"]       = $row['codigo'];
    $sub_array["abreviacao"]   = $row['abreviacao'];
    $sub_array["nome"]         = $row['nome'];
    $sub_array["botaoexcluir"] = '<button type="button" name="btnexcluirCartao" id="'.$row["cod_verificacao"].'" class="btn btn-danger btn-xs btnexcluirCartao" disabled>Excluir</button>';

    $someArray["data"][] = array_map("utf8_encode",$sub_array);
}
$aux = json_encode($someArray);
echo json_encode($someArray);