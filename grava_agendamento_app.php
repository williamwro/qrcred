<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Incluir arquivo de conexão com banco (ajuste o caminho conforme sua estrutura)
require_once 'Adm/php/banco.php';

try {
    // Obter dados do POST
    $cod_associado = $_POST['cod_associado'] ?? '';
    $id_empregador = $_POST['id_empregador'] ?? '';
    $cod_convenio = $_POST['cod_convenio'] ?? '1';
    $data_solicitacao = $_POST['data_solicitacao'] ?? date('Y-m-d H:i:s');
    $status = $_POST['status'] ?? '1';
    $profissional = $_POST['profissional'] ?? '';
    $especialidade = $_POST['especialidade'] ?? '';
    $convenio_nome = $_POST['convenio_nome'] ?? '';
    $data_agendada = $_POST['data_agendada'] ?? null;
    
    // Log da requisição
    error_log("📋 AGENDAMENTO REQUEST: cod_associado={$cod_associado}, empregador={$id_empregador}, profissional={$profissional}, timestamp=" . date('Y-m-d H:i:s'));
    
    // Validar dados obrigatórios
    if (empty($cod_associado) || empty($id_empregador)) {
        throw new Exception('Código do associado e ID do empregador são obrigatórios');
    }
    
    // Conectar ao banco PostgreSQL
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 🛡️ PROTEÇÃO ANTI-DUPLICAÇÃO: Verificar se já existe agendamento recente idêntico
    $sql_check = "SELECT id, data_solicitacao 
                  FROM sind.agendamento 
                  WHERE cod_associado = :cod_associado 
                  AND id_empregador = :id_empregador 
                  AND cod_convenio = :cod_convenio
                  AND data_solicitacao >= NOW() - INTERVAL '5 minutes'
                  ORDER BY data_solicitacao DESC 
                  LIMIT 1";
    
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([
        ':cod_associado' => $cod_associado,
        ':id_empregador' => $id_empregador,
        ':cod_convenio' => $cod_convenio
    ]);
    
    $agendamento_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if ($agendamento_existente) {
        error_log("🚫 DUPLICAÇÃO EVITADA: Agendamento já existe ID={$agendamento_existente['id']}, data={$agendamento_existente['data_solicitacao']}");
        
        // Retornar o agendamento existente como se fosse novo (idempotência)
        echo json_encode([
            'success' => true,
            'message' => 'Agendamento já existe (duplicação evitada)',
            'data' => [
                'id' => $agendamento_existente['id'],
                'cod_associado' => $cod_associado,
                'id_empregador' => $id_empregador,
                'cod_convenio' => $cod_convenio,
                'data_solicitacao' => $agendamento_existente['data_solicitacao'],
                'status' => $status,
                'profissional' => $profissional,
                'especialidade' => $especialidade,
                'convenio_nome' => $convenio_nome,
                'duplicate_prevented' => true
            ]
        ]);
        exit();
    }
    
    // 🔒 LOCK para prevenir race conditions
    $pdo->beginTransaction();
    
    try {
        // Verificação dupla dentro da transação
        $stmt_check2 = $pdo->prepare($sql_check);
        $stmt_check2->execute([
            ':cod_associado' => $cod_associado,
            ':id_empregador' => $id_empregador,
            ':cod_convenio' => $cod_convenio
        ]);
        
        $agendamento_existente2 = $stmt_check2->fetch(PDO::FETCH_ASSOC);
        
        if ($agendamento_existente2) {
            $pdo->rollBack();
            error_log("🚫 DUPLICAÇÃO EVITADA (2ª verificação): ID={$agendamento_existente2['id']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Agendamento já existe (2ª verificação)',
                'data' => [
                    'id' => $agendamento_existente2['id'],
                    'cod_associado' => $cod_associado,
                    'id_empregador' => $id_empregador,
                    'cod_convenio' => $cod_convenio,
                    'data_solicitacao' => $agendamento_existente2['data_solicitacao'],
                    'status' => $status,
                    'profissional' => $profissional,
                    'especialidade' => $especialidade,
                    'convenio_nome' => $convenio_nome,
                    'duplicate_prevented' => true,
                    'check_level' => 2
                ]
            ]);
            exit();
        }
        
        // Preparar query de inserção
        $sql = "INSERT INTO sind.agendamento (
            cod_associado, 
            id_empregador, 
            data_solicitacao, 
            data_agendada,
            cod_convenio, 
            status,
            profissional,
            especialidade,
            convenio_nome
        ) VALUES (
            :cod_associado, 
            :id_empregador, 
            :data_solicitacao, 
            :data_agendada,
            :cod_convenio, 
            :status,
            :profissional,
            :especialidade,
            :convenio_nome
        ) RETURNING id";

        $stmt = $pdo->prepare($sql);

        // Executar inserção
        $result = $stmt->execute([
            ':cod_associado' => $cod_associado,
            ':id_empregador' => $id_empregador,
            ':data_solicitacao' => $data_solicitacao,
            ':data_agendada' => $data_agendada,
            ':cod_convenio' => $cod_convenio,
            ':status' => $status,
            ':profissional' => $profissional,
            ':especialidade' => $especialidade,
            ':convenio_nome' => $convenio_nome
        ]);
        
        if ($result) {
            // Obter o ID do registro inserido
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id_agendamento = $row['id'];
            
            // Commit da transação
            $pdo->commit();
            
            // Log do agendamento criado
            error_log("✅ AGENDAMENTO CRIADO: ID={$id_agendamento}, cod_associado={$cod_associado}, empregador={$id_empregador}, convenio={$cod_convenio}");
            
            // Retornar sucesso
            echo json_encode([
                'success' => true,
                'message' => 'Agendamento criado com sucesso',
                'data' => [
                    'id' => $id_agendamento,
                    'cod_associado' => $cod_associado,
                    'id_empregador' => $id_empregador,
                    'cod_convenio' => $cod_convenio,
                    'data_solicitacao' => $data_solicitacao,
                    'status' => $status,
                    'profissional' => $profissional,
                    'especialidade' => $especialidade,
                    'convenio_nome' => $convenio_nome,
                    'new_record' => true
                ]
            ]);
        } else {
            $pdo->rollBack();
            throw new Exception('Erro ao executar inserção no banco de dados');
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("❌ ERRO AGENDAMENTO: " . $e->getMessage() . " | cod_associado={$cod_associado}");
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'cod_associado' => $cod_associado,
            'id_empregador' => $id_empregador,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?> 