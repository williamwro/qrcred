<?php
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$cod_convenio = $_POST['cod_convenio'];
$mes = $_POST['mes'];

if ($cod_convenio != "" and $mes != "todos" ) {

    $query = "SELECT mesx, mesy, total FROM sind.soma_meses_convenio(".$cod_convenio.") 
              WHERE mesy = '".$mes."' 
              ORDER BY mesx";

} else if ($cod_convenio != "" and $mes == "todos" ) {

    $query = "SELECT mesx, mesy, total FROM sind.soma_meses_convenio(".$cod_convenio.") 
              ORDER BY mesx";

}
$someArray = array();
$statment = $pdo->query($query);
while($row = $statment->fetch()) {
    $sub_array = array();
    $sub_array["mesy"]  = $row["mesy"];
    $sub_array["mesx"]  = $row["mesx"];
    $sub_array["total"] = $row["total"];

    $someArray["data"][] = array_map("utf8_encode",$sub_array);

}
$aux = json_encode($someArray);
echo $aux;