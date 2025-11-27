<?php
/**
 * Check Agendamentos Notifications
 * Verifica agendamentos com data_agendada preenchida e envia push notifications
 */

require_once 'Adm/php/banco.php';
require_once 'send_push_fixed.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[AGENDAMENTO_NOTIFICATIONS] [{$timestamp}] {$message}");
    
    if (php_sapi_name() === 'cli') {
        echo "[{$timestamp}] {$message}\n";
    }
}

function sendAgendamentoNotification($agendamento) {
    logMessage("Enviando notificação para agendamento ID: {$agendamento['id']}");
    
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
    
    // Dados para o push notification
    $pushData = [
        'user_card' => $agendamento['cod_associado'],
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
    
    // Simular requisição POST para send_push_fixed.php
    $postData = http_build_query($pushData);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData
        ]
    ]);
    
    $url = 'https://sas.makecard.com.br/send_push_fixed.php';
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        logMessage("ERRO: Falha ao chamar send_push_fixed.php");
        return false;
    }
    
    $response = json_decode($result, true);
    logMessage("Resposta do push: " . json_encode($response));
    
    return $response['success'] ?? false;
}

try {
    logMessage("=== INICIANDO VERIFICAÇÃO DE AGENDAMENTOS ===");
    
    // Conectar ao banco
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar agendamentos com data_agendada preenchida e notificação não enviada
    $sql = "
        SELECT 
            id, cod_associado, id_empregador, data_solicitacao, 
            cod_convenio, status, profissional, especialidade, 
            convenio_nome, data_agendada, notification_sent_confirmado,
            notification_sent_24h, notification_sent_1h
        FROM sind.agendamento 
        WHERE 
            data_agendada IS NOT NULL 
            AND (notification_sent_confirmado IS NULL OR notification_sent_confirmado = false)
            AND status = 1
        ORDER BY data_agendada ASC
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
            logMessage("Processando agendamento ID: {$agendamento['id']} - Usuário: {$agendamento['cod_associado']}");
            
            // Enviar push notification
            $pushSuccess = sendAgendamentoNotification($agendamento);
            
            if ($pushSuccess) {
                // Marcar como notificação enviada
                $updateSql = "
                    UPDATE sind.agendamento 
                    SET notification_sent_confirmado = true,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$agendamento['id']]);
                
                $results['notifications_sent']++;
                $results['details'][] = [
                    'agendamento_id' => $agendamento['id'],
                    'user_card' => $agendamento['cod_associado'],
                    'success' => true,
                    'message' => 'Notificação enviada e marcada como enviada'
                ];
                
                logMessage("✅ Sucesso: Agendamento {$agendamento['id']} notificado");
                
            } else {
                $results['errors']++;
                $results['details'][] = [
                    'agendamento_id' => $agendamento['id'],
                    'user_card' => $agendamento['cod_associado'],
                    'success' => false,
                    'message' => 'Falha ao enviar push notification'
                ];
                
                logMessage("❌ Erro: Falha ao notificar agendamento {$agendamento['id']}");
            }
            
        } catch (Exception $e) {
            $results['errors']++;
            $results['details'][] = [
                'agendamento_id' => $agendamento['id'],
                'user_card' => $agendamento['cod_associado'],
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ];
            
            logMessage("❌ Erro ao processar agendamento {$agendamento['id']}: " . $e->getMessage());
        }
    }
    
    logMessage("=== PROCESSAMENTO CONCLUÍDO ===");
    logMessage("Total processados: {$results['total_processed']}");
    logMessage("Notificações enviadas: {$results['notifications_sent']}");
    logMessage("Erros: {$results['errors']}");
    
    echo json_encode([
        'success' => true,
        'message' => "Processados {$results['total_processed']} agendamentos",
        'results' => $results
    ]);
    
} catch (Exception $e) {
    logMessage("ERRO CRÍTICO: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage()
    ]);
}
?> 