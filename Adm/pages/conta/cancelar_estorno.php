<?php
header("Content-type: application/json");
require '../../php/banco.php';
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg='';
$mes_bloqueado = false;
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

        $sql = "DELETE FROM sind.estornos 
             WHERE lancamento = :lancamento";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':lancamento', $_lancamento, PDO::PARAM_INT);
        $qtde_deletados = $stmt->execute();
        $msg = 'excluido';

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





