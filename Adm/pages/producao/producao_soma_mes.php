<?php
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao      = $_POST['divisao'];
$cod_convenio = $_POST['cod_convenio'];


    $query = "SELECT mesx, mesy, total FROM sind.soma_meses_convenio(".$divisao.",".$cod_convenio.") 
              ORDER BY mesx";

$someArray = array();
$statment = $pdo->query($query);
while($row = $statment->fetch()) {
    $sub_array = array();
    $sub_array["mesy"]  = $row["mesy"];
    $sub_array["mesx"]  = $row["mesx"];
    $sub_array["total"] = $row["total"];

    $someArray["data"][] = array_map(function($value) {
    return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
}, $sub_array);

}
$aux = json_encode($someArray);
echo $aux;