<?PHP
header("Content-type: application/json");
require "../../php/banco.php";
include "NumeroPorExtenso.php";
$extenso = new NumeroPorExtenso;
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST['divisao'];
$total = 0;
$prolabore = 0;
$valor_prolabore = 0;
$total_liquido = 0;
if ( $_POST["categoria"] != "" ) {
    $query = "SELECT id,id_convenio,mes,valor,alicota,val_alicota, 
                     liquido,extenso,data,id_categoria_recibo,prtsn, 
                     data_pgto,prtch,id_new,razaosocial,nomefantasia,
                     categoria 
                FROM sind.pagamentos2 
               WHERE mes = '" . $_POST["mes"] ."' 
                 AND id_categoria_recibo = " . $_POST["categoria"];
} else {
    $query = "SELECT id,id_convenio,mes,valor,alicota,val_alicota, 
                     liquido,extenso,data,id_categoria_recibo,prtsn, 
                     data_pgto,prtch,id_new,razaosocial,nomefantasia,
                     categoria 
                FROM sind.pagamentos2 
               WHERE mes = '" . $_POST["mes"] ."'";
}
$someArray = array();
$statment  = $pdo->query($query);
while($row = $statment->fetch()) {
    $sub_array = array();
    $extenso                          = $row["extenso"];
    $sub_array["id"]                  = $row["id"];
    $sub_array["id_convenio"]         = $row["id_convenio"];
    $sub_array["mes"]                 = $row["mes"];
    $sub_array["valor"]               = $row["valor"];
    $sub_array["alicota"]             = $row["alicota"];
    $sub_array["val_alicota"]         = $row["val_alicota"];
    $sub_array["liquido"]             = $row["liquido"];
    $sub_array["extenso"]             = $row["extenso"];
    $sub_array["data"]                = $row["data"];
    $sub_array["id_categoria_recibo"] = $_POST["id_categoria_recibo"];
    $sub_array["extenso"]             = htmlentities($extenso->converter($extenso));
    $sub_array["razaosocial"]         = $row["razaosocial"];
    $sub_array["nomefantasia"]        = $row["nomefantasia"];
    $sub_array["categoria"]           = $row["categoria"];
    $someArray["data"][]              = array_map("utf8_encode",$sub_array);
}
$aux = json_encode($someArray);
echo $aux;