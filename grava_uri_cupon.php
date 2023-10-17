<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/08/2018
 * Time: 14:02
 */
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST['lancamento'])) {
    $lancamento = $_POST['lancamento'];
}else{
    $lancamento = "";
}
if(isset($_POST['uri_cupon'])) {
    $uri_cupon = $_POST['uri_cupon'];
}else{
    $uri_cupon = "";
}

$sql = "UPDATE sind.conta SET uri_cupom =:uri_cupom WHERE lancamento=:lancamento";
$stmt = $pdo->prepare($sql);

$stmt->bindParam(':lancamento', $lancamento, PDO::PARAM_STR);
$stmt->bindParam(':uri_cupom', $uri_cupon, PDO::PARAM_STR);
$count = $stmt->execute();

if ($count == 1) {
    echo "gravou";
}else{
    echo "nao gravou";
}
