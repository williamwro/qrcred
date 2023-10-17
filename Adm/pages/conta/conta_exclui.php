<?php
header("Content-type: application/json");
require '../../php/banco.php';
date_default_timezone_set('America/Sao_Paulo');
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg='';
$data2          = new DateTime();
$data           = $data2->format('Y-m-d');
$hora           = date('H:i:s');
if($_POST['usuario_codigo'] === ''){
    $usuario_codigo = null;
}else{
    $usuario_codigo = $_POST['usuario_codigo'];
}

if(isset($_POST['descricao'])) {
    $descricao = $_POST['descricao'];
}else{
    $descricao = '';
}
$mes_bloqueado  = false;
if (isset($_POST['lancamento'])){
    if($_POST['lancamento'] != "")
        $mes = $_POST['mes'];
    $stmt = new stdClass();

    $sql = "SELECT mes FROM sind.controle WHERE mes = '".$mes."'";
    $statment = $pdo->prepare($sql);
    $statment->execute();
    $result = $statment->fetchAll();
    foreach ($result as $row) {
        $mes_bloqueado = true;
    }
    $_lancamento  = (int)$_POST['lancamento'];
    if(!$mes_bloqueado){

        $sql = "DELETE FROM sind.conta 
             WHERE lancamento = :lancamento";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':lancamento', $_lancamento, PDO::PARAM_INT);
        $qtde_deletados = $stmt->execute();
        $msg = 'excluido';
        // ATUALIZA DATA ESTORNO
        $sql2 = "UPDATE sind.estornos SET 
                    data_estorno = :data_estorno,
                    hora_estorno = :hora_estorno,
                    func_estorno = :func_estorno,
                    descricao = :descricao       
                 WHERE lancamento = :lancamento";
        $stmt = $pdo->prepare($sql2);
        $stmt->bindParam(':lancamento', $_lancamento, PDO::PARAM_INT);
        $stmt->bindParam(':data_estorno', $data, PDO::PARAM_STR);
        $stmt->bindParam(':hora_estorno', $hora, PDO::PARAM_STR);
        $stmt->bindParam(':func_estorno', $usuario_codigo, PDO::PARAM_INT);
        $stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
        $qtde_deletados = $stmt->execute();

    }else{
        $msg = 'mes_bloqueado';
    }
    $arr = array('lancamento' =>$_lancamento,'mes' =>$mes,'Resultado'=>$msg);
    $someArray = array_map("utf8_encode",$arr);

}else{
    $msg = 'nao excluido';
    $arr = array('lancamento' =>'','Resultado'=>$msg);
    $someArray = array_map("utf8_encode",$arr);
}
echo json_encode($someArray);