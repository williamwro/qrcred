<?php
header("Content-type: application/json");
require '../../php/banco.php';
date_default_timezone_set('America/Sao_Paulo');
$msg='';
$_cartao='';
$_opcao=0;
$_opcao2=0;
$_obs='';
$_matricula='';
$_data='';
$_hora='';
$_usuario='';
$data2 = new DateTime();
$datax = $data2->format('Y-m-d');
$data3 = $data2->format('d-m-Y');
$data4 = new DateTime($data3);
$data = $data4->format('d/m/Y');
$data = converte_data($data);
$hora = date("H:i:s");
$hora = str_replace("00:00:00",$hora,$data);
function converte_data($date) {
    return substr($date,6,4).'-'.substr($date,3,2).'-'.substr($date,0,2).' 00:00:00';
}
if($_POST['cartao']){
    if($_POST['cartao'] != "") {

        $stmt = new stdClass();
        $stmt2 = new stdClass();
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $_opcao = (int)$_POST['opcao'];
        $_cartao = $_POST['cartao'];
        $_matricula = $_POST['matricula'];
        $_obs = $_POST['obs'];
        $_usuario = $_POST['usuario'];
        $_empregador = $_POST['empregador'];

        $sql = "UPDATE sind.c_cartaoassociado SET cod_situacaocartao = :cod_situacao, data_pedido = :data_pedido 
                WHERE cod_verificacao = :cod_verificacao ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':cod_situacao', $_opcao, PDO::PARAM_INT);
        $stmt->bindParam(':cod_verificacao', $_cartao, PDO::PARAM_STR);
        $stmt->bindParam(':data_pedido', $datax, PDO::PARAM_STR);
        $stmt->execute();
        if($_opcao == 7){
            $_opcao2 = 3;
            $sql3 = "UPDATE sind.c_cartaoassociado SET cod_situacao2 = :cod_situacao , data_entrega = :data_entrega 
                     WHERE cod_verificacao = :cod_verificacao ";
            $stmt3 = $pdo->prepare($sql3);
            $stmt3->bindParam(':cod_situacao', $_opcao2, PDO::PARAM_INT);
            $stmt3->bindParam(':cod_verificacao', $_cartao, PDO::PARAM_STR);
            $stmt3->bindParam(':data_entrega', $datax, PDO::PARAM_STR);
            $stmt3->execute();
        }
        $sql2 = "INSERT INTO sind.c_historico_cartoes(matricula,cod_verificacao,cod_situacaocartao,data,hora,usuario,obs,id_empregador)";
        $sql2 .= " VALUES(";
        $sql2 .= ":matricula, ";
        $sql2 .= ":cod_verificacao, ";
        $sql2 .= ":cod_situacao, ";
        $sql2 .= ":data, ";
        $sql2 .= ":hora, ";
        $sql2 .= ":usuario, ";
        $sql2 .= ":obs, ";
        $sql2 .= ":id_empregador)";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->bindParam(':matricula', $_matricula, PDO::PARAM_STR);
        $stmt2->bindParam(':cod_verificacao', $_cartao, PDO::PARAM_STR);
        $stmt2->bindParam(':cod_situacao', $_opcao, PDO::PARAM_INT);
        $stmt2->bindParam(':data', $data, PDO::PARAM_STR);
        $stmt2->bindParam(':hora', $hora, PDO::PARAM_STR);
        $stmt2->bindParam(':usuario', $_usuario, PDO::PARAM_STR);
        $stmt2->bindParam(':obs', $_obs, PDO::PARAM_STR);
        $stmt2->bindParam(':id_empregador', $_empregador, PDO::PARAM_INT);

        $stmt2->execute();
        $msg = $_opcao;
        $arr = array('cod_verificacao' => $_cartao, 'Resultado' => $msg);
        $someArray = array_map("utf8_encode", $arr);
    }else{
        $msg = 'nao alterado';
        $arr = array('cod_verificacao' => $_cartao, 'Resultado' => $msg);
        $someArray = array_map("utf8_encode",$arr);
    }

}else{
    $msg = 'nao alterado';
    $arr = array('cod_verificacao' =>'','Resultado'=>$msg);
    $someArray = array_map("utf8_encode",$arr);
}
echo json_encode($someArray);