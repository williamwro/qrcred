<?PHP
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', true);
require 'Adm/php/banco.php';
include "Adm/php/funcoes.php";

$_codigo   = isset($_POST['codigo']) ? (int)$_POST['codigo'] : 0;
$latitude  = isset($_POST['latitude']) ?  $_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ?  $_POST['longitude'] : null;

$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";

$sql = "UPDATE sind.convenio SET ";
$sql .= "latitude = :latitude, ";
$sql .= "longitude = :longitude ";
$sql .= "WHERE codigo = :codigo";

$msg_grava_cad = "aualizado";

try {

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':codigo', $_codigo, PDO::PARAM_INT);
    $stmt->bindParam(':latitude', $latitude, PDO::PARAM_STR);
    $stmt->bindParam(':longitude', $longitude, PDO::PARAM_STR);

    $stmt->execute();

    echo $msg_grava_cad;

} catch (PDOException $erro) {
    echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();

}
