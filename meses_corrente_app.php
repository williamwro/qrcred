<?PHP
header("Content-type: application/json");
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$std = new stdClass();
$someArray = array();

$query = $pdo->query("SELECT * FROM sind.mes_corrente");

while($row = $query->fetch()) {

    $someArray[] = array_map("utf8_encode",$row);

}

echo json_encode($someArray);