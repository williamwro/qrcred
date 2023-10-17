<?php
require '../../php/banco.php';
$divisao= $_POST['divisao'];
$abreviacao= $_POST['abreviacao'];
$status= $_POST['status'];
$mes_anterior= $_POST['mes_anterior'];      
$mes_anterior2= $_POST['mes_anterior2'];      
$valstatus=0;
$valstatus_invertido=0;

$std = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if($abreviacao != null){
    if($status === "Bloqueado" ){
        $valstatus=1;
        $valstatus_invertido=0;
        $stmt = $pdo->prepare("DELETE FROM sind.controle WHERE mes =  '".$abreviacao."'");
        $stmt->execute();

        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cadastro = ".$valstatus.", status_cheque = ".$valstatus_invertido." WHERE abreviacao =  '".$abreviacao."'");
        $stmt->execute();
    
        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cheque = ".$valstatus_invertido." WHERE abreviacao =  '".$mes_anterior."'");
        $stmt->execute();
    
        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cheque = ".$valstatus." WHERE abreviacao =  '".$mes_anterior2."'");
        $stmt->execute();
    }else if($status === "Liberado" ){
        $stmt = $pdo->prepare("INSERT INTO sind.controle(mes) VALUES('".$abreviacao."')");
        $stmt->execute();
        $valstatus=0;
        $valstatus_invertido=1;

        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cadastro = ".$valstatus.", status_cheque = ".$valstatus_invertido." WHERE abreviacao =  '".$abreviacao."'");
        $stmt->execute();
    
        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cheque = ".$valstatus_invertido." WHERE abreviacao =  '".$mes_anterior."'");
        $stmt->execute();
    
        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cheque = ".$valstatus." WHERE abreviacao =  '".$mes_anterior2."'");
        $stmt->execute();
    }



    $resultado = "atualizado";
    $arr = array('resultado' => $resultado);
    $someArray = array_map("utf8_encode",$arr);

    echo json_encode($someArray);
}