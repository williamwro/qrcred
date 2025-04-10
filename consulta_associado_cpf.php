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
    $logFile = __DIR__ . '/logs/consulta_' . date('Y-m-d') . '.log';
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

// Capturar e validar parâmetro CPF
if (!isset($_GET['cpf']) || empty($_GET['cpf'])) {
    echo json_encode([
        'success' => false,
        'message' => 'CPF é obrigatório',
        'error' => 'missing_cpf'
    ]);
    exit;
}

$cpf = preg_replace('/\D/', '', $_GET['cpf']);

if (strlen($cpf) !== 11) {
    echo json_encode([
        'success' => false,
        'message' => 'CPF deve ter 11 dígitos',
        'error' => 'invalid_cpf'
    ]);
    exit;
}

try {
    escreverLog("Consulta por CPF: $cpf");
    
    // Conectar ao banco de dados
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se a tabela existe
    $stmt = $pdo->query("SELECT to_regclass('sind.associado_novo_app')");
    $tableExists = $stmt->fetchColumn();
    
    if (!$tableExists) {
        echo json_encode([
            'success' => true,
            'existe' => false,
            'message' => 'Associado não encontrado'
        ]);
        exit;
    }
    
    // Consultar por CPF
    $stmt = $pdo->prepare("SELECT * FROM sind.associado_novo_app WHERE cpf = :cpf");
    $stmt->bindParam(':cpf', $cpf);
    $stmt->execute();
    
    $associado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($associado) {
        // Formatar data
        if (isset($associado['nascimento']) && $associado['nascimento']) {
            $data = new DateTime($associado['nascimento']);
            $associado['nascimento_formatada'] = $data->format('d/m/Y');
        } else {
            $associado['nascimento_formatada'] = '';
        }
        
        echo json_encode([
            'success' => true,
            'existe' => true,
            'message' => 'Associado encontrado',
            'dados' => $associado
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'existe' => false,
            'message' => 'Associado não encontrado'
        ]);
    }
    
} catch (PDOException $e) {
    escreverLog("Erro PDO: " . $e->getMessage(), 'ERROR');
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao consultar associado',
        'error' => 'database_error',
        'details' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    escreverLog("Erro geral: " . $e->getMessage(), 'ERROR');
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar consulta',
        'error' => 'general_error',
        'details' => $e->getMessage()
    ]);
}