<?php
// Configurações iniciais
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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
    $logFile = __DIR__ . '/logs/cadastro_' . date('Y-m-d') . '.log';
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido',
        'error' => 'method_not_allowed'
    ]);
    exit;
}

// Capturar e processar dados
try {
    // Registrar requisição
    escreverLog("Requisição recebida: " . json_encode($_POST));
    
    // Capturar campos obrigatórios
    $nome = isset($_POST['C_nome_assoc']) ? trim($_POST['C_nome_assoc']) : '';
    $cpf = isset($_POST['C_cpf_assoc']) ? preg_replace('/\D/', '', $_POST['C_cpf_assoc']) : '';
    $email = isset($_POST['C_Email_assoc']) ? trim($_POST['C_Email_assoc']) : '';
    $celular = isset($_POST['C_cel_assoc']) ? preg_replace('/\D/', '', $_POST['C_cel_assoc']) : '';
    
    // Validações
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "Nome é obrigatório";
    }
    
    if (empty($cpf)) {
        $erros[] = "CPF é obrigatório";
    } elseif (strlen($cpf) !== 11) {
        $erros[] = "CPF deve ter 11 dígitos";
    }
    
    if (empty($email)) {
        $erros[] = "E-mail é obrigatório";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "E-mail inválido";
    }
    
    if (empty($celular)) {
        $erros[] = "Celular é obrigatório";
    }
    
    if (!empty($erros)) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro de validação',
            'errors' => $erros
        ]);
        exit;
    }
    
    // Capturar campos opcionais
    $rg = isset($_POST['C_rg_assoc']) ? trim($_POST['C_rg_assoc']) : '';
    $nascimento = isset($_POST['C_nascimento']) ? trim($_POST['C_nascimento']) : '';
    $telefoneRes = isset($_POST['C_telres']) ? trim($_POST['C_telres']) : '';
    $cep = isset($_POST['C_cep_assoc']) ? trim($_POST['C_cep_assoc']) : '';
    $endereco = isset($_POST['C_endereco_assoc']) ? trim($_POST['C_endereco_assoc']) : '';
    $numero = isset($_POST['C_numero_assoc']) ? trim($_POST['C_numero_assoc']) : '';
    $complemento = isset($_POST['C_complemento_assoc']) ? trim($_POST['C_complemento_assoc']) : '';
    $bairro = isset($_POST['C_bairro_assoc']) ? trim($_POST['C_bairro_assoc']) : '';
    $cidade = isset($_POST['C_cidade_assoc']) ? trim($_POST['C_cidade_assoc']) : '';
    $uf = isset($_POST['C_uf_assoc']) ? trim($_POST['C_uf_assoc']) : '';
    
    // Formatar data
    $nascimentoFormatado = null;
    if (!empty($nascimento)) {
        $partes = explode('/', $nascimento);
        if (count($partes) === 3) {
            $nascimentoFormatado = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
        }
    }
    
    // Conectar ao banco de dados
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se CPF já existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sind.associado_novo_app WHERE cpf = :cpf");
    $stmt->bindParam(':cpf', $cpf);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'CPF já cadastrado no sistema',
            'error' => 'duplicate_cpf'
        ]);
        exit;
    }
    
    // Criar tabela se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS sind.associado_novo_app (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(50),
        endereco VARCHAR(50),
        numero VARCHAR(25),
        nascimento TIMESTAMP,
        cep VARCHAR(9),
        telres VARCHAR(13),
        telcom VARCHAR(13),
        cel VARCHAR(15),
        bairro VARCHAR(60),
        complemento VARCHAR(20),
        cidade VARCHAR(50),
        rg VARCHAR(20),
        cpf VARCHAR(14) UNIQUE,
        email VARCHAR(50),
        uf VARCHAR(2)
    )");
    
    // Preparar SQL
    $sql = "INSERT INTO sind.associado_novo_app (
        nome, cpf, rg, nascimento, email, cel, telres,
        cep, endereco, numero, complemento, bairro, cidade, uf
    ) VALUES (
        :nome, :cpf, :rg, :nascimento, :email, :cel, :telres,
        :cep, :endereco, :numero, :complemento, :bairro, :cidade, :uf
    ) RETURNING id";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind de parâmetros
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->bindParam(':rg', $rg);
    $stmt->bindParam(':nascimento', $nascimentoFormatado);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':cel', $celular);
    $stmt->bindParam(':telres', $telefoneRes);
    $stmt->bindParam(':cep', $cep);
    $stmt->bindParam(':endereco', $endereco);
    $stmt->bindParam(':numero', $numero);
    $stmt->bindParam(':complemento', $complemento);
    $stmt->bindParam(':bairro', $bairro);
    $stmt->bindParam(':cidade', $cidade);
    $stmt->bindParam(':uf', $uf);
    
    // Executar e obter ID
    $stmt->execute();
    $id = $stmt->fetchColumn();
    
    // Registrar sucesso
    escreverLog("Cadastro realizado com sucesso. ID: $id, Nome: $nome, CPF: $cpf");
    
    // Retornar resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Cadastro realizado com sucesso!',
        'data' => [
            'id' => $id,
            'nome' => $nome,
            'cpf' => $cpf
        ]
    ]);
    
} catch (PDOException $e) {
    escreverLog("Erro PDO: " . $e->getMessage(), 'ERROR');
    
    $errorMessage = 'Erro ao processar cadastro';
    $errorCode = 'database_error';
    
    // Tratar erro de chave duplicada especificamente
    if (strpos($e->getMessage(), 'unique constraint') !== false || 
        strpos($e->getMessage(), 'Unique violation') !== false) {
        $errorMessage = 'CPF já cadastrado no sistema';
        $errorCode = 'duplicate_cpf';
    }
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage,
        'error' => $errorCode,
        'details' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    escreverLog("Erro geral: " . $e->getMessage(), 'ERROR');
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar cadastro',
        'error' => 'general_error',
        'details' => $e->getMessage()
    ]);
}