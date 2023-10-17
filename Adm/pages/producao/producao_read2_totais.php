<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST['divisao'];

if ($_POST["cod_tipo"] != "" and $_POST["empregador"] != "" ) {

    $query = "Select nome_convenio as descri, sum(valor) as total, divisao From sind.qextrato Where mes = '" . $_POST["mes"] ."' and id_empregador = " . $_POST["empregador"] . " and cod_tipo_convenio = " . $_POST["cod_tipo"] . " and divisao = ".$divisao." and cobranca = true Group by nome_convenio, divisao order by nome_convenio";

} else if ($_POST["cod_tipo"] != "" and $_POST["empregador"] == "" ) {

    $query = "Select nome_convenio as descri, sum(valor) as total, divisao From sind.qextrato Where mes = '" . $_POST["mes"] ."' and cod_tipo_convenio = " . $_POST["cod_tipo"] . " and divisao = ".$divisao." and cobranca = true Group by nome_convenio, divisao order by nome_convenio";

} else if ($_POST["cod_tipo"] != "" and $_POST["empregador"] == "" ) {

    if ($_POST["cod_subtipo"] == "" || $_POST["cod_subtipo"] == "empregador") {
        $query = "Select nome_empregador as descri, sum(valor) as total, divisao From sind.qextrato Where mes = '" . $_POST["mes"] . "' and cod_tipo_convenio = " . $_POST["cod_tipo"] . " and divisao = ".$divisao." and cobranca = true Group by nome_empregador, divisao order by nome_empregador";
    }else{
        $query = "Select nome_convenio as descri, sum(valor) as total, divisao From sind.qextrato Where mes = '" . $_POST["mes"] . "' and cod_tipo_convenio = " . $_POST["cod_tipo"] . " and divisao = ".$divisao." and cobranca = true Group by nome_convenio, divisao order by nome_convenio";
    }

} else if ($_POST["cod_tipo"] == "" and $_POST["empregador"] != "" ) {

    $query = "Select nome_convenio as descri, sum(valor) as total, divisao From sind.qextrato Where mes = '" . $_POST["mes"] ."' and id_empregador = " . $_POST["empregador"]. " and divisao = ".$divisao." and cobranca = true Group by nome_convenio, divisao order by nome_convenio";

} else if ($_POST["cod_tipo"] == "" and $_POST["empregador"] == "" and $_POST["cod_subtipo"] == "EMPREGADOR" ) {

    $query = "Select nome_empregador as descri, sum(valor) as total, divisao From sind.qextrato Where mes = '" . $_POST["mes"] . "' and divisao = ".$divisao." and cobranca = true Group by nome_empregador, divisao order by nome_empregador";

} else if ($_POST["cod_tipo"] == "" and $_POST["empregador"] == "" and $_POST["cod_subtipo"] == "CONVENIO" ) {

    $query = "Select nome_convenio as descri, sum(valor) as total, divisao From sind.qextrato Where mes = '" . $_POST["mes"] . "' and divisao = ".$divisao." and cobranca = true Group by nome_convenio, divisao order by nome_convenio";

}
$someArray = array();
$statment = $pdo->query($query);
while($row = $statment->fetch()) {
    $sub_array = array();
    $sub_array["descricao"]     = $row["descri"];
    $sub_array["total"]         = (real)$row["total"];

    $someArray["data"][] = array_map("utf8_encode",$sub_array);

}
$aux = json_encode($someArray);
echo $aux;