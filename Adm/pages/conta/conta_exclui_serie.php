<?php
header("Content-type: application/json");
require '../../php/banco.php';
date_default_timezone_set('America/Sao_Paulo');
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$data2          = new DateTime();
$data           = $data2->format('Y-m-d');
$hora           = date('H:i:s');
$msg='';
$contador = 0;
$sqlregs = "";
$totalpost = 0;
if (isset($_POST['registro'])){
    if($_POST['registro'] != "")
        $stmt = new stdClass();

    foreach($_POST['usuario_codigo'] as $i => $arr) {

        $usuario_codigo = $arr['usuario_codigo'];

    }

    $totalpost = count($_POST['registro']);

    foreach($_POST['registro'] as $i => $arr) {

        if ($totalpost > $i+1){
            $sqlregs .= $arr['registro'] . ",";
        }else{
            $sqlregs .= $arr['registro'];
        }
    }
    $sql = "DELETE FROM sind.conta WHERE lancamento IN(".$sqlregs.")";
    $stmt = $pdo->prepare($sql);
    //$stmt->bindParam(':lancamento', $_lancamento, PDO::PARAM_INT);
    $stmt->execute();

    $msg = 'excluido';

    // ATUALIZA DATA ESTORNO
    $sql2 = "UPDATE sind.estornos SET 
                data_estorno = :data_estorno,
                hora_estorno = :hora_estorno,
                func_estorno = :func_estorno         
             WHERE lancamento IN(".$sqlregs.")";
    $stmt = $pdo->prepare($sql2);
    $stmt->bindParam(':data_estorno', $data, PDO::PARAM_STR);
    $stmt->bindParam(':hora_estorno', $hora, PDO::PARAM_STR);
    $stmt->bindParam(':func_estorno', $usuario_codigo, PDO::PARAM_INT);
    $qtde_deletados = $stmt->execute();

    $arr = array('Resultado'=>$msg);
    $someArray = array_map("utf8_encode",$arr);

}else{
    $msg = 'nao excluido';
    $arr = array('lancamento' =>'','Resultado'=>$msg);
    $someArray = array_map("utf8_encode",$arr);
}
echo json_encode($someArray);





