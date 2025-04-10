<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', true);
include "Adm/php/banco.php";

// Configurar arquivo de log
$log_file = __DIR__ . '/gerencia_codigo_debug.log';
function debug_log($message) {
    global $log_file;
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message" . PHP_EOL, FILE_APPEND);
}

// Iniciar log
debug_log("=============== NOVA SOLICITAÇÃO GERENCIA_CODIGO ===============");

// Cabeçalhos para permitir requisições de origens diferentes (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Verificar token de administração
$admin_token = isset($_POST['admin_token']) ? $_POST['admin_token'] : 
              (isset($_GET['admin_token']) ? $_GET['admin_token'] : '');

if ($admin_token !== 'chave_segura_123') {
    debug_log("Acesso negado: Token inválido");
    echo json_encode(["status" => "erro", "erro" => "Token inválido"]);
    exit;
}

// Obter operação
$operacao = isset($_POST['operacao']) ? $_POST['operacao'] : 
           (isset($_GET['operacao']) ? $_GET['operacao'] : '');

debug_log("Operação solicitada: $operacao");

try {
    // Conexão com o banco de dados
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    debug_log("Conexão com o banco estabelecida");

    // Verificar se a tabela existe e criar se necessário
    $sql_verificar_tabela = "SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'sind' 
        AND table_name = 'codigos_recuperacao'
    )";
    $stmt_verificar_tabela = $pdo->prepare($sql_verificar_tabela);
    $stmt_verificar_tabela->execute();
    $tabela_existe = $stmt_verificar_tabela->fetchColumn();
    
    if (!$tabela_existe) {
        debug_log("Tabela não encontrada. Criando tabela sind.codigos_recuperacao...");
        // Criar a tabela sind.codigos_recuperacao se não existir
        $sql_criar_tabela = "CREATE TABLE sind.codigos_recuperacao (
            id SERIAL PRIMARY KEY,
            cartao VARCHAR(20) NOT NULL,
            codigo VARCHAR(10) NOT NULL,
            metodo VARCHAR(10) NOT NULL,
            destino VARCHAR(100) NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT NOW(),
            usado BOOLEAN DEFAULT FALSE,
            expirado BOOLEAN DEFAULT FALSE
        )";
        
        $pdo->exec($sql_criar_tabela);
        
        // Criar índices para otimização
        $pdo->exec("CREATE INDEX idx_codigos_cartao ON sind.codigos_recuperacao(cartao)");
        $pdo->exec("CREATE INDEX idx_codigos_codigo ON sind.codigos_recuperacao(codigo)");
        
        debug_log("Tabela sind.codigos_recuperacao criada com sucesso");
    }

    // Processar operação
    switch ($operacao) {
        case 'inserir':
            $cartao = isset($_POST['cartao']) ? preg_replace('/\D/', '', $_POST['cartao']) : "";
            $codigo = isset($_POST['codigo']) ? $_POST['codigo'] : "";
            $metodo = isset($_POST['metodo']) ? $_POST['metodo'] : "email";
            $destino = isset($_POST['destino']) ? $_POST['destino'] : "desconhecido";
            
            debug_log("Inserindo código - Cartão: $cartao, Código: $codigo, Método: $metodo, Destino: $destino");
            
            if (empty($cartao) || empty($codigo)) {
                debug_log("Erro: cartão ou código não informados");
                echo json_encode(["status" => "erro", "erro" => "Cartão e código são obrigatórios"]);
                exit;
            }
            
            // Excluir códigos anteriores
            $sql_excluir = "DELETE FROM sind.codigos_recuperacao WHERE cartao = :cartao";
            $stmt_excluir = $pdo->prepare($sql_excluir);
            $stmt_excluir->bindParam(':cartao', $cartao);
            $stmt_excluir->execute();
            debug_log("Códigos anteriores excluídos");
            
            // Inserir novo código
            $sql_inserir = "INSERT INTO sind.codigos_recuperacao (cartao, codigo, metodo, destino, timestamp, usado, expirado)
                           VALUES (:cartao, :codigo, :metodo, :destino, NOW(), FALSE, FALSE)";
            $stmt_inserir = $pdo->prepare($sql_inserir);
            $stmt_inserir->bindParam(':cartao', $cartao);
            $stmt_inserir->bindParam(':codigo', $codigo);
            $stmt_inserir->bindParam(':metodo', $metodo);
            $stmt_inserir->bindParam(':destino', $destino);
            
            if ($stmt_inserir->execute()) {
                debug_log("Código inserido com sucesso");
                echo json_encode(["status" => "sucesso"]);
            } else {
                debug_log("Erro ao inserir código: " . implode(", ", $stmt_inserir->errorInfo()));
                echo json_encode(["status" => "erro", "erro" => "Falha ao inserir código"]);
            }
            break;
            
        case 'inserir_direto':
            $cartao = isset($_POST['cartao']) ? preg_replace('/\D/', '', $_POST['cartao']) : "";
            $codigo = isset($_POST['codigo']) ? $_POST['codigo'] : "";
            $metodo = isset($_POST['metodo']) ? $_POST['metodo'] : "email";
            $destino = isset($_POST['destino']) ? $_POST['destino'] : "desconhecido";
            
            debug_log("Inserindo código direto - Cartão: $cartao, Código: $codigo");
            
            if (empty($cartao) || empty($codigo)) {
                debug_log("Erro: cartão ou código não informados");
                echo json_encode(["status" => "erro", "erro" => "Cartão e código são obrigatórios"]);
                exit;
            }
            
            // Inserir novo código sem excluir os anteriores
            $sql_inserir = "INSERT INTO sind.codigos_recuperacao (cartao, codigo, metodo, destino, timestamp, usado, expirado)
                           VALUES (:cartao, :codigo, :metodo, :destino, NOW(), FALSE, FALSE)";
            $stmt_inserir = $pdo->prepare($sql_inserir);
            $stmt_inserir->bindParam(':cartao', $cartao);
            $stmt_inserir->bindParam(':codigo', $codigo);
            $stmt_inserir->bindParam(':metodo', $metodo);
            $stmt_inserir->bindParam(':destino', $destino);
            
            if ($stmt_inserir->execute()) {
                debug_log("Código inserido diretamente com sucesso");
                echo json_encode(["status" => "sucesso"]);
            } else {
                debug_log("Erro ao inserir código: " . implode(", ", $stmt_inserir->errorInfo()));
                echo json_encode(["status" => "erro", "erro" => "Falha ao inserir código"]);
            }
            break;
            
        case 'listar':
            debug_log("Listando códigos");
            $sql_listar = "SELECT * FROM sind.codigos_recuperacao ORDER BY id DESC";
            $stmt_listar = $pdo->prepare($sql_listar);
            $stmt_listar->execute();
            $codigos = $stmt_listar->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(["status" => "sucesso", "codigos" => $codigos]);
            break;
            
        default:
            debug_log("Operação inválida: $operacao");
            echo json_encode(["status" => "erro", "erro" => "Operação inválida"]);
    }
    
} catch (PDOException $e) {
    debug_log("Erro de banco de dados: " . $e->getMessage());
    echo json_encode(["status" => "erro", "erro" => $e->getMessage()]);
} catch (Exception $e) {
    debug_log("Erro geral: " . $e->getMessage());
    echo json_encode(["status" => "erro", "erro" => $e->getMessage()]);
}

debug_log("=============== FIM DO PROCESSAMENTO ===============");
?>