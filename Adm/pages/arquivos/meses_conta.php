<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$origem = $_GET['origem'];
$divisao = $_GET['divisao'];
$someArray = array();
$i=0;
$row = $pdo->query( "SELECT abreviacao FROM sind.mes_corrente WHERE id_divisao = ".$divisao )->fetch();
$someArray[$i]["mes_corrente"] = $row["abreviacao"];
if($origem === "admin"){
    $sql = "SELECT * FROM sind.meses_conta WHERE status_admin = 1 AND divisao = ? ORDER BY data";
}elseif($origem === "convenio"){
    $sql = "SELECT * FROM sind.meses_conta WHERE status_convenio = 1 AND divisao = ? ORDER BY data";
}elseif($origem === "relatorio"){
    $sql = "SELECT * FROM sind.meses_conta WHERE status_relatorio = 1 AND divisao = ? ORDER BY data";
}
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