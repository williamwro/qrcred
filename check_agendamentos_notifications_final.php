<?php
/**
 * Check Agendamentos Notifications - VERSÃO FINAL CORRIGIDA
 * CORREÇÃO FINAL: JOIN triplo para pegar cod_verificacao da tabela sind.c_cartaoassociado
 */

require_once 'Adm/php/banco.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[AGENDAMENTO_NOTIFICATIONS_FINAL] [{$timestamp}] {$message}");
    
    if (php_sapi_name() === 'cli') {
        echo "[{$timestamp}] {$message}\n";
    }
}

function sendAgendamentoNotification($agendamento) {
    logMessage("Enviando notificação para agendamento ID: {$agendamento['id']} - Cartão: {$agendamento['numero_cartao']}");
    
    // Preparar dados da notificação
    $data_agendada = new DateTime($agendamento['data_agendada']);
    $data_formatada = $data_agendada->format('d/m/Y \à\s H:i');
    
    $titulo = "📅 Agendamento Confirmado!";
    $mensagem = "Seu agendamento foi confirmado para {$data_formatada}";
    
    if (!empty($agendamento['profissional'])) {
        $mensagem .= " - {$agendamento['profissional']}";
    }
    
    if (!empty($agendamento['especialidade'])) {
        $mensagem .= " ({$agendamento['especialidade']})";
    }
    
    // Dados para o push notification - USANDO COD_VERIFICACAO CORRETO
    $pushData = [
        'user_card' => $agendamento['numero_cartao'], // ← CORREÇÃO FINAL: usar cod_verificacao
        'titulo' => $titulo,
        'mensagem' => $mensagem,
        'tipo_notificacao' => 'agendamento_confirmado',
        'agendamento_id' => $agendamento['id'],
        'data_agendada' => $agendamento['data_agendada'],
        'profissional' => $agendamento['profissional'] ?? '',
        'especialidade' => $agendamento['especialidade'] ?? '',
        'convenio_nome' => $agendamento['convenio_nome'] ?? ''
    ];
    
    logMessage("Dados do push: " . json_encode($pushData));
    
    // Enviar para send_push_fixed.php
    $url = 'https://sas.makecard.com.br/send_push_fixed.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pushData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($result === false) {
        logMessage("ERRO: Falha ao chamar send_push_fixed.php");
        return false;
    }
    
    $response = json_decode($result, true);
    logMessage("Resposta do push (HTTP {$httpCode}): " . json_encode($response));
    
    return $response['success'] ?? false;
}

try {
    logMessage("=== INICIANDO VERIFICAÇÃO DE AGENDAMENTOS (VERSÃO FINAL CORRIGIDA) ===");
    
    // Conectar ao banco
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // QUERY FINAL CORRIGIDA: JOIN TRIPLO para pegar cod_verificacao
    $sql = "
        SELECT 
            a.id, 
            a.cod_associado, 
            a.id_empregador, 
            a.data_solicitacao, 
            a.cod_convenio, 
            a.status, 
            a.profissional, 
            a.especialidade, 
            a.convenio_nome, 
            a.data_agendada, 
            a.notification_sent_confirmado,
            a.notification_sent_24h, 
            a.notification_sent_1h,
            s.nome as nome_associado,
            s.email,
            s.cel,
            c.cod_verificacao as numero_cartao  -- ← CAMPO CORRETO DO NÚMERO DO CARTÃO
        FROM sind.agendamento a
        INNER JOIN sind.associado s ON (
            a.cod_associado = s.codigo 
            AND a.id_empregador = s.empregador
        )
        INNER JOIN sind.c_cartaoassociado c ON (
            s.codigo = c.cod_associado 
            AND s.empregador = c.empregador
        )
        WHERE 
            a.data_agendada IS NOT NULL 
            AND (a.notification_sent_confirmado IS NULL OR a.notification_sent_confirmado = false)
            AND a.status = 2                   -- Apenas agendamentos CONFIRMADOS
            AND c.cod_verificacao IS NOT NULL  -- Garantir que tem número do cartão
            AND c.cod_situacaocartao = 1       -- Apenas cartões ativos
        ORDER BY a.data_agendada ASC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Encontrados " . count($agendamentos) . " agendamentos para notificar");
    
    if (empty($agendamentos)) {
        echo json_encode([
            'success' => true,
            'message' => 'Nenhum agendamento pendente de notificação',
            'total_processed' => 0,
            'notifications_sent' => 0,
            'errors' => 0
        ]);
        exit;
    }
    
    $results = [
        'total_processed' => count($agendamentos),
        'notifications_sent' => 0,
        'errors' => 0,
        'details' => []
    ];
    
    // Processar cada agendamento
    foreach ($agendamentos as $agendamento) {
        try {
            logMessage("Processando agendamento ID: {$agendamento['id']} - Associado: {$agendamento['cod_associado']} - Cartão: {$agendamento['numero_cartao']}");
            
            // Enviar push notification
            $pushSuccess = sendAgendamentoNotification($agendamento);
            
            if ($pushSuccess) {
                // Marcar como notificação enviada
                $updateSql = "
                    UPDATE sind.agendamento 
                    SET notification_sent_confirmado = true
                    WHERE id = ?
                ";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$agendamento['id']]);
                
                $results['notifications_sent']++;
                $results['details'][] = [
                    'agendamento_id' => $agendamento['id'],
                    'cod_associado' => $agendamento['cod_associado'],
                    'user_card' => $agendamento['numero_cartao'], // ← Número do cartão correto
                    'nome_associado' => $agendamento['nome_associado'],
                    'success' => true,
                    'message' => 'Notificação enviada e marcada como enviada'
                ];
                
                logMessage("✅ Sucesso: Agendamento {$agendamento['id']} notificado para cartão {$agendamento['numero_cartao']}");
                
            } else {
                $results['errors']++;
                $results['details'][] = [
                    'agendamento_id' => $agendamento['id'],
                    'cod_associado' => $agendamento['cod_associado'],
                    'user_card' => $agendamento['numero_cartao'],
                    'nome_associado' => $agendamento['nome_associado'],
                    'success' => false,
                    'message' => 'Falha ao enviar push notification'
                ];
                
                logMessage("❌ Erro: Falha ao notificar agendamento {$agendamento['id']} para cartão {$agendamento['numero_cartao']}");
            }
            
        } catch (Exception $e) {
            $results['errors']++;
            $results['details'][] = [
                'agendamento_id' => $agendamento['id'],
                'cod_associado' => $agendamento['cod_associado'],
                'user_card' => $agendamento['numero_cartao'] ?? 'N/A',
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ];
            
            logMessage("❌ Erro ao processar agendamento {$agendamento['id']}: " . $e->getMessage());
        }
    }
    
    logMessage("=== PROCESSAMENTO CONCLUÍDO (VERSÃO FINAL CORRIGIDA) ===");
    logMessage("Total processados: {$results['total_processed']}");
    logMessage("Notificações enviadas: {$results['notifications_sent']}");
    logMessage("Erros: {$results['errors']}");
    
    echo json_encode([
        'success' => true,
        'message' => "Processados {$results['total_processed']} agendamentos (versão final corrigida)",
        'results' => $results,
        'version' => 'final_with_triple_join'
    ]);
    
} catch (Exception $e) {
    logMessage("ERRO CRÍTICO: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage(),
        'version' => 'final_with_triple_join'
    ]);
}
?> 