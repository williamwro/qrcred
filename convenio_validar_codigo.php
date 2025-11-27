<?php
//convenio_validar_codigo.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');


// Configurações de depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);


// Início do log de execução
function escreverLog($mensagem, $tipo = 'INFO') {
    $dataHora = date('Y-m-d H:i:s');
    $diretorioLog = './logs';
    
    if (!file_exists($diretorioLog)) {
        mkdir($diretorioLog, 0775, true);
        error_log("[$dataHora][SISTEMA] Diretório de logs criado: $diretorioLog");
    }
    
    $arquivoLog = $diretorioLog . '/recuperacao_senha_convenio_' . date('Y-m-d') . '.log';
    $textoLog = "[$dataHora][$tipo] $mensagem" . PHP_EOL;
    file_put_contents($arquivoLog, $textoLog, FILE_APPEND);
    
    // Duplicar logs críticos no error_log do PHP
    if ($tipo == 'ERRO' || $tipo == 'DEBUG_CRITICO') {
        error_log("[$tipo] $mensagem");
    }
}


// Log de início da execução
escreverLog("=== INÍCIO DA VALIDAÇÃO DE CÓDIGO ===", 'DEBUG');
escreverLog("Método da requisição: " . $_SERVER['REQUEST_METHOD'], 'DEBUG');
escreverLog("User Agent: " . $_SERVER['HTTP_USER_AGENT'], 'DEBUG');
escreverLog("IP Remoto: " . $_SERVER['REMOTE_ADDR'], 'DEBUG');


// Se for uma solicitação OPTIONS, retorne apenas os cabeçalhos
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    escreverLog("Requisição OPTIONS recebida, retornando headers CORS", 'DEBUG');
    http_response_code(200);
    exit;
}


// Obter dados com base no método da requisição
$dados = [];
$rawInput = file_get_contents('php://input');


escreverLog("Raw input recebido: " . $rawInput, 'DEBUG');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Primeiro, tentar obter dados do JSON
    if (!empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if ($jsonData !== null) {
            $dados = $jsonData;
            escreverLog("Dados recebidos via JSON (decodificados com sucesso): " . json_encode($dados), 'DEBUG');
        } else {
            escreverLog("Falha ao decodificar JSON. Erro: " . json_last_error_msg(), 'ERRO');
            escreverLog("JSON recebido: " . $rawInput, 'DEBUG_CRITICO');
        }
    } else {
        escreverLog("Nenhum dado raw recebido", 'DEBUG');
    }
    
    // Se não conseguir obter por JSON, tentar $_POST
    if (empty($dados) && !empty($_POST)) {
        $dados = $_POST;
        escreverLog("Dados recebidos via POST: " . json_encode($dados), 'DEBUG');
    }
}

// Verificar se temos os dados necessários
if (empty($dados)) {
    escreverLog("Nenhum dado recebido em formato reconhecível", 'ERRO');
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido']);
    exit;
}

if (!isset($dados['email']) || !isset($dados['codigo'])) {
    escreverLog("Parâmetros obrigatórios ausentes: " . json_encode($dados), 'ERRO');
    echo json_encode(['success' => false, 'message' => 'Parâmetros "email" e "codigo" são obrigatórios']);
    exit;
}

$email = trim($dados['email']);
$codigo = trim($dados['codigo']);

escreverLog("Validando código '$codigo' para email '$email'", 'INFO');

