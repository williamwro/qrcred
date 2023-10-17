<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', true);
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 26/08/2023
 * Time: 20:40
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
if(isset($_POST['senha'])) {
    $senha = $_POST['senha'];
}else{
    $senha = "";
}

$sql = "UPDATE sind.c_senhaassociado SET ";
$sql .= "senha = :senha ";
$sql .= "WHERE cod_associado = :matricula AND id_empregador = :empregador";

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
$stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
$stmt->bindParam(':senha', $senha, PDO::PARAM_STR);

$count = $stmt->execute();

if ($count == 1) {
    echo "atualizou";
}else{
    echo "nao atualizou";
}