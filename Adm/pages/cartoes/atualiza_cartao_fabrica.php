<?php
header("Content-type: application/json");
require '../../php/banco.php';
date_default_timezone_set('America/Sao_Paulo');
$msg='';
$totalpost = 0;
$codempregador = 0;
if (isset($_POST['dados'][0]['numerocartao'])){
    if($_POST['dados'][0]['numerocartao'] != "") {
        $stmt = new stdClass();
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $totalpost = count($_POST['numerocartao']);
        foreach($_POST['dados'] as $i => $arr) {
            $sql = "UPDATE C_CARTAOASSOCIADO SET cod_situacaocartao = 1 WHERE cod_verificacao ='".$_POST['dados'][$i]['numerocartao']."' AND Empregador = ".$_POST['dados'][$i]['codempregador'];
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }

        $msg = "criado";
        $arr = array('cod_verificacao' => $_cartao, 'resultado' => $msg);
        $someArray = array_map("utf8_encode", $arr);
    }else{
        $msg = 'nao criado';
        $arr = array('cod_verificacao' => $_cartao, 'resultado' => $msg);
        $someArray = array_map("utf8_encode",$arr);
    }

}else{
    $msg = 'nao criado';
    $arr = array('cod_verificacao' =>'','Resultado'=>$msg);
    $someArray = array_map("utf8_encode",$arr);
}
echo json_encode($someArray);