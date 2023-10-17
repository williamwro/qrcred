<?PHP
header("Content-type: application/json");
require "../../php/banco.php";
require "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$i=0;
$status_cheque = 1;

$sql = "SELECT abreviacao FROM sind.meses_conta WHERE status_cheque = ".$status_cheque;

$sql = $pdo->query($sql);

$i++;
while($row = $sql->fetch()) {
    $someArray[$i] = array_map("utf8_encode",$row);
    $i++;
}

echo json_encode($someArray);