<?php
require '../../php/banco.php';
$divisao= $_POST['divisao'];
$abreviacao= $_POST['abreviacao'];
$status= $_POST['status'];
$mes_anterior= $_POST['mes_anterior'];      
$mes_anterior2= $_POST['mes_anterior2'];      
$status_value = 0;
$inverted_status_value = 0;

$std = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if($abreviacao != null){
    if($status === "Bloqueado" ){
        $status_value=1;
        $inverted_status_value=0;
        $stmt = $pdo->prepare("DELETE FROM sind.controle WHERE mes = ? AND divisao = ?");
        $stmt->execute([$abreviacao, $divisao]);

        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cadastro = ?, status_cheque = ? WHERE abreviacao = ? AND divisao = ?");
        $stmt->execute([$status_value, $inverted_status_value, $abreviacao, $divisao]);
    
        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cheque = ? WHERE abreviacao = ? AND divisao = ?");
        $stmt->execute([$inverted_status_value, $mes_anterior, $divisao]);
    
        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cheque = ? WHERE abreviacao = ? AND divisao = ?");
        $stmt->execute([$status_value, $mes_anterior2, $divisao]);
    }else if($status === "Liberado" ){
        $stmt = $pdo->prepare("INSERT INTO sind.controle(mes, divisao) VALUES(?, ?)");
        $stmt->execute([$abreviacao, $divisao]);
        $status_value = 0;
        $inverted_status_value = 1;

        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cadastro = ?, status_cheque = ? WHERE abreviacao = ? AND divisao = ?");
        $stmt->execute([$status_value, $inverted_status_value, $abreviacao, $divisao]);
    
        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cheque = ? WHERE abreviacao = ? AND divisao = ?");
        $stmt->execute([$inverted_status_value, $mes_anterior, $divisao]);
    
        $stmt = $pdo->prepare("UPDATE sind.meses_conta SET status_cheque = ? WHERE abreviacao = ? AND divisao = ?");
        $stmt->execute([$status_value, $mes_anterior2, $divisao]);
    }



    $resultado = "atualizado";
    $arr = array('resultado' => $resultado);
    $someArray = array_map(function($value) {
        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
    }, $arr);

    echo json_encode($someArray);
}