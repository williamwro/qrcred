<?PHP
header("Content-type: application/json");
require "../../php/banco.php";
require "../../php/funcoes.php";

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$i=0;
$sql = "SELECT codigo,mes FROM sind.controle order by codigo desc limit 3";
$sql = $pdo->query($sql);
$i++;
while($row = $sql->fetch()) {
    $someArray[$i] = array_map("utf8_encode",$row);
    $i++;
}
echo json_encode($someArray);