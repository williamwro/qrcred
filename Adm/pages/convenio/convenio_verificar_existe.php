<?PHP
require_once '../../../functions.php';
ini_set('display_errors', true);
error_reporting(E_ALL);
require '../../php/banco.php';
include "../../php/funcoes.php";

$cod_convenio = isset($_POST['cod_convenio']) ? (int)$_POST['cod_convenio'] : 0;

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$response = array();

try {
    $sql = "SELECT COUNT(*) as total FROM sind.convenio WHERE codigo = :cod_convenio";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cod_convenio', $cod_convenio, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($result['total'] > 0) {
        $response['existe'] = true;
        $response['codigo'] = $cod_convenio;
    } else {
        $response['existe'] = false;
        $response['codigo'] = $cod_convenio;
    }
    
} catch (PDOException $erro) {
    $response['existe'] = false;
    $response['erro'] = $erro->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?> 