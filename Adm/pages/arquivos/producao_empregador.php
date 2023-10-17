<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$i=1;
$divisao = $_GET["divisao"];
$sql = $pdo->query("SELECT * FROM sind.empregador WHERE divisao = ".$divisao." ORDER BY nome ");
while($row = $sql->fetch()) {
    $someArray[$i] = array_map("utf8_encode",$row);
    $i++;
}
echo json_encode($someArray);