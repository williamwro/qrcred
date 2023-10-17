<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST["divisao"];
$card1 = $_POST["card1"];
$card2 = $_POST["card2"];
$card3 = $_POST["card3"];
$card4 = $_POST["card4"];
$card5 = $_POST["card5"];
$card6 = $_POST["card6"];
if($_POST["mes"] == "Todos") {
    $sql = "Where divisao = ".$divisao." AND matricula <> '".$card1."' AND matricula <> '".$card2."' AND matricula <> '".$card3."' AND matricula <> '".$card4."' AND matricula <> '".$card5."' AND matricula <> '".$card6."'";
}else{
    $sql = "Where mes = '".$_POST["mes"]."' AND divisao ".$divisao." AND matricula <> '".$card1."' AND matricula <> '".$card2."' AND matricula <> '".$card3."' AND matricula <> '".$card4."' AND matricula <> '".$card5."' AND matricula <> '".$card6."'";
}
$query = "Select * From sind.\"qEstornos\" " . $sql ." order by lancamento";

$someArray = array();
$statment = $pdo->query($query);
while($row = $statment->fetch()) {
    $sub_array = array();
    $sub_array["lancamento"]      = $row["lancamento"];
    $sub_array["matricula"]       = $row["matricula"];
    $sub_array["nome"]            = $row["nome"];
    $sub_array["razaosocial"]     = $row["razaosocial"];
    $sub_array["nome_empregador"] = $row["nome_empregador"];
    $sub_array["valor"]           = $row["valor"];
    $sub_array["data"]            = $row["data"];
    $sub_array["hora"]            = $row["hora"];
    $sub_array["mes"]             = $row["mes"];
    $sub_array["parcela"]         = $row["parcela"];
    $sub_array["username"]        = $row["username"];
    $sub_array["data_estorno"]    = $row["data_estorno"];
    $sub_array["hora_estorno"]    = $row["hora_estorno"];
    $sub_array["username_estornado"]    = $row["username_estornado"];
    $sub_array["botaocancelar"]   = '<button type="button" name="btncancelarestorno" id="'.$row["lancamento"].'" class="btn btn-warning glyphicon glyphicon-open btn-xs btncancelarestorno" data-toggle="tooltip" data-placement="top" title="Cancelar estorno"></button>';
    $someArray["data"][] = array_map("utf8_encode",$sub_array);
}
$aux = json_encode($someArray);
echo $aux;