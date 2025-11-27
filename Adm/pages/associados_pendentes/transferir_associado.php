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

// Incluir arquivo de conexão ao banco de dados
try {
    require_once '../../php/banco.php';
} catch (Exception $e) {
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
    // Capturar ID do registro pendente
    $id_pendente = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id_pendente <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID do registro é obrigatório',
            'error' => 'missing_id'
        ]);
        exit;
    }
    
    // Conectar ao banco de dados
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar dados do registro pendente
    $stmt = $pdo->prepare("SELECT * FROM sind.associado_novo_app WHERE id = :id");
    $stmt->bindParam(':id', $id_pendente);
    $stmt->execute();
    $dadosPendente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dadosPendente) {
        echo json_encode([
            'success' => false,
            'message' => 'Registro pendente não encontrado',
            'error' => 'record_not_found'
        ]);
        exit;
    }
    
    // Verificar se CPF já existe na tabela definitiva
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sind.associado WHERE cpf = :cpf");
    $stmt->bindParam(':cpf', $dadosPendente['cpf']);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Já existe um usuário cadastrado com o CPF: ' . $dadosPendente['cpf'],
            'error' => 'duplicate_cpf',
            'cpf' => $dadosPendente['cpf']
        ]);
        exit;
    }
    
    // Preparar dados para inserção na tabela definitiva
    $dataAtual = date('Y-m-d H:i:s');
    
    // Inserir na tabela definitiva
    $sql = "INSERT INTO sind.associado (
        codigo, nome, endereco, numero, nascimento, empregador, cep, telres, telcom, cel, 
        bairro, complemento, cidade, rg, cpf, email, uf, data_filiacao, filiado, id_situacao, tipo, id_divisao
    ) VALUES (
        :codigo, :nome, :endereco, :numero, :nascimento, :empregador, :cep, :telres, :telcom, :cel,
        :bairro, :complemento, :cidade, :rg, :cpf, :email, :uf, :data_filiacao, :filiado, :id_situacao, :tipo, :id_divisao
    ) RETURNING id";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind de parâmetros
    $stmt->bindParam(':codigo', $dadosPendente['codigo']);
    $stmt->bindParam(':nome', $dadosPendente['nome']);
    $stmt->bindParam(':endereco', $dadosPendente['endereco']);
    $stmt->bindParam(':numero', $dadosPendente['numero']);
    $stmt->bindParam(':nascimento', $dadosPendente['nascimento']);
    $stmt->bindParam(':empregador', $dadosPendente['empregador']);
    $stmt->bindParam(':cep', $dadosPendente['cep']);
    $stmt->bindParam(':telres', $dadosPendente['telres']);
    $stmt->bindParam(':telcom', $dadosPendente['telcom']);
    $stmt->bindParam(':cel', $dadosPendente['cel']);
    $stmt->bindParam(':bairro', $dadosPendente['bairro']);
    $stmt->bindParam(':complemento', $dadosPendente['complemento']);
    $stmt->bindParam(':cidade', $dadosPendente['cidade']);
    $stmt->bindParam(':rg', $dadosPendente['rg']);
    $stmt->bindParam(':cpf', $dadosPendente['cpf']);
    $stmt->bindParam(':email', $dadosPendente['email']);
    $stmt->bindParam(':uf', $dadosPendente['uf']);
    $stmt->bindParam(':data_filiacao', $dataAtual);
    
    // Definir valores padrão
    $filiado = true;
    $id_situacao = 1;
    $tipo = 1;
    $id_divisao = 1;
    
    $stmt->bindParam(':filiado', $filiado, PDO::PARAM_BOOL);
    $stmt->bindParam(':id_situacao', $id_situacao);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->bindParam(':id_divisao', $id_divisao);
    
    // Executar e obter ID
    $stmt->execute();
    $novoId = $stmt->fetchColumn();
    
    // Remover da tabela de pendentes
    $stmt = $pdo->prepare("DELETE FROM sind.associado_novo_app WHERE id = :id");
    $stmt->bindParam(':id', $id_pendente);
    $stmt->execute();
    
    // Retornar resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Associado transferido com sucesso para o cadastro definitivo!',
        'data' => [
            'novo_id' => $novoId,
            'nome' => $dadosPendente['nome'],
            'cpf' => $dadosPendente['cpf']
        ]
    ]);
    
} catch (PDOException $e) {
    $errorMessage = 'Erro ao processar transferência';
    $errorCode = 'database_error';
    
    // Tratar erro de chave duplicada especificamente
    if (strpos($e->getMessage(), 'unique constraint') !== false || 
        strpos($e->getMessage(), 'Unique violation') !== false) {
        $errorMessage = 'CPF já cadastrado no sistema definitivo';
        $errorCode = 'duplicate_cpf';
    }
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage,
        'error' => $errorCode,
        'details' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar transferência',
        'error' => 'general_error',
        'details' => $e->getMessage()
    ]);
}
?>
