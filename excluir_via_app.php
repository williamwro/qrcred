<?php
// Cabeçalhos para permitir CORS e definir tipo de conteúdo
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
header("Content-type: application/json");

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'Adm/php/banco.php';
date_default_timezone_set('America/Sao_Paulo');

// Função para registrar logs
function log_message($message, $data = null) {
    $log_file = __DIR__ . '/estorno_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    
    if ($data !== null) {
        $log_message .= " " . json_encode($data);
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

log_message("Iniciando processamento de estorno");

// Conectar ao banco de dados
try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    log_message("Erro ao conectar ao banco de dados: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro de conexão com o banco de dados'
    ]);
    exit;
}

// Obter dados da requisição (suporta tanto JSON quanto POST regular)
$data = [];
$raw_input = file_get_contents('php://input');
log_message("Dados brutos recebidos:", $raw_input);

if (!empty($raw_input)) {
    // Tentar interpretar como JSON
    $json_data = json_decode($raw_input, true);
    if ($json_data !== null) {
        $data = $json_data;
        log_message("Dados JSON processados:", $data);
    }
}

// Se não tiver dados JSON, tentar $_POST
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
    log_message("Dados POST processados:", $data);
}

// Validação dos parâmetros obrigatórios
if (!isset($data['lancamento']) || 
    !isset($data['data']) || 
    !isset($data['empregador']) || 
    !isset($data['valor']) || 
    !isset($data['mes'])) {
    
    log_message("Parâmetros obrigatórios não fornecidos", $data);
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetros obrigatórios não fornecidos'
    ]);
    exit;
}

// Converter e formatar os dados
$lancamento = (int)$data['lancamento'];
$data_lancamento = $data['data'];
$empregador = (int)$data['empregador']; // Certifica que é um inteiro
$valor = $data['valor'];
$mes = $data['mes'];

log_message("Parâmetros processados:", [
    'lancamento' => $lancamento,
    'data' => $data_lancamento,
    'empregador' => $empregador,
    'valor' => $valor,
    'mes' => $mes
]);

try {
    // Verificar se o lançamento existe e confere com os parâmetros
    $sql = "SELECT lancamento 
            FROM sind.conta 
            WHERE lancamento = :lancamento 
            AND data = :data_lancamento
            AND empregador = :empregador 
            AND valor = :valor 
            AND mes = :mes";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':lancamento', $lancamento, PDO::PARAM_INT);
    $stmt->bindParam(':data_lancamento', $data_lancamento, PDO::PARAM_STR);
    $stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
    $stmt->bindParam(':valor', $valor, PDO::PARAM_STR);
    $stmt->bindParam(':mes', $mes, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        log_message("Dados do lançamento não conferem", [
            'lancamento' => $lancamento,
            'data' => $data_lancamento,
            'empregador' => $empregador,
            'valor' => $valor,
            'mes' => $mes
        ]);
        throw new Exception('Dados do lançamento não conferem');
    }

    // Verificar se o mês está bloqueado
    $sql = "SELECT mes FROM sind.controle WHERE mes = :mes";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':mes', $mes, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        log_message("Mês bloqueado para estorno: $mes");
        throw new Exception('Mês bloqueado para estorno');
    }

    // Iniciar transação
    $pdo->beginTransaction();
    log_message("Iniciando transação para estorno");

    // Deletar o lançamento
    $sql = "DELETE FROM sind.conta 
            WHERE lancamento = :lancamento 
            AND data = :data_lancamento
            AND empregador = :empregador 
            AND valor = :valor 
            AND mes = :mes";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':lancamento', $lancamento, PDO::PARAM_INT);
    $stmt->bindParam(':data_lancamento', $data_lancamento, PDO::PARAM_STR);
    $stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
    $stmt->bindParam(':valor', $valor, PDO::PARAM_STR);
    $stmt->bindParam(':mes', $mes, PDO::PARAM_STR);
    $stmt->execute();
    $qtde_deletados = $stmt->rowCount();

    if ($qtde_deletados === 0) {
        log_message("Nenhum registro excluído");
        throw new Exception('Erro ao estornar lançamento: nenhum registro afetado');
    }

    // Commit da transação
    $pdo->commit();
    log_message("Estorno realizado com sucesso: $qtde_deletados registro(s) excluído(s)");

    $result = [
        'success' => true,
        'message' => 'Lançamento estornado com sucesso',
        'data' => [
            'lancamento' => $lancamento,
            'data' => $data_lancamento,
            'empregador' => $empregador,
            'valor' => $valor,
            'mes' => $mes
        ]
    ];
    
    log_message("Resposta de sucesso:", $result);
    echo json_encode($result);

} catch (Exception $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        log_message("Rollback realizado");
    }

    $error_result = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    log_message("Erro durante o processo:", $error_result);
    echo json_encode($error_result);
}
?>