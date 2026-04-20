<?php
// Configurações iniciais
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Se for uma requisição OPTIONS, terminar aqui (para CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Habilitar log de erros
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Função para log
function escreverLog($mensagem, $tipo = 'INFO') {
    $logFile = __DIR__ . '/logs/api_empregadores_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);

    // Criar diretório de logs se não existir
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Formatar mensagem
    $data = date('Y-m-d H:i:s');
    $mensagemLog = "[$data][$tipo] $mensagem\n";

    // Escrever no arquivo
    file_put_contents($logFile, $mensagemLog, FILE_APPEND);
}

// Incluir arquivo de conexão ao banco de dados
try {
    require_once 'Adm/php/banco.php';
} catch (Exception $e) {
    escreverLog("Erro ao incluir arquivo de conexão: " . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false,
        'message' => 'Erro de configuração do servidor',
        'error' => 'database_config_error'
    ]);
    exit;
}

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido',
        'error' => 'method_not_allowed'
    ]);
    exit;
}

// Buscar dados da tabela sind.empregador
try {
    // Conectar ao banco de dados
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consultar dados
    $stmt = $pdo->query("SELECT id, nome, responsavel, telefone, abreviacao, id_divisao FROM sind.empregador");
    $empregadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Registrar sucesso
    escreverLog("Consulta realizada com sucesso. Total de registros: " . count($empregadores));

    // Retornar resposta com os dados
    echo json_encode([
        'success' => true,
        'data' => $empregadores
    ]);

} catch (PDOException $e) {
    escreverLog("Erro PDO: " . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar dados',
        'error' => 'database_error',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    escreverLog("Erro geral: " . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar requisição',
        'error' => 'general_error',
        'details' => $e->getMessage()
    ]);
}
