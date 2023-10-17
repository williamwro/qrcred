<?PHP
header("Content-type: application/json");
require "../../php/banco.php";
include "NumeroPorExtenso.php";
$extenso = new NumeroPorExtenso;
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$total = 0;
$prolabore = 0;
$valor_prolabore = 0;
$total_liquido = 0;
if ( $_POST["categoria"] != "" ) {
    $query = "SELECT id_convenio, mes, valor, alicota, val_alicota, liquido, extenso, data, 
                     id_categoria_recibo, prtch, razaosocial, nomefantasia, categoria, id_new
                FROM sind.pagamentos2 
               WHERE mes = '" . $_POST["mes"] ."' 
                 AND id_categoria_recibo = " . $_POST["categoria"] . "
            ORDER BY razaosocial ASC";
} else {
    $query = "SELECT id_convenio, mes, valor, alicota, val_alicota, liquido, extenso, data, 
                     id_categoria_recibo, prtch, razaosocial, nomefantasia, categoria, id_new
                FROM sind.pagamentos2 
               WHERE mes = '" . $_POST["mes"] ."'
            ORDER BY categoria ASC, razaosocial ASC";
}
$someArray = array();
$statment  = $pdo->query($query);
while($row = $statment->fetch()) {
    $sub_array = array();
    $total                            = $row["valor"];
    $prolabore                        = $row["alicota"];
    $valor_prolabore                  = ($total * $prolabore)/100;
    $total_liquido                    = $total - $valor_prolabore;
    $sub_array["razaosocial"]         = $row["razaosocial"];
    $sub_array["total"]               = $row["valor"];
    $sub_array["prolabore"]           = $row["alicota"];
    $sub_array["valor_prolabore"]     = $valor_prolabore;
    $sub_array["total_liquido"]       = $total_liquido;
    $sub_array["id_convenio"]         = $row["id_convenio"];
    $sub_array["categoria"]           = $row["categoria"];
    $sub_array["id_new"]              = $row["id_new"];
    $sub_array["prtch"]               = $row["prtch"];
    $sub_array["id_categoria_recibo"] = $row["id_categoria_recibo"];
    $sub_array["mes"]                 = $_POST["mes"];
    $sub_array["extenso"]             = htmlentities($extenso->converter($total_liquido));
    $someArray["data"][]              = array_map("utf8_encode",$sub_array);
}
$aux = json_encode($someArray);
echo $aux;