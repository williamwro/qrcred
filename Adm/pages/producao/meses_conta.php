<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$origem = $_GET['origem'];
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$i=0;
$row = $pdo->query( "SELECT abreviacao FROM sind.mes_corrente" )->fetch();
$someArray[$i]["mes_corrente"] = $row["abreviacao"];
if($origem === "admin"){
    $sql = "SELECT * FROM sind.meses_conta WHERE status_admin = 1 ORDER BY data";
}elseif($origem === "convenio"){
    $sql = "SELECT * FROM sind.meses_conta WHERE  status_convenio = 1 ORDER BY data";
}elseif($origem === "relatorio"){
    $sql = "SELECT * FROM sind.meses_conta WHERE  status_relatorio = 1 ORDER BY data";
}
$sql = $pdo->query($sql);

$i++;
while($row = $sql->fetch()) {
    $someArray[$i] = array_map("utf8_encode",$row);
    $i++;
}

echo json_encode($someArray);