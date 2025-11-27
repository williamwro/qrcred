<?PHP
header("Content-type: application/json");
require "../../php/banco.php";
require "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$i=0;
$status_cheque = 1;
$divisao = $_GET['divisao'];
$sql = "SELECT abreviacao FROM sind.meses_conta WHERE status_cheque = ".$status_cheque." AND divisao = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array($divisao));
$i++;
while($row = $stmt->fetch()) {
    $someArray[$i] = array_map(function($value) {
    return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
}, $row);
    $i++;
}

echo json_encode($someArray);