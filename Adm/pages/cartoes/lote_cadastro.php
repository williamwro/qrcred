<?PHP
header("Content-type: application/json");
require '../../php/banco.php';

$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$data2 = new DateTime();
$data = $data2->format('Y-m-d');
//$data4 = new DateTime($data3);
//$data = $data4->format('d/m/Y');

$divisao = $_POST['divisao'];

$sql_senha = $pdo->query("SELECT MAX(lote) AS lote FROM sind.lotes_cartao WHERE id_divisao = ".$divisao);
while($row = $sql_senha->fetch()) {
    $lote = (int)$row["lote"]+1;
}

$sql = "INSERT INTO sind.lotes_cartao(";
$sql .= "lote,data,id_divisao) ";
$sql .= "VALUES(:lote,:data,:id_divisao)";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':lote', $lote, PDO::PARAM_INT);
$stmt->bindParam(':data', $data, PDO::PARAM_STR);
$stmt->bindParam(':id_divisao', $divisao, PDO::PARAM_INT);
$stmt->execute();

$sql = "UPDATE sind.c_cartaoassociado SET data_pedido = :data, lote = :lote WHERE lote isnull AND id_divisao = :id_divisao";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':data', $data, PDO::PARAM_INT);
$stmt->bindParam(':lote', $lote, PDO::PARAM_STR);
$stmt->bindParam(':id_divisao', $divisao, PDO::PARAM_INT);

$stmt->execute();

$arr = array('divisao' =>$divisao);

echo json_encode($arr);

