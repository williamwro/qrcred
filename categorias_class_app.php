<?PHP
header("Cache-Control: no-cache, no-store, must-revalidate"); // limpa o cache
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
clearstatcache(); // limpa o cache

include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$i=0;
$sql = $pdo->query("SELECT codigo, nome FROM sind.categoriaconvenio ORDER BY nome asc;");
while($row = $sql->fetch()) {
    $someArray[$i] = array_map("utf8_encode",$row);
    $i++;
}
//print_r($someArray);

echo json_encode($someArray);