<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 26/08/2020
 * Time: 11:17
 */
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST['codigo'])) {
    $codigo = (int)$_POST['codigo'];
}else{
    $codigo = "";
}
if(isset($_POST['token'])) {
    $token = $_POST['token'];
}else{
    $token = "";
}

$sql = "UPDATE sind.convenio SET ";
$sql .= "token_convenio = :token ";
$sql .= "WHERE codigo = :codigo";

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':codigo', $codigo, PDO::PARAM_INT);
$stmt->bindParam(':token', $token, PDO::PARAM_STR);

$count = $stmt->execute();

if ($count == 1) {
    echo "atualizou";
}else{
    echo "nao atualizou";
}