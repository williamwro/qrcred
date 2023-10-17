<?PHP
header("Content-type: application/json");
require "../../php/banco.php";
include "NumeroPorExtenso.php";
$extenso = new NumeroPorExtenso;
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST['divisao'];
$dia =  $_POST['dia'];
$total = 0;
$prolabore = 0;
$valor_prolabore = 0;
$total_liquido = 0;
if ( $_POST["categoria"] != "" ) {
    $query = "SELECT nome_convenio as descri,sum(valor) as total,prolabore,divisao,cod_convenio,id_categoria_recibo, categoria_recibo 
              FROM sind.qextrato 
              WHERE mes = '" . $_POST["mes"] ."' 
              AND id_categoria_recibo = " . $_POST["categoria"] . " 
              AND divisao = ".$divisao." 
              AND cobranca = true 
              GROUP BY nome_convenio,divisao,prolabore,cod_convenio,id_categoria_recibo, categoria_recibo 
              ORDER BY nome_convenio";
} else {
    $query = "SELECT nome_convenio as descri,sum(valor) as total,prolabore,divisao,cod_convenio,id_categoria_recibo, categoria_recibo 
              FROM sind.qextrato 
              WHERE mes = '" . $_POST["mes"] ."' 
              AND divisao = ".$divisao."
              AND cobranca = true 
              GROUP BY nome_convenio,divisao,prolabore,cod_convenio,id_categoria_recibo, categoria_recibo  
              ORDER BY categoria_recibo, nome_convenio";
}
$someArray = array();
$statment  = $pdo->query($query);
while($row = $statment->fetch()) {
    $sub_array = array();
    $total                            = $row["total"];
    $prolabore                        = $row["prolabore"];
    $valor_prolabore                  = ($total * $prolabore)/100;
    $total_liquido                    = $total - $valor_prolabore;
    $sub_array["descricao"]           = $row["descri"];
    $sub_array["total"]               = $row["total"];
    $sub_array["prolabore"]           = $row["prolabore"];
    $sub_array["valor_prolabore"]     = $valor_prolabore;
    $sub_array["total_liquido"]       = $total_liquido;
    $sub_array["cod_convenio"]        = $row["cod_convenio"];
    $sub_array["categoria_recibo"]    = $row["categoria_recibo"];
    $sub_array["id_categoria_recibo"] = $row["id_categoria_recibo"];
    $sub_array["dia"]                 = $dia;
    $sub_array["mes"]                 = $_POST["mes"];
    $sub_array["extenso"]             = htmlentities($extenso->converter($total_liquido));
    $someArray["data"][]              = array_map("utf8_encode",$sub_array);
}
$aux = json_encode($someArray);
echo $aux;