<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 21/10/2020
 * Time: 14:02
 */
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST['codigo'])) {
    $codigo = $_POST['codigo'];
}else{
    $codigo = 0;
}
if(isset($_POST['estado_termo'])) {
    if ($_POST['estado_termo'] === "true") {
        $estado_termo = "true";
    }else{
        $estado_termo = "false";
    }
}else{
    $estado_termo = false;
}
$sql = "UPDATE sind.convenio SET ";
$sql .= "aceita_termo = :estado_termo ";
$sql .= "WHERE codigo = :codigo";

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':codigo', $codigo, PDO::PARAM_INT);
$stmt->bindParam(':estado_termo', $estado_termo, PDO::PARAM_STR);

$count = $stmt->execute();

if ($count == 1) {
    echo "gravou";
}else{
    echo "nao gravou";
}