<?php
header("Content-type: application/json");
require '../../php/banco.php';
date_default_timezone_set('America/Sao_Paulo');
$msg='';
$_codsituacao='';
$_cartao=0;
$_matricula='';
$_empregador='';

$data2 = new DateTime();
$data3 = $data2->format('d-m-Y');
$data4 = new DateTime($data3);
$data = $data4->format('d/m/Y');
$hora = date("H:i:s");

if (isset($_POST['matricula'])){
    if($_POST['matricula'] != "") {

        $stmt = new stdClass();
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $_codsituacao = 4;
        $_cartao = $_POST['numerocartao'];
        $_matricula = $_POST['matricula'];
        $_empregador = $_POST['codempregador'];

        $sql = "INSERT INTO C_CARTAOASSOCIADO(cod_situacaocartao,cod_associado,cod_verificacao,empregador,data_pedido)";
        $sql .= " VALUES(";
        $sql .= ":cod_situacaocartao, ";
        $sql .= ":cod_associado, ";
        $sql .= ":cod_verificacao, ";
        $sql .= ":empregador, ";
        $sql .= ":data_pedido)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':cod_situacaocartao', $_codsituacao, PDO::PARAM_INT);
        $stmt->bindParam(':cod_associado', $_matricula, PDO::PARAM_STR);
        $stmt->bindParam(':cod_verificacao', $_cartao, PDO::PARAM_STR);
        $stmt->bindParam(':empregador', $_empregador, PDO::PARAM_STR);
        $stmt->bindParam(':data_pedido', $data, PDO::PARAM_STR);

        $stmt->execute();
        $msg = 'Cartão criado com sucesso!';
        $arr = array('resultado' => $msg);
        $someArray = array_map("utf8_encode", $arr);
    }else{
        $msg = 'ERRO';
        $arr = array('resultado' => $msg);
        $someArray = array_map("utf8_encode",$arr);
    }

}else{
    $msg = 'nao alterado';
    $arr = array('resultado'=>$msg);
    $someArray = array_map("utf8_encode",$arr);
}
echo json_encode($someArray);