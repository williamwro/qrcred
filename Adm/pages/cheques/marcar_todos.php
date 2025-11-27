<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);
header("Content-type: application/json");
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$marcar = $_POST["marcar"];
$categoria = $_POST["categoria"];
if($categoria === "") {//todas as categorias
    $sql = "";
}else{
    $sql = " AND id_categoria_recibo = :categoria";
}
$stmt = new stdClass();
$query = "UPDATE sind.pagamentos2
             SET prtch = :marcar
           WHERE mes = :mes ".$sql;
$stmt = $pdo->prepare($query);
$stmt->bindParam(':mes', $_POST["mes"], PDO::PARAM_STR);
$stmt->bindParam(':marcar', $marcar, PDO::PARAM_BOOL);
if($categoria != "") {
    $stmt->bindParam(':categoria', $categoria, PDO::PARAM_INT);
}
$stmt->execute();
if($marcar === "true"){
    $msg = 'marcado';
}else{
    $msg = 'desmarcado';
}

$arr = array('resultado'=>$msg);
$someArray = array_map(function($value) {
    return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
}, $arr);
echo json_encode($someArray);