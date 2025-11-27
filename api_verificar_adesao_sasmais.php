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
    
    // Log do input recebido
    error_log("📥 API Verificar Adesão - Input: " . $input);
    
    // Extrair parâmetros
    $codigo     = isset($data['codigo']) ? trim($data['codigo']) : null;
    $id         = isset($data['id']) ? (int)$data['id'] : null;
    $id_divisao = isset($data['id_divisao']) ? (int)$data['id_divisao'] : null;
    
    // ✅ VALIDAÇÃO INTELIGENTE: Priorizar id_associado, fallback para codigo
    if ($id === null && ($codigo === null || $codigo === '')) {
        http_response_code(400);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'É necessário enviar id_associado OU codigo do associado.',
            'jaAderiu' => false
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    error_log("🔍 Verificando adesão - Código: $codigo, ID Associado: $id, ID Divisão: $id_divisao");
    
    // ✅ QUERY OTIMIZADA: Priorizar busca por id_associado (mais seguro)
    $sql = "SELECT id, codigo, nome, celular, data_hora, autorizado, 
                   aceitou_termo, has_signed, id_associado, id_divisao,
                   email, cpf, limite, valor_aprovado, chave_pix, reprovado, tipo
            FROM sind.associados_sasmais 
            WHERE 1=1";
    
    $params = [];
    
    // 🎯 ESTRATÉGIA DE BUSCA EM ORDEM DE PRIORIDADE:
    
    // PRIORIDADE 1: id_associado + id_divisao (MAIS SEGURO)
    if ($id !== null && $id_divisao !== null) {
        $sql .= " AND id_associado = :id_associado AND id_divisao = :id_divisao";
        $params[':id_associado'] = $id;
        $params[':id_divisao'] = $id_divisao;
        error_log("🔒 BUSCA SEGURA: id_associado=$id + id_divisao=$id_divisao");
    }
    // PRIORIDADE 2: apenas id_associado (SEGURO)
    elseif ($id !== null) {
        $sql .= " AND id_associado = :id_associado";
        $params[':id_associado'] = $id;
        error_log("🔒 BUSCA SEGURA: id_associado=$id");
    }
    // PRIORIDADE 3: codigo + id_divisao (FALLBACK)
    elseif ($codigo !== null && $id_divisao !== null) {
        $sql .= " AND codigo = :codigo AND id_divisao = :id_divisao";
        $params[':codigo'] = $codigo;
        $params[':id_divisao'] = $id_divisao;
        error_log("⚠️ BUSCA FALLBACK: codigo=$codigo + id_divisao=$id_divisao");
    }
    // PRIORIDADE 4: apenas codigo (MENOS SEGURO - último recurso)
    else {
        $sql .= " AND codigo = :codigo";
        $params[':codigo'] = $codigo;
        error_log("⚠️ BUSCA MENOS SEGURA: apenas codigo=$codigo");
    }
    
    $sql .= " LIMIT 1";
    
    error_log("📝 Query SQL: $sql");
    error_log("📝 Params: " . json_encode($params));

    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Erro ao preparar query SQL');
    }
    
    $stmt->execute($params);
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        // ✅ ASSOCIADO ENCONTRADO - retornar status 'sucesso'
        error_log("✅ Associado ENCONTRADO - ID: " . $resultado['id'] . ", ID Associado: " . $resultado['id_associado']);
        
        echo json_encode([
            'status' => 'sucesso',
            'jaAderiu' => true,
            'mensagem' => 'Associado já aderiu ao Sascred.',
            'dados' => [
                'id' => $resultado['id'],
                'codigo' => $resultado['codigo'],
                'nome' => $resultado['nome'],
                'celular' => $resultado['celular'],
                'data_hora' => $resultado['data_hora'],
                'autorizado' => $resultado['autorizado'],
                'aceitou_termo' => $resultado['aceitou_termo'],
                'has_signed' => $resultado['has_signed'],
                'id_associado' => $resultado['id_associado'],
                'id_divisao' => $resultado['id_divisao'],
                'email' => $resultado['email'],
                'cpf' => $resultado['cpf'],
                'limite' => $resultado['limite'],
                'valor_aprovado' => $resultado['valor_aprovado'],
                'chave_pix' => $resultado['chave_pix'],
                'reprovado' => $resultado['reprovado'],
                'tipo' => $resultado['tipo']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // ✅ CORREÇÃO CRÍTICA: Associado NÃO encontrado - retornar status 'sucesso' com jaAderiu false
        // A API Next.js só processa se status === 'sucesso'
        error_log("❌ Associado NÃO encontrado");
        
        echo json_encode([
            'status' => 'sucesso',  // ✅ CORRIGIDO (era 'erro')
            'jaAderiu' => false,
            'mensagem' => 'Associado ainda não aderiu ao Sascred.',
            'dados' => null
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    // Erro de banco de dados
    error_log("❌ Erro PDO ao verificar adesão: " . $e->getMessage());
    error_log("❌ Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao consultar banco de dados.',
        'jaAderiu' => false,
        'erro_detalhes' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Erro geral
    error_log("❌ Erro geral ao verificar adesão: " . $e->getMessage());
    error_log("❌ Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro interno do servidor.',
        'jaAderiu' => false,
        'erro_detalhes' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} finally {
    // Fechar conexão
    $pdo = null;
}
?>