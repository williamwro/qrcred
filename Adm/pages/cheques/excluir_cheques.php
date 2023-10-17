<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);
header("Content-type: application/json");
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = new stdClass();
$query = "DELETE FROM sind.pagamentos2 
           WHERE mes = :mes";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':mes', $_POST["mes"], PDO::PARAM_STR);
$stmt->execute();
$msg = 'excluido';
$arr = array('resultado'=>$msg);
$someArray = array_map("utf8_encode",$arr);
echo json_encode($someArray);