<?php
/**
 * Webhook ZapSign para Adesão SasCred
 * Versão ATUALIZADA - Busca divisão correta na tabela adesoes_pendentes
 * 
 * Resolve problema: Associado pode ter múltiplos registros com divisões diferentes
 * Solução: Busca id_associado e id_divisao corretos da sessão ativa do usuário
 */

header('Content-Type: application/json; charset=utf-8');

// Log de recebimento do webhook
error_log("========================================");
error_log("🔔 WEBHOOK ZAPSIGN RECEBIDO - " . date('Y-m-d H:i:s'));
error_log("========================================");

try {
    // Ler dados do webhook
    $input = file_get_contents('php://input');
    error_log("📥 Input recebido: " . $input);
    
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Dados inválidos recebidos do webhook');
    }
    
    // Extrair dados do signatário
    $signers = $data['signers'] ?? [];
    
    if (empty($signers)) {
        throw new Exception('Nenhum signatário encontrado no webhook');
    }
    
    $signer = $signers[0];
    
    // Dados básicos do signatário
    $nome = $signer['name'] ?? '';
    $email = $signer['email'] ?? '';
    $cpf_original = $signer['cpf'] ?? '';
    $celular = $signer['phone_number'] ?? '';
    $has_signed = $signer['has_signed'] ?? false;
    $signed_at = $signer['signed_at'] ?? null;
    
    // ✅ LIMPAR CPF: Remover pontos, traços e espaços
    $cpf = preg_replace('/[^0-9]/', '', $cpf_original);
    
    // Dados do documento
    $doc_token = $data['token'] ?? '';
    $doc_name = $data['name'] ?? '';
    $event = $data['event'] ?? '';
    
    error_log("📋 Dados extraídos:");
    error_log("   Nome: $nome");
    error_log("   Email: $email");
    error_log("   CPF Original: $cpf_original");
    error_log("   CPF Limpo: $cpf");
    error_log("   Celular: $celular");
    error_log("   Has Signed: " . ($has_signed ? 'true' : 'false'));
    error_log("   Event: $event");
    
    // Validar dados obrigatórios
    if (empty($cpf) || empty($email)) {
        throw new Exception('CPF e Email são obrigatórios');
    }
    
    // Incluir arquivo de conexão com banco
    include "Adm/php/banco.php";
    
    // Conectar ao banco
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ✅ SOLUÇÃO: Buscar id_associado e id_divisao corretos na tabela adesoes_pendentes
    error_log("🔍 Buscando dados da adesão pendente...");
    error_log("   CPF fornecido: " . ($cpf ? $cpf : '[VAZIO]'));
    error_log("   Email fornecido: " . ($email ? $email : '[VAZIO]'));
    
    // Construir query dinâmica baseada nos dados disponíveis
    $sqlPendente = "SELECT id, codigo, id_associado, id_divisao, nome, celular 
                    FROM sind.adesoes_pendentes 
                    WHERE status = 'pendente'";
    
    $params = [];
    
    // ✅ BUSCA FLEXÍVEL: Aceitar CPF OU Email (ou ambos)
    if (!empty($cpf) && !empty($email)) {
        // Caso ideal: ambos disponíveis (busca mais precisa)
        $sqlPendente .= " AND cpf = :cpf AND email = :email";
        $params[':cpf'] = $cpf;
        $params[':email'] = $email;
        error_log("   Estratégia: Busca por CPF + Email (mais precisa)");
    } elseif (!empty($cpf)) {
        // Apenas CPF disponível
        $sqlPendente .= " AND cpf = :cpf";
        $params[':cpf'] = $cpf;
        error_log("   Estratégia: Busca apenas por CPF");
    } elseif (!empty($email)) {
        // Apenas Email disponível
        $sqlPendente .= " AND email = :email";
        $params[':email'] = $email;
        error_log("   Estratégia: Busca apenas por Email");
    } else {
        // Nenhum dos dois disponível - não é possível buscar
        error_log("⚠️ AVISO: Nem CPF nem Email disponíveis para busca em adesoes_pendentes");
    }
    
    $sqlPendente .= " ORDER BY data_inicio DESC LIMIT 1";
    
    $stmtPendente = $pdo->prepare($sqlPendente);
    $stmtPendente->execute($params);
    
    $adesaoPendente = $stmtPendente->fetch(PDO::FETCH_ASSOC);
    
    if (!$adesaoPendente) {
        error_log("⚠️ AVISO: Adesão pendente não encontrada para CPF: $cpf, Email: $email");
        error_log("⚠️ Tentando buscar diretamente na tabela associado...");
        
        // Fallback: Buscar na tabela associado (menos seguro)
        error_log("   Tentando fallback na tabela associado...");
        
        $sqlAssociado = "SELECT id, id_divisao, codigo 
                         FROM sind.associado 
                         WHERE 1=1";
        
        $paramsAssociado = [];
        
        // ✅ FALLBACK INTELIGENTE: Priorizar CPF + Email juntos
        if (!empty($cpf) && !empty($email)) {
            // MELHOR CENÁRIO: Buscar por CPF E Email (mais preciso)
            $sqlAssociado .= " AND cpf = :cpf AND email = :email";
            $paramsAssociado[':cpf'] = $cpf;
            $paramsAssociado[':email'] = $email;
            error_log("   Fallback: Buscando por CPF + Email (mais preciso)");
        } elseif (!empty($cpf)) {
            // Apenas CPF disponível
            $sqlAssociado .= " AND cpf = :cpf";
            $paramsAssociado[':cpf'] = $cpf;
            error_log("   Fallback: Buscando apenas por CPF");
        } elseif (!empty($email)) {
            // Apenas Email disponível
            $sqlAssociado .= " AND email = :email";
            $paramsAssociado[':email'] = $email;
            error_log("   Fallback: Buscando apenas por Email");
        } else {
            error_log("❌ ERRO: Impossível buscar associado sem CPF ou Email");
            throw new Exception('CPF e Email não disponíveis - impossível identificar associado');
        }
        
        $sqlAssociado .= " ORDER BY id DESC LIMIT 1";
        
        $stmtAssociado = $pdo->prepare($sqlAssociado);
        $stmtAssociado->execute($paramsAssociado);
        
        $associadoData = $stmtAssociado->fetch(PDO::FETCH_ASSOC);
        
        if (!$associadoData) {
            $criterio = !empty($cpf) ? "CPF: $cpf" : "Email: $email";
            error_log("❌ ERRO: Associado não encontrado no banco de dados ($criterio)");
            throw new Exception("Associado não encontrado no banco de dados ($criterio)");
        }
        
        $id_associado = $associadoData['id'];
        $id_divisao = $associadoData['id_divisao'];
        $codigo = $associadoData['codigo'];
        
        error_log("⚠️ Usando dados do associado (fallback): ID=$id_associado, Divisão=$id_divisao, Código=$codigo");
        
    } else {
        // ✅ Dados encontrados na adesão pendente (CORRETO)
        $id_associado = $adesaoPendente['id_associado'];
        $id_divisao = $adesaoPendente['id_divisao'];
        $codigo = $adesaoPendente['codigo'];
        
        error_log("✅ Adesão pendente encontrada:");
        error_log("   ID Adesão Pendente: " . $adesaoPendente['id']);
        error_log("   Código: $codigo");
        error_log("   ID Associado: $id_associado");
        error_log("   ID Divisão: $id_divisao");
        
        // Atualizar status da adesão pendente
        $sqlUpdatePendente = "UPDATE sind.adesoes_pendentes 
                              SET status = 'assinado', 
                                  doc_token = :doc_token
                              WHERE id = :id";
        
        $stmtUpdatePendente = $pdo->prepare($sqlUpdatePendente);
        $stmtUpdatePendente->execute([
            ':doc_token' => $doc_token,
            ':id' => $adesaoPendente['id']
        ]);
        
        error_log("✅ Status da adesão pendente atualizado para 'assinado'");
    }
    
    // ✅ BUSCAR DADOS ADICIONAIS DO ASSOCIADO (limite, salário, etc)
    error_log("🔍 Buscando dados adicionais do associado...");
    
    $sqlDadosAssociado = "SELECT limite, salario FROM sind.associado WHERE id = :id_associado LIMIT 1";
    $stmtDadosAssociado = $pdo->prepare($sqlDadosAssociado);
    $stmtDadosAssociado->execute([':id_associado' => $id_associado]);
    $dadosAssociado = $stmtDadosAssociado->fetch(PDO::FETCH_ASSOC);
    
    $limite = $dadosAssociado['limite'] ?? '2000.00';
    $salario = $dadosAssociado['salario'] ?? '0.00';
    
    error_log("   Limite: $limite");
    error_log("   Salário: $salario");
    
    // ✅ DETERMINAR TIPO DO DOCUMENTO (1=adesão, 2=antecipação)
    $tipo = 1; // Padrão: adesão
    if (stripos($doc_name, 'antecipação') !== false || stripos($doc_name, 'antecipacao') !== false) {
        $tipo = 2;
    }
    error_log("   Tipo documento: $tipo (" . ($tipo == 1 ? 'Adesão' : 'Antecipação') . ")");
    
    // ✅ VALORES DE APROVAÇÃO AUTOMÁTICA
    $valor_aprovado = '550.00'; // Valor fixo de aprovação
    $data_pgto = date('Y-m-d H:i:s'); // Data/hora atual
    
    // Verificar se já existe registro na tabela associados_sasmais
    // ✅ IMPORTANTE: Verificar também o TIPO para permitir múltiplos registros (adesão + antecipação)
    error_log("🔍 Verificando se já existe registro em associados_sasmais...");
    error_log("   Buscando por: id_associado=$id_associado, id_divisao=$id_divisao, tipo=$tipo");
    
    $sqlVerifica = "SELECT id FROM sind.associados_sasmais 
                    WHERE id_associado = :id_associado 
                    AND id_divisao = :id_divisao
                    AND tipo = :tipo
                    LIMIT 1";
    
    $stmtVerifica = $pdo->prepare($sqlVerifica);
    $stmtVerifica->execute([
        ':id_associado' => $id_associado,
        ':id_divisao' => $id_divisao,
        ':tipo' => $tipo
    ]);
    
    $registroExistente = $stmtVerifica->fetch();
    
    if ($registroExistente) {
        error_log("⚠️ Registro já existe na tabela associados_sasmais (ID: " . $registroExistente['id'] . ")");
        error_log("⚠️ Atualizando registro existente...");
        
        // Atualizar registro existente COM TODOS OS CAMPOS
        $sqlUpdate = "UPDATE sind.associados_sasmais 
                      SET nome = :nome,
                          email = :email,
                          celular = :celular,
                          has_signed = 't',
                          signed_at = :signed_at,
                          doc_token = :doc_token,
                          doc_name = :doc_name,
                          event = 'doc_signed',
                          name = :name,
                          cel_informado = :cel_informado,
                          limite = :limite,
                          valor_aprovado = :valor_aprovado,
                          data_pgto = :data_pgto,
                          tipo = :tipo,
                          autorizado = 't',
                          data_hora = NOW()
                      WHERE id = :id";
        
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':celular' => $celular,
            ':signed_at' => $signed_at,
            ':doc_token' => $doc_token,
            ':doc_name' => $doc_name,
            ':name' => $nome,
            ':cel_informado' => $celular,
            ':limite' => $limite,
            ':valor_aprovado' => $valor_aprovado,
            ':data_pgto' => $data_pgto,
            ':tipo' => $tipo,
            ':id' => $registroExistente['id']
        ]);
        
        error_log("✅ Registro atualizado com sucesso (ID: " . $registroExistente['id'] . ")");
        
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => 'Registro atualizado com sucesso',
            'id' => $registroExistente['id'],
            'acao' => 'atualizado'
        ]);
        
    } else {
        // Inserir novo registro com divisão correta
        error_log("📝 Inserindo novo registro em associados_sasmais...");
        
        $sqlInsert = "INSERT INTO sind.associados_sasmais 
                      (codigo, nome, email, cpf, celular, id_associado, id_divisao, 
                       has_signed, signed_at, doc_token, doc_name, event, name, cel_informado,
                       limite, valor_aprovado, data_pgto, tipo, reprovado, chave_pix,
                       aceitou_termo, data_hora, autorizado)
                      VALUES 
                      (:codigo, :nome, :email, :cpf, :celular, :id_associado, :id_divisao,
                       't', :signed_at, :doc_token, :doc_name, 'doc_signed', :name, :cel_informado,
                       :limite, :valor_aprovado, :data_pgto, :tipo, 'f', '',
                       't', NOW(), 't')
                      RETURNING id";
        
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':codigo' => $codigo,
            ':nome' => $nome,
            ':email' => $email,
            ':cpf' => $cpf,
            ':celular' => $celular,
            ':id_associado' => $id_associado,
            ':id_divisao' => $id_divisao,
            ':signed_at' => $signed_at,
            ':doc_token' => $doc_token,
            ':doc_name' => $doc_name,
            ':name' => $nome,
            ':cel_informado' => $celular,
            ':limite' => $limite,
            ':valor_aprovado' => $valor_aprovado,
            ':data_pgto' => $data_pgto,
            ':tipo' => $tipo
        ]);
        
        $resultado = $stmtInsert->fetch(PDO::FETCH_ASSOC);
        $novoId = $resultado['id'];
        
        error_log("✅ Novo registro inserido com sucesso:");
        error_log("   ID: $novoId");
        error_log("   Código: $codigo");
        error_log("   ID Associado: $id_associado");
        error_log("   ID Divisão: $id_divisao (✅ DIVISÃO CORRETA)");
        error_log("   Has Signed: true (PADRÃO)");
        error_log("   Event: doc_signed (PADRÃO)");
        error_log("   Autorizado: true (PADRÃO)");
        error_log("   Limite: $limite");
        error_log("   Valor Aprovado: $valor_aprovado");
        error_log("   Data Pgto: $data_pgto");
        error_log("   Tipo: $tipo");
        error_log("   Celular Informado: $celular");
        
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => 'Adesão registrada com sucesso',
            'id' => $novoId,
            'acao' => 'inserido',
            'id_associado' => $id_associado,
            'id_divisao' => $id_divisao
        ]);
    }
    
    // Fechar conexão
    $pdo = null;
    
    error_log("========================================");
    error_log("✅ WEBHOOK PROCESSADO COM SUCESSO");
    error_log("========================================");
    
} catch (PDOException $e) {
    error_log("❌ ERRO PDO: " . $e->getMessage());
    error_log("❌ Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao processar webhook',
        'erro_detalhes' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    error_log("❌ ERRO GERAL: " . $e->getMessage());
    error_log("❌ Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao processar webhook',
        'erro_detalhes' => $e->getMessage()
    ]);
}
?>
