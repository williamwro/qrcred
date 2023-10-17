<?PHP
header("Content-type: application/json");
require '../../php/banco.php';
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('America/Sao_Paulo');
$_codsituacao = 4;
$_matricula   = $_POST['matricula'];
$_empregador  = $_POST['empregador'];
$id_divisao   = $_POST['id_divisao'];
$data2 = new DateTime();
$data3 = $data2->format('d-m-Y');
$data4 = new DateTime($data3);
$data = $data4->format('d/m/Y');
$data = converte_data($data);
$someArray = array();
$existe_cartao="nao";

$_cartao = "";
//GERA O NUMERO DO CARTAO ALEATORIO
$_cartao = mt_rand(1111111111, 9999999999);

function converte_data($date) {
    return substr($date,6,4).'-'.substr($date,3,2).'-'.substr($date,0,2).' 00:00:00';
}
//VERIFICA SE EXISTE O CARTAO GERADO E CALCULA OUTRO NUMERO NOVO
for ($i=1;$i<100;$i++) {
    $query = "SELECT * FROM sind.c_cartaoassociado WHERE cod_verificacao = '".$_cartao."'";
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();
    foreach ($result as $row) {
        $existe_cartao = "sim";
    }
    if($existe_cartao === 'sim'){
        $_cartao = mt_rand(1111111111, 9999999999);
        $existe_cartao = "nao";
    }else{
        break;
    }
}
$_cartao = strval($_cartao);
$_empregador = intval($_empregador);
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $msg_grava_cad = "";

$sql = "INSERT INTO sind.c_cartaoassociado(cod_situacaocartao,cod_associado,cod_verificacao,empregador,data_pedido,cod_situacao2,id_divisao)";
$sql .= " VALUES(";
$sql .= ":cod_situacaocartao, ";
$sql .= ":cod_associado, ";
$sql .= ":cod_verificacao, ";
$sql .= ":empregador, ";
$sql .= ":data_pedido, ";
$sql .= ":cod_situacao2, ";
$sql .= ":id_divisao)";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':cod_situacaocartao', $_codsituacao, PDO::PARAM_INT);
$stmt->bindParam(':cod_associado', $_matricula, PDO::PARAM_STR);
$stmt->bindParam(':cod_verificacao', $_cartao, PDO::PARAM_STR);
$stmt->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
$stmt->bindParam(':data_pedido', $data, PDO::PARAM_STR);
$stmt->bindParam(':cod_situacao2', $_codsituacao, PDO::PARAM_INT);
$stmt->bindParam(':id_divisao', $id_divisao, PDO::PARAM_INT);

    $msg_grava_cad = "cadastrado";

    $stmt->execute();

$arr = array('resultado' =>$msg_grava_cad);
echo json_encode($arr);