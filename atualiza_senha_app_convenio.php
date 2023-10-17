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
if(isset($_POST['cod_convenio'])) {
    $cod_convenio = $_POST['cod_convenio'];
}else{
    $cod_convenio = 0;
}
if(isset($_POST['senha'])) {
    $senha_md5 = md5($_POST['senha']);
    $password  = $_POST['senha'];
} else {
    $senha_md5 = "";
    $password  = "";
}

$sql = "UPDATE sind.c_senhaconvenio SET ";
$sql .= "password = :password, ";
$sql .= "senha = :senha ";
$sql .= "WHERE cod_convenio = :cod_convenio";

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':cod_convenio', $cod_convenio, PDO::PARAM_INT);
$stmt->bindParam(':senha', $senha_md5, PDO::PARAM_STR);
$stmt->bindParam(':password', $password, PDO::PARAM_STR);


$count = $stmt->execute();

if ($count == 1) {
    echo "atualizou";
}else{
    echo "nao atualizou";
}