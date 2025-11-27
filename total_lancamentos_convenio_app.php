<?PHP
// Permitir acesso de qualquer origem
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json");

ini_set('display_errors', true);
error_reporting(E_ALL);

include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$response = new stdClass();

try {
    // Obter parâmetros
    $convenio = isset($_POST['convenio']) ? intval($_POST['convenio']) : (isset($_GET['convenio']) ? intval($_GET['convenio']) : null);
    $mes = isset($_POST['mes']) ? $_POST['mes'] : (isset($_GET['mes']) ? $_GET['mes'] : null);

    if (!$convenio || !$mes) {
        $response->success = false;
        $response->message = "Parâmetros convenio e mes são obrigatórios";
        $response->total_lancamentos = 0;
        echo json_encode($response);
        exit;
    }

    // SQL para contar total de lançamentos
    $sql = "SELECT COUNT(*) as total_lancamentos
            FROM sind.conta 
            WHERE convenio = :convenio
            AND mes = :mes";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':convenio', $convenio, PDO::PARAM_INT);
    $stmt->bindParam(':mes', $mes, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->success = true;
    $response->total_lancamentos = intval($result['total_lancamentos']);
    $response->convenio = $convenio;
    $response->mes = $mes;

} catch (Exception $e) {
    error_log("Erro em total_lancamentos_convenio_app.php: " . $e->getMessage());
    $response->success = false;
    $response->message = "Erro ao consultar total de lançamentos";
    $response->total_lancamentos = 0;
}

echo json_encode($response);
?>
