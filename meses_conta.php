<?PHP
    header("Content-type: application/json");
    require 'Adm/php/banco.php';
    include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$origem = $_GET['origem'];
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