try {
    escreverLog("Tentando incluir arquivo de conexão com o banco", 'DEBUG');
    
    // Verificar se o arquivo existe
    if (!file_exists("Adm/php/banco.php")) {
        escreverLog("ERRO CRÍTICO: Arquivo banco.php não encontrado", 'ERRO');
        escreverLog("Diretório atual: " . getcwd(), 'DEBUG_CRITICO');
        escreverLog("Conteúdo do diretório: " . implode(", ", scandir(".")), 'DEBUG_CRITICO');
        echo json_encode(['success' => false, 'message' => 'Erro de configuração no servidor']);
        exit;
    }
    
    // Conexão com o banco de dados 
    include "Adm/php/banco.php";
    
    escreverLog("Arquivo banco.php incluído com sucesso", 'DEBUG');
    escreverLog("Tentando conectar ao banco PostgreSQL", 'DEBUG');
    
    $pdo = Banco::conectar_postgres();
    if (!$pdo) {
        escreverLog("ERRO CRÍTICO: Falha ao obter conexão com o banco", 'ERRO');
        echo json_encode(['success' => false, 'message' => 'Falha na conexão com o banco de dados']);
        exit;
    }
    
    escreverLog("Conexão com banco PostgreSQL estabelecida com sucesso", 'DEBUG');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar convênio pelo email para validar se existe
    $query = "SELECT sc.*, c.email, c.razaosocial FROM sind.c_senhaconvenio sc 
              INNER JOIN sind.convenio c ON sc.cod_convenio = c.codigo
              WHERE c.email = :email";
              
    escreverLog("Executando consulta para buscar convênio por email: $query", 'DEBUG');
    escreverLog("Parâmetro email: $email", 'DEBUG');
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        escreverLog("Consulta executada com sucesso", 'DEBUG');
        $convenio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($convenio) {
            escreverLog("Convênio encontrado: " . json_encode(array_intersect_key($convenio, array_flip(['usuario_texto', 'email', 'razaosocial']))), 'DEBUG');
        } else {
            escreverLog("Convênio não encontrado para email: $email", 'ERRO');
            echo json_encode(['success' => false, 'message' => 'Email não encontrado']);
            exit;
        }
    } catch (PDOException $e) {
        escreverLog("Erro ao buscar convênio: " . $e->getMessage(), 'ERRO');
        escreverLog("Stack trace: " . $e->getTraceAsString(), 'DEBUG_CRITICO');
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar convênio']);
        exit;
    }
    
    // Buscar código
    $query = "SELECT * FROM sind.codigos_recuperacao_convenio 
              WHERE codigo = :codigo 
              AND identificador = :email 
              AND data_expiracao > NOW() 
              AND utilizado = FALSE";
              
    escreverLog("Executando consulta para buscar código: $query", 'DEBUG');
    escreverLog("Parâmetros: codigo=$codigo, email=$email", 'DEBUG');
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $codigoRecuperacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($codigoRecuperacao) {
            escreverLog("Código encontrado e válido: " . json_encode($codigoRecuperacao), 'INFO');
            
            // Verificar data de expiração
            $dataExpiracao = new DateTime($codigoRecuperacao['data_expiracao']);
            $agora = new DateTime();
            
            escreverLog("Data de expiração: " . $dataExpiracao->format('Y-m-d H:i:s'), 'DEBUG');
            escreverLog("Data atual: " . $agora->format('Y-m-d H:i:s'), 'DEBUG');
            
            if ($dataExpiracao < $agora) {
                escreverLog("Código expirado: Expira em " . $dataExpiracao->format('Y-m-d H:i:s'), 'ERRO');
                echo json_encode(['success' => false, 'message' => 'Código expirado. Solicite um novo código.']);
                exit;
            }
        } else {
            // Consultar para depuração
            $queryDebug = "SELECT * FROM sind.codigos_recuperacao_convenio WHERE identificador = :email";
            $stmtDebug = $pdo->prepare($queryDebug);
            $stmtDebug->bindParam(':email', $email);
            $stmtDebug->execute();
            $codigosEmail = $stmtDebug->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($codigosEmail) > 0) {
                escreverLog("Códigos encontrados para o email mas inválidos: " . json_encode($codigosEmail), 'DEBUG');
                foreach ($codigosEmail as $cod) {
                    escreverLog("Código: " . $cod['codigo'] . 
                             ", Expiração: " . $cod['data_expiracao'] . 
                             ", Utilizado: " . ($cod['utilizado'] ? 'Sim' : 'Não'), 'DEBUG');
                }
            } else {
                escreverLog("Nenhum código encontrado para o email: $email", 'DEBUG');
            }
            
            escreverLog("Código inválido ou expirado: $codigo para email $email", 'ERRO');
            echo json_encode(['success' => false, 'message' => 'Código inválido ou expirado']);
            exit;
        }
    } catch (PDOException $e) {
        escreverLog("Erro ao buscar código: " . $e->getMessage(), 'ERRO');
        escreverLog("Stack trace: " . $e->getTraceAsString(), 'DEBUG_CRITICO');
        echo json_encode(['success' => false, 'message' => 'Erro ao validar código']);
        exit;
    }
    
    // Gerar token para próxima etapa - CORREÇÃO: usar milissegundos para compatibilidade com o JavaScript
    $timestamp = time() * 1000; // Multiplicar por 1000 para converter para milissegundos (formato JavaScript)
    $tokenString = "$email:$codigo:$timestamp";
    $token = base64_encode($tokenString);
    
    // Logs detalhados do token e timestamp para depuração
    escreverLog("Gerando token com timestamp: $timestamp", 'DEBUG');
    escreverLog("Timestamp em formato legível: " . date('Y-m-d H:i:s', $timestamp/1000), 'DEBUG');
    escreverLog("Timestamp expira em: " . date('Y-m-d H:i:s', ($timestamp/1000) + 7200), 'DEBUG'); // +2 horas
    escreverLog("Token gerado: $token", 'DEBUG');
    escreverLog("Código validado com sucesso para email: $email", 'INFO');
    
    echo json_encode([
        'success' => true,
        'message' => 'Código validado com sucesso',
        'token' => $token
    ]);
    
} catch (PDOException $e) {
    escreverLog("ERRO CRÍTICO PDOException: " . $e->getMessage(), 'ERRO');
    escreverLog("Código do erro: " . $e->getCode(), 'DEBUG_CRITICO');
    escreverLog("Stack trace: " . $e->getTraceAsString(), 'DEBUG_CRITICO');
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno no servidor',
        'debug' => [
            'code' => $e->getCode(),
            'message' => $e->getMessage()
        ]
    ]);
} catch (Exception $e) {
    escreverLog("ERRO CRÍTICO Exception: " . $e->getMessage(), 'ERRO');
    escreverLog("Código do erro: " . $e->getCode(), 'DEBUG_CRITICO');
    escreverLog("Stack trace: " . $e->getTraceAsString(), 'DEBUG_CRITICO');
    echo json_encode([
        'success' => false, 
        'message' => 'Erro inesperado no servidor',
        'debug' => [
            'code' => $e->getCode(),
            'message' => $e->getMessage()
        ]
    ]);
} finally {
    escreverLog("=== FIM DA VALIDAÇÃO DE CÓDIGO ===", 'DEBUG');
}
?>
