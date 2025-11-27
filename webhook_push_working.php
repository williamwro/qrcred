<?php
/**
 * Webhook Push Working
 * Webhook que funciona - baseado no teste manual bem-sucedido
 */

require_once 'Adm/php/banco.php';
require_once 'vapid_config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Permitir OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!file_exists('vendor/autoload.php')) {
    echo json_encode([
        'success' => false,
        'error' => 'Biblioteca web-push não encontrada. Execute: composer require minishlink/web-push'
    ]);
    exit;
}

require_once 'vendor/autoload.php';

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
    
    // Buscar subscription ativa do usuário
    $stmt = $pdo->prepare("
        SELECT 
            id,
            endpoint,
            COALESCE(p256dh, p256dh_key) as p256dh,
            COALESCE(auth, auth_key) as auth,
            settings
        FROM sind.push_subscriptions 
        WHERE user_card = ? AND is_active = true
        LIMIT 1
    ");
    
    $stmt->execute([$agendamento['numero_cartao']]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não tem notificações ativadas',
            'user_card' => $agendamento['numero_cartao'],
            'agendamento_id' => $agendamento['id']
        ]);
        exit;
    }
    
    if (empty($subscription['p256dh']) || empty($subscription['auth'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Subscription incompleta - reative notificações no app',
            'user_card' => $agendamento['numero_cartao'],
            'agendamento_id' => $agendamento['id']
        ]);
        exit;
    }
    
    // Configurar WebPush (igual ao teste manual que funcionou)
    $auth = [
        'VAPID' => [
            'subject' => VAPID_SUBJECT,
            'publicKey' => VAPID_PUBLIC_KEY,
            'privateKey' => VAPID_PRIVATE_KEY,
        ],
    ];
    
    $webPush = new \Minishlink\WebPush\WebPush($auth);
    
    // Criar objeto Subscription
    $pushSubscription = \Minishlink\WebPush\Subscription::create([
        'endpoint' => $subscription['endpoint'],
        'keys' => [
            'p256dh' => $subscription['p256dh'],
            'auth' => $subscription['auth']
        ]
    ]);
    
    // Preparar dados para o push
    $dataFormatada = date('d/m/Y H:i', strtotime($agendamento['data_agendada']));
    
    $titulo = "🎉 Agendamento Confirmado!";
    $mensagem = "Seu agendamento foi confirmado para {$dataFormatada} com {$agendamento['profissional']}";
    
    // Payload da notificação (igual ao teste que funcionou)
    $payload = json_encode([
        'title' => $titulo,
        'body' => $mensagem,
        'icon' => 'https://sas.makecard.com.br/icons/icon-192x192.png',
        'badge' => 'https://sas.makecard.com.br/icons/icon-192x192.png',
        'data' => [
            'tipo' => 'agendamento_confirmado_working',
            'agendamento_id' => $agendamento['id'],
            'url' => 'https://sas.makecard.com.br/dashboard',
            'timestamp' => time()
        ],
        'actions' => [
            [
                'action' => 'view',
                'title' => 'Ver Detalhes',
                'icon' => 'https://sas.makecard.com.br/icons/icon-192x192.png'
            ]
        ],
        'requireInteraction' => true,
        'tag' => 'agendamento-' . $agendamento['id']
    ]);
    
    // Enviar notificação (igual ao teste manual que funcionou)
    $report = $webPush->sendOneNotification($pushSubscription, $payload);
    
    if ($report->isSuccess()) {
        // Marcar como notificado
        $stmt = $pdo->prepare("UPDATE sind.agendamento SET notification_sent_confirmado = true WHERE id = ?");
        $stmt->execute([$agendamento['id']]);
        
        // Log da notificação (se tabela existir)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notification_log (user_card, subscription_id, notification_type, payload, sent_at, status, agendamento_id)
                VALUES (?, ?, ?, ?, NOW(), 'sent', ?)
            ");
            $stmt->execute([
                $agendamento['numero_cartao'],
                $subscription['id'],
                'agendamento_confirmado_working',
                $payload,
                $agendamento['id']
            ]);
        } catch (Exception $e) {
            // Se der erro no log, não interromper o processo
            error_log("Erro ao salvar log de notificação: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Push notification enviado com sucesso!',
            'agendamento_id' => $agendamento['id'],
            'user_card' => $agendamento['numero_cartao'],
            'nome_associado' => $agendamento['nome_associado'],
            'data_agendada' => $dataFormatada,
            'profissional' => $agendamento['profissional'],
            'http_code' => 201,
            'timestamp' => date('Y-m-d H:i:s'),
            'webhook_version' => 'working_v1.0'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Falha ao enviar push notification',
            'agendamento_id' => $agendamento['id'],
            'error' => $report->getReason(),
            'http_code' => $report->getResponse()->getStatusCode()
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 