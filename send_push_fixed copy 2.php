<?php
/**
 * Send Push Fixed
 * Enviar notificação push usando biblioteca web-push
 */

require_once 'Adm/php/banco.php';
require_once 'vapid_config.php';

// Verificar se a biblioteca web-push está disponível
if (!file_exists('vendor/autoload.php')) {
    echo json_encode([
        'success' => false,
        'error' => 'Biblioteca web-push não encontrada. Execute: composer require minishlink/web-push'
    ]);
    exit;
}

require_once 'vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Receber dados do POST
    $userCard = $_POST['user_card'] ?? null;
    $titulo = $_POST['titulo'] ?? 'Nova Notificação';
    $mensagem = $_POST['mensagem'] ?? '';
    $tipoNotificacao = $_POST['tipo_notificacao'] ?? 'geral';
    $agendamentoId = $_POST['agendamento_id'] ?? null;

    if (!$userCard) {
        echo json_encode([
            'success' => false,
            'error' => 'user_card é obrigatório'
        ]);
        exit;
    }

    /** @noinspection PhpUndefinedClassInspection */
    /** @var PDO $pdo */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar subscriptions ativas do usuário
    $stmt = $pdo->prepare("
        SELECT 
            id,
            endpoint,
            COALESCE(p256dh, p256dh_key) as p256dh,
            COALESCE(auth, auth_key) as auth,
            settings
        FROM sind.push_subscriptions 
        WHERE user_card = ? AND is_active = true
    ");
    
    $stmt->execute([$userCard]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($subscriptions)) {
        echo json_encode([
            'success' => false,
            'error' => 'Nenhuma subscription ativa encontrada',
            'user_card' => $userCard
        ]);
        exit;
    }

    // Configurar WebPush
    $auth = [
        'VAPID' => [
            'subject' => VAPID_SUBJECT,
            'publicKey' => VAPID_PUBLIC_KEY,
            'privateKey' => VAPID_PRIVATE_KEY,
        ],
    ];

    $webPush = new WebPush($auth);

    $results = [
        'total_subscriptions' => count($subscriptions),
        'success_count' => 0,
        'error_count' => 0,
        'details' => []
    ];

    // Preparar payload da notificação
    $payload = json_encode([
        'title' => $titulo,
        'body' => $mensagem,
        'icon' => 'https://sas.makecard.com.br/icons/icon-192x192.png',
        'badge' => 'https://sas.makecard.com.br/icons/icon-192x192.png',
        'data' => [
            'tipo' => $tipoNotificacao,
            'agendamento_id' => $agendamentoId,
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
        'tag' => 'agendamento-' . $agendamentoId
    ]);

    // Enviar para cada subscription
    foreach ($subscriptions as $sub) {
        try {
            // Verificar se os dados estão completos
            if (empty($sub['p256dh']) || empty($sub['auth'])) {
                $results['details'][] = [
                    'subscription_id' => $sub['id'],
                    'success' => false,
                    'error' => 'Dados de subscription incompletos'
                ];
                $results['error_count']++;
                continue;
            }

            // Criar objeto Subscription
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
                $results['details'][] = [
                    'subscription_id' => $sub['id'],
                    'success' => true,
                    'http_code' => 201
                ];
                $results['success_count']++;
            } else {
                $results['details'][] = [
                    'subscription_id' => $sub['id'],
                    'success' => false,
                    'http_code' => $report->getResponse()->getStatusCode(),
                    'error' => $report->getReason()
                ];
                $results['error_count']++;
            }

        } catch (Exception $e) {
            $results['details'][] = [
                'subscription_id' => $sub['id'],
                'success' => false,
                'error' => $e->getMessage()
            ];
            $results['error_count']++;
        }
    }

    // Log da notificação (se tabela existir)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notification_log (user_card, subscription_id, notification_type, payload, sent_at, status, agendamento_id)
            VALUES (?, ?, ?, ?, NOW(), ?, ?)
        ");
        
        foreach ($results['details'] as $detail) {
            $status = $detail['success'] ? 'sent' : 'failed';
            $stmt->execute([
                $userCard,
                $detail['subscription_id'],
                $tipoNotificacao,
                $payload,
                $status,
                $agendamentoId
            ]);
        }
    } catch (Exception $e) {
        // Se der erro no log, não interromper o processo
        error_log("Erro ao salvar log de notificação: " . $e->getMessage());
    }

    // Resposta final
    $success = $results['success_count'] > 0;
    
    echo json_encode([
        'success' => $success,
        'message' => "Processadas {$results['total_subscriptions']} subscriptions",
        'results' => $results
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 