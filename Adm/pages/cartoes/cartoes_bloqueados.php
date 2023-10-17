<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST['divisao'];
$idsituacao = $_POST['idsituacao'];
$query = "SELECT c_cartaoassociado.cod_verificacao, associado.nome, c_cartaoassociado.data_pedido,
                 associado.codigo, divisao.id_divisao, empregador.nome as empregador
            FROM sind.associado
      INNER JOIN sind.c_cartaoassociado 
              ON associado.codigo = c_cartaoassociado.cod_associado and associado.empregador = c_cartaoassociado.empregador
      INNER JOIN sind.empregador 
              ON associado.empregador = empregador.id
      INNER JOIN sind.divisao
              ON empregador.divisao = divisao.id_divisao
           WHERE c_cartaoassociado.cod_situacaocartao = ".$idsituacao."
             AND associado.id_divisao = ".$divisao."
        ORDER BY nome";

$someArray = array();
$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();
foreach ($result as $row){
    $sub_array = array();
    if($row['data_pedido'] !== null){
        $sub_array["data_pedido"] = date('d/m/Y', strtotime($row['data_pedido']));
    }else{
        $sub_array["data_pedido"] = '';
    }
    $sub_array["cartao"]       = $row['cod_verificacao'];
    $sub_array["nome"]         = $row['nome'];
    $sub_array["empregador"]   = $row['empregador'];
    if($idsituacao == 1) {
        $sub_array["botaoexcluir"] = '<span class="badge badge-success" style="background: green">Liberado</span>';
    }elseif($idsituacao == 2) {
        $sub_array["botaoexcluir"] = '<span class="badge badge-danger" style="background: red">Bloqueado</span>';
    }elseif($idsituacao == 3) {
        $sub_array["botaoexcluir"] = '<span class="badge badge-dark" style="background: black">Cancelado</span>';
    }elseif($idsituacao == 4) {
        $sub_array["botaoexcluir"] = '<span class="badge badge-primary" style="background: blue">Producao</span>';
    }elseif($idsituacao == 5) {
        $sub_array["botaoexcluir"] = '<span class="badge badge-primary" style="background: maroon">Segunda Via</span>';
    }elseif($idsituacao == 6) {
        $sub_array["botaoexcluir"] = '<span class="badge badge-warning" style="background: orange"> Disponivel</span>';
    }elseif($idsituacao == 7) {
        $sub_array["botaoexcluir"] = '<span class="badge badge-info" style="background: cyan;color: black">Entregue</span>';
    }

    $someArray["data"][] = array_map("utf8_encode",$sub_array);
}
$aux = json_encode($someArray);
echo json_encode($someArray);