<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ADICIONAR AUTOLOAD DO COMPOSER
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die(json_encode([
        'success' => false,
        'error' => 'Composer autoload não encontrado',
        'path_checked' => __DIR__ . '/vendor/autoload.php',
        'solution' => 'Execute: composer install'
    ]));
}

require_once 'vapid_config.php';
require_once 'Adm/php/banco.php';

// Verificar se biblioteca WebPush está disponível
if (!class_exists('Minishlink\WebPush\WebPush')) {
    die(json_encode([
        'success' => false,
        'error' => 'Biblioteca web-push não instalada',
        'solution' => 'Execute: composer require minishlink/web-push'
    ]));
}

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// ... resto do código

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obter dados
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = [
            'user_card' => '6338507346',
            'agendamento_id' => 93,
            'tipo_notificacao' => 'agendamento_confirmado',
            'titulo' => 'Teste Real de Notificação',
            'mensagem' => 'Esta notificação foi enviada via WebPush!'
        ];
    } else {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
    }
    
    if (!$data || !isset($data['user_card'])) {
        throw new Exception('user_card não fornecido');
    }
    
    $userCard = $data['user_card'];
    
    // Buscar subscriptions
    $stmt = $pdo->prepare("
        SELECT id, endpoint, p256dh_key, auth_key, settings 
        FROM sind.push_subscriptions 
        WHERE user_card = :user_card AND is_active = true
    ");
    $stmt->execute([':user_card' => $userCard]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        throw new Exception('Nenhuma subscription ativa encontrada');
    }
    
    // Configurar WebPush
    $auth = [
        'VAPID' => [
            'subject' => VAPID_SUBJECT,
            'publicKey' => VAPID_PUBLIC_KEY,
            'privateKey' => VAPID_PRIVATE_KEY
        ]
    ];
    
    $webPush = new WebPush($auth);
    $webPush->setAutomaticPadding(false);
    
    // Preparar payload
    $payload = json_encode([
        'title' => $data['titulo'] ?? 'Notificação',
        'body' => $data['mensagem'] ?? 'Você tem uma nova notificação',
        'icon' => '/icons/icon-192x192.png',
        'badge' => '/icons/icon-192x192.png',
        'data' => [
            'url' => '/dashboard/agendamentos',
            'agendamento_id' => $data['agendamento_id'] ?? null
        ]
    ]);
    
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    // Enviar para cada subscription
    foreach ($subscriptions as $sub) {
        try {
            // Debug: mostrar dados da subscription
            $debugSub = [
                'id' => $sub['id'],
                'endpoint_length' => strlen($sub['endpoint']),
                'p256dh_length' => strlen($sub['p256dh_key']),
                'auth_length' => strlen($sub['auth_key']),
                'endpoint_start' => substr($sub['endpoint'], 0, 50)
            ];
            
            // Verificar se chaves estão em base64url válido
            $p256dh_decoded = base64_decode(strtr($sub['p256dh_key'], '-_', '+/'));
            $auth_decoded = base64_decode(strtr($sub['auth_key'], '-_', '+/'));
            
            $debugSub['p256dh_decoded_length'] = strlen($p256dh_decoded);
            $debugSub['auth_decoded_length'] = strlen($auth_decoded);
            
            // Criar subscription
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh_key'],
                    'auth' => $sub['auth_key']
                ]
            ]);
            
            $debugSub['subscription_created'] = true;
            
            // Tentar enviar
            $report = $webPush->sendOneNotification($subscription, $payload);
            
            if ($report->isSuccess()) {
                $successCount++;
                $results[] = [
                    'subscription_id' => $sub['id'],
                    'success' => true,
                    'debug' => $debugSub
                ];
            } else {
                $errorCount++;
                $results[] = [
                    'subscription_id' => $sub['id'],
                    'success' => false,
                    'error' => $report->getReason(),
                    'debug' => $debugSub
                ];
            }
            
        } catch (Exception $e) {
            $errorCount++;
            $results[] = [
                'subscription_id' => $sub['id'],
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'debug' => $debugSub ?? null
            ];
        }
    }
    
    echo json_encode([
        'success' => $successCount > 0,
        'message' => "Processadas {$successCount} notificações com sucesso",
        'results' => [
            'total_subscriptions' => count($subscriptions),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'details' => $results
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>