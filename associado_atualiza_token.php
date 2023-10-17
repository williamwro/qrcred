<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', true);
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 26/08/2020
 * Time: 11:17
 */
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST['matricula'])) {
    $matricula = $_POST['matricula'];
}else{
    $matricula = "";
}
if(isset($_POST['empregador'])) {
    $empregador = (int)$_POST['empregador'];
}else{
    $empregador = "";
}
if(isset($_POST['token'])) {
    $token = $_POST['token'];
}else{
    $token = "";
}

$sql = "UPDATE sind.associado SET ";
$sql .= "token_associado = :token ";
$sql .= "WHERE codigo = :matricula AND empregador = :empregador";

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
$stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
$stmt->bindParam(':token', $token, PDO::PARAM_STR);

$count = $stmt->execute();

if ($count == 1) {
    echo "atualizou";
}else{
    echo "nao atualizou";
}