<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Só aceitar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Método não permitido. Use POST.'
    ]);
    exit();
}

try {
    // Incluir arquivo de conexão com banco
    include "Adm/php/banco.php";
    
    // Conectando ao banco de dados utilizando o PDO
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ler dados JSON da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validar dados obrigatórios
    if (!isset($data['codigo']) || empty($data['codigo']) ||
        !isset($data['nome']) || empty($data['nome']) ||
        !isset($data['celular']) || empty($data['celular'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Dados incompletos. Código, nome e celular são obrigatórios.'
        ]);
        exit();
    }
    
    $codigo = trim($data['codigo']);
    $nome = trim($data['nome']);
    $celular = trim($data['celular']);
    
    // Log da operação
    error_log("TENTATIVA DE ADESÃO - Código: $codigo, Nome: $nome, Celular: $celular");
    
    // 🔍 VERIFICAÇÃO PRÉVIA: Verificar se já existe
    $sqlVerifica = "SELECT id, codigo, nome, celular, data_hora, autorizado 
                    FROM sind.associados_sasmais 
                    WHERE codigo = :codigo 
                    LIMIT 1";
    
    $stmtVerifica = $pdo->prepare($sqlVerifica);
    $stmtVerifica->bindParam(':codigo', $codigo, PDO::PARAM_STR);
    $stmtVerifica->execute();
    
    $existente = $stmtVerifica->fetch(PDO::FETCH_ASSOC);
    
    if ($existente) {
        // Já existe - retornar erro
        error_log("DUPLICAÇÃO EVITADA - Código $codigo já existe (ID: {$existente['id']})");
        http_response_code(409); // Conflict
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Associado já aderiu ao Sascred anteriormente.',
            'codigo_existente' => $existente['codigo'],
            'data_adesao' => $existente['data_hora']
        ]);
        exit();
    }
    
    // 🔒 INICIAR TRANSAÇÃO para garantir atomicidade
    $pdo->beginTransaction();
    
    try {
        // 🔍 VERIFICAÇÃO DUPLA dentro da transação (para casos de concorrência extrema)
        $sqlVerifica2 = "SELECT id FROM sind.associados_sasmais WHERE codigo = :codigo FOR UPDATE";
        $stmtVerifica2 = $pdo->prepare($sqlVerifica2);
        $stmtVerifica2->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmtVerifica2->execute();
        
        if ($stmtVerifica2->fetch()) {
            // Encontrou durante a transação - fazer rollback
            $pdo->rollBack();
            error_log("DUPLICAÇÃO EVITADA NA TRANSAÇÃO - Código $codigo");
            http_response_code(409);
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Associado já foi processado por requisição simultânea.'
            ]);
            exit();
        }
        
        // ✅ INSERIR novo registro
        $sqlInsert = "INSERT INTO sind.associados_sasmais (codigo, nome, celular, data_hora, autorizado) 
                      VALUES (:codigo, :nome, :celular, NOW(), false)";
        
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmtInsert->bindParam(':nome', $nome, PDO::PARAM_STR);
        $stmtInsert->bindParam(':celular', $celular, PDO::PARAM_STR);
        
        $resultado = $stmtInsert->execute();
        
        if ($resultado) {
            // Obter ID do registro inserido
            $novoId = $pdo->lastInsertId();
            
            // Confirmar transação
            $pdo->commit();
            
            error_log("ADESÃO REALIZADA COM SUCESSO - ID: $novoId, Código: $codigo");
            
            // Retornar sucesso
            echo json_encode([
                'status' => 'sucesso',
                'mensagem' => 'Adesão realizada com sucesso.',
                'id' => $novoId,
                'codigo' => $codigo,
                'nome' => $nome,
                'celular' => $celular,
                'data_hora' => date('Y-m-d H:i:s')
            ]);
        } else {
            throw new Exception('Erro ao inserir registro na tabela.');
        }
        
    } catch (PDOException $e) {
        // Fazer rollback em caso de erro
        $pdo->rollBack();
        
        // Verificar se é erro de constraint única
        if (strpos($e->getMessage(), 'uk_associados_sasmais_codigo') !== false || 
            strpos($e->getMessage(), 'duplicate key') !== false ||
            strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
            
            error_log("CONSTRAINT ÚNICA VIOLADA - Código: $codigo");
            http_response_code(409);
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Associado já aderiu ao Sascred (constraint única).',
                'tipo_erro' => 'duplicacao'
            ]);
        } else {
            // Outro erro de banco
            error_log("ERRO DE BANCO NA TRANSAÇÃO - Código: $codigo, Erro: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Erro de banco de dados.',
                'erro_detalhes' => $e->getMessage()
            ]);
        }
        exit();
    }
    
} catch (PDOException $e) {
    // Erro de conexão ou preparação
    error_log("ERRO DE CONEXÃO - " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro de conexão com banco de dados.',
        'erro_detalhes' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // Erro geral
    error_log("ERRO GERAL - " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro interno do servidor.',
        'erro_detalhes' => $e->getMessage()
    ]);
    
} finally {
    // Fechar conexão
    $pdo = null;
}
?>