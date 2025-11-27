<?php
/**
 * Webhook Agendamento Imediato
 * Webhook para enviar push imediato quando agendamento for alterado no admin
 */

require_once 'Adm/php/banco.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Permitir OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Aceitar tanto POST quanto GET
    $agendamentoId = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $agendamentoId = $input['agendamento_id'] ?? $_POST['agendamento_id'] ?? null;
    } else {
        $agendamentoId = $_GET['agendamento_id'] ?? null;
    }
    
    if (!$agendamentoId) {
        echo json_encode([
            'success' => false,
            'error' => 'agendamento_id é obrigatório',
            'usage' => 'POST ou GET com agendamento_id'
        ]);
        exit;
    }
    
    /** @noinspection PhpUndefinedClassInspection */
    /** @var PDO $pdo */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar agendamento específico
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.cod_associado,
            a.id_empregador,
            a.data_agendada,
            a.status,
            a.profissional,
            a.especialidade,
            a.convenio_nome,
            a.notification_sent_confirmado,
            s.nome as nome_associado,
            c.cod_verificacao as numero_cartao
        FROM sind.agendamento a
        INNER JOIN sind.associado s ON (a.cod_associado = s.codigo AND a.id_empregador = s.empregador)
        INNER JOIN sind.c_cartaoassociado c ON (s.codigo = c.cod_associado AND s.empregador = c.empregador)
        WHERE a.id = ?
    ");
    
    $stmt->execute([$agendamentoId]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agendamento) {
        echo json_encode([
            'success' => false,
            'message' => 'Agendamento não encontrado',
            'agendamento_id' => $agendamentoId
        ]);
        exit;
    }
    
    // Verificar se precisa enviar notificação
    if (!$agendamento['data_agendada'] || $agendamento['status'] != 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Agendamento não está confirmado ou sem data agendada',
            'agendamento' => [
                'id' => $agendamento['id'],
                'data_agendada' => $agendamento['data_agendada'],
                'status' => $agendamento['status']
            ]
        ]);
        exit;
    }
    
    // Resetar flag de notificação para permitir reenvio
    $stmt = $pdo->prepare("
        UPDATE sind.agendamento 
        SET notification_sent_confirmado = false,
            notification_sent_24h = false,
            notification_sent_1h = false
        WHERE id = ?
    ");
    $stmt->execute([$agendamentoId]);
    
    // Preparar dados para o push
    $dataFormatada = date('d/m/Y H:i', strtotime($agendamento['data_agendada']));
    
    $titulo = "🎉 Agendamento Confirmado!";
    $mensagem = "Seu agendamento foi confirmado para {$dataFormatada} com {$agendamento['profissional']}";
    
    $payload = [
        'user_card' => $agendamento['numero_cartao'],
        'titulo' => $titulo,
        'mensagem' => $mensagem,
        'tipo_notificacao' => 'agendamento_confirmado_webhook',
        'agendamento_id' => $agendamento['id'],
        'data_agendada' => $agendamento['data_agendada'],
        'profissional' => $agendamento['profissional']
    ];
    
    // Verificar se existem subscriptions ativas para este usuário
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM sind.push_subscriptions 
        WHERE user_card = ? AND is_active = true
    ");
    $stmt->execute([$agendamento['numero_cartao']]);
    $subscriptionsAtivas = $stmt->fetchColumn();
    
    if ($subscriptionsAtivas == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não tem notificações ativadas',
            'user_card' => $agendamento['numero_cartao'],
            'agendamento_id' => $agendamento['id']
        ]);
        exit;
    }
    
    // Usar webhook_push_working.php que funciona
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sas.makecard.com.br/webhook_push_working.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['agendamento_id' => $agendamentoId]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro cURL: ' . $error,
            'agendamento_id' => $agendamento['id']
        ]);
        exit;
    }
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        
        if ($result && $result['success']) {
            // Marcar como notificado
            $stmt = $pdo->prepare("UPDATE sind.agendamento SET notification_sent_confirmado = true WHERE id = ?");
            $stmt->execute([$agendamento['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Push notification enviado com sucesso!',
                'agendamento_id' => $agendamento['id'],
                'user_card' => $agendamento['numero_cartao'],
                'nome_associado' => $agendamento['nome_associado'],
                'data_agendada' => $dataFormatada,
                'profissional' => $agendamento['profissional'],
                'subscriptions_ativas' => $subscriptionsAtivas,
                'push_result' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Falha ao enviar push notification',
                'agendamento_id' => $agendamento['id'],
                'push_response' => $response,
                'http_code' => $httpCode
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Erro HTTP {$httpCode} ao enviar push",
            'agendamento_id' => $agendamento['id'],
            'response' => $response
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ]);
}
?> 