<?php
/**
 * Send Push Notification - Versão Corrigida
 * Sem constantes duplicadas e usando biblioteca web-push
 */

// Incluir configuração VAPID
require_once 'vapid_config.php';

// Incluir conexão com banco
require_once 'Adm/php/banco.php';

// Incluir biblioteca web-push
require_once 'vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

try {
    // Conectar ao banco
    $pdo = Banco::conectar_postgres();
    
    // Buscar subscriptions do usuário
    $user_card = $data['user_card'] ?? '';
    
    if (empty($user_card)) {
        throw new Exception('user_card é obrigatório');
    }
    
    $stmt = $pdo->prepare("
        SELECT id, endpoint, 
               COALESCE(p256dh, p256dh_key) as p256dh, 
               COALESCE(auth, auth_key) as auth, 
               settings, created_at 
        FROM sind.push_subscriptions 
        WHERE user_card = ? AND is_active = true
    ");
    $stmt->execute([$user_card]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        echo json_encode([
            'success' => false,
            'message' => 'Nenhuma subscription encontrada para este usuário',
            'user_card' => $user_card
        ]);
        exit;
    }
    
    // Configurar WebPush
    $auth = [
        'VAPID' => [
            'subject' => VAPID_SUBJECT ?? 'mailto:admin@sas.makecard.com.br',
            'publicKey' => VAPID_PUBLIC_KEY,
            'privateKey' => VAPID_PRIVATE_KEY,
        ],
    ];
    
    $webPush = new WebPush($auth);
    
    // Preparar payload da notificação
    $payload = json_encode([
        'title' => $data['titulo'] ?? 'Nova Notificação',
        'body' => $data['mensagem'] ?? 'Você tem uma nova notificação',
        'icon' => '/icons/icon-192x192.png',
        'badge' => '/icons/icon-192x192.png',
        'data' => [
            'type' => $data['tipo_notificacao'] ?? 'general',
            'agendamento_id' => $data['agendamento_id'] ?? null,
            'url' => '/dashboard'
        ]
    ]);
    
    $results = [
        'total_subscriptions' => count($subscriptions),
        'success_count' => 0,
        'error_count' => 0,
        'details' => []
    ];
    
    // Enviar para cada subscription
    foreach ($subscriptions as $sub) {
        try {
            // Verificar se subscription tem dados válidos
            if (empty($sub['endpoint']) || empty($sub['p256dh']) || empty($sub['auth'])) {
                $results['error_count']++;
                $results['details'][] = [
                    'subscription_id' => $sub['id'],
                    'success' => false,
                    'error' => 'Dados de subscription incompletos'
                ];
                continue;
            }
            
            // Criar subscription object
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh'],
                    'auth' => $sub['auth']
                ]
            ]);
            
            // Enviar notificação
            $report = $webPush->sendOneNotification($subscription, $payload);
            
            if ($report->isSuccess()) {
                $results['success_count']++;
                $results['details'][] = [
                    'subscription_id' => $sub['id'],
                    'success' => true,
                    'http_code' => $report->getResponse() ? $report->getResponse()->getStatusCode() : 200
                ];
                
                // Log da notificação enviada (temporariamente desabilitado)
                // TODO: Criar tabela notification_log
                error_log("Notificação enviada com sucesso - User: {$user_card}, Subscription: {$sub['id']}");
                
            } else {
                $results['error_count']++;
                $results['details'][] = [
                    'subscription_id' => $sub['id'],
                    'success' => false,
                    'http_code' => $report->getResponse() ? $report->getResponse()->getStatusCode() : 0,
                    'error' => $report->getReason()
                ];
                
                // Log do erro (temporariamente desabilitado)
                // TODO: Criar tabela notification_log
                error_log("Erro ao enviar notificação - User: {$user_card}, Subscription: {$sub['id']}, Erro: " . $report->getReason());
            }
            
        } catch (Exception $e) {
            $results['error_count']++;
            $results['details'][] = [
                'subscription_id' => $sub['id'],
                'success' => false,
                'error' => $e->getMessage()
            ];
            
            error_log("Erro ao enviar push notification: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => $results['success_count'] > 0,
        'message' => "Processadas {$results['total_subscriptions']} subscriptions",
        'results' => $results
    ]);
    
} catch (Exception $e) {
    error_log("Erro no send_push_notification_app.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de conexão com banco de dados',
        'error' => $e->getMessage()
    ]);
}
?> 