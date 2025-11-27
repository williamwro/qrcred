<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = "SELECT id, nome FROM sind.grupos ORDER BY nome";
$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();

$grupos = array();
foreach ($result as $row) {
    $grupo = array();
    $grupo["id"] = $row["id"];
    $grupo["nome"] = is_string($row["nome"]) ? (mb_check_encoding($row["nome"], 'UTF-8') ? $row["nome"] : mb_convert_encoding($row["nome"], 'UTF-8', 'ISO-8859-1')) : $row["nome"];
    $grupos[] = $grupo;
}

echo json_encode($grupos, JSON_UNESCAPED_UNICODE);
?>
