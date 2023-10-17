<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);
header("Content-type: application/json");
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST['divisao'];
$total = 0;
$prolabore = 0;
$valor_prolabore = 0;
$total_liquido = 0;
$stmt = new stdClass();
$query = "SELECT nome_convenio as descri,sum(valor) as total,prolabore,prolabore2,residuo,acrescimo,divisao,cod_convenio,email,controle,id_categoria_recibo FROM sind.qextrato WHERE mes = '" . $_POST["mes"] ."' AND divisao = ".$divisao."AND cobranca = true AND prolabore2 > 0 GROUP BY nome_convenio,divisao,prolabore,prolabore2,acrescimo,cod_convenio,residuo,email,controle,id_categoria_recibo ORDER BY nome_convenio";

$someArray = array();
$statment = $pdo->query($query);
$enviado = 0;
$pago = 0;
$data_pgto = null;
while($row = $statment->fetch()) {
    $sub_array = array();
    if($row["total"] !== null){
        $total = number_format( $row["total"], 2, '.', '');
    }else{
        $total = 0;
    }
    if($row["prolabore"] !== null) {
        $prolabore = number_format($row["prolabore"], 2, '.', '');
    }else{
        $prolabore = 0;
    }
    if($row["prolabore2"] !== null) {
        $prolabore2 = number_format($row["prolabore2"], 2, '.', '');
    }else{
        $prolabore2 = 0;
    }
    if($row["acrescimo"] !== null) {
        $acrescimo = number_format($row["acrescimo"], 2, '.', '');
    }else{
        $acrescimo = 0;
    }
    if($row["residuo"] !== null) {
        $residuo = number_format($row["residuo"], 2, '.', '');
    }else{
        $residuo = 0;
    }
    $valor_prolabore = number_format(($total * $prolabore2)/100, 2, '.', '');
    $total_cobranca  = number_format(($valor_prolabore + $acrescimo + $residuo), 2, '.', '');
    $total_liquido   = number_format( $total - $total_cobranca, 2, '.', '');

    $sql = "INSERT INTO sind.cobranca(";
    $sql .= "cod_convenio,total,prolabore1,prolabore2,val_pro2,";
    $sql .= "residuo,acrescimo,val_cob,enviado,pago,data_pgto,mes,id_categoria) VALUES(";
    $sql .= ":cod_convenio, ";
    $sql .= ":total, ";
    $sql .= ":prolabore1, ";
    $sql .= ":prolabore2, ";
    $sql .= ":val_pro2, ";
    $sql .= ":residuo, ";
    $sql .= ":acrescimo, ";
    $sql .= ":val_cob, ";
    $sql .= ":enviado, ";
    $sql .= ":pago, ";
    $sql .= ":data_pgto, ";
    $sql .= ":mes, ";
    $sql .= ":id_categoria_recibo)";

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':cod_convenio', $row["cod_convenio"], PDO::PARAM_INT);
    $stmt->bindParam(':total', $total, PDO::PARAM_STR);
    $stmt->bindParam(':prolabore1', $prolabore, PDO::PARAM_STR);
    $stmt->bindParam(':prolabore2', $prolabore2, PDO::PARAM_STR);
    $stmt->bindParam(':val_pro2', $valor_prolabore, PDO::PARAM_STR);
    $stmt->bindParam(':residuo', $residuo, PDO::PARAM_STR);
    $stmt->bindParam(':acrescimo', $acrescimo, PDO::PARAM_STR);
    $stmt->bindParam(':val_cob',   $total_cobranca, PDO::PARAM_STR);
    $stmt->bindParam(':enviado',$enviado , PDO::PARAM_STR);
    $stmt->bindParam(':pago', $pago, PDO::PARAM_STR);
    $stmt->bindParam(':data_pgto', $data_pgto, PDO::PARAM_STR);
    $stmt->bindParam(':mes', $_POST["mes"], PDO::PARAM_STR);
    $stmt->bindParam(':id_categoria_recibo', $row["id_categoria_recibo"], PDO::PARAM_INT);

    $stmt->execute();
}
$aux = json_encode($someArray);
echo $aux;