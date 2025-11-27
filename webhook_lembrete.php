<?php
/**
 * Webhook Lembrete
 * Envia push notifications de lembrete (24h e 1h antes)
 */

require_once 'Adm/php/banco.php';
require_once 'vapid_config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!file_exists('vendor/autoload.php')) {
    echo json_encode([
        'success' => false,
        'error' => 'Biblioteca web-push não encontrada'
    ]);
    exit;
}

require_once 'vendor/autoload.php';

try {
    // Receber dados JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $agendamentoId = $input['agendamento_id'] ?? null;
    $tipoLembrete = $input['tipo_lembrete'] ?? null; // '24h' ou '1h'
    
    if (!$agendamentoId || !$tipoLembrete) {
        echo json_encode([
            'success' => false,
            'error' => 'agendamento_id e tipo_lembrete são obrigatórios'
        ]);
        exit;
    }
    
    /** @noinspection PhpUndefinedClassInspection */
    /** @var PDO $pdo */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar agendamento
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.data_agendada,
            a.profissional,
            a.especialidade,
            a.convenio_nome,
            s.nome as nome_associado,
            c.cod_verificacao as numero_cartao
        FROM sind.agendamento a
        INNER JOIN sind.associado s ON (a.cod_associado = s.codigo AND a.id_empregador = s.empregador)
        INNER JOIN sind.c_cartaoassociado c ON (s.codigo = c.cod_associado AND s.empregador = c.empregador)
        WHERE a.id = ? AND a.status = 2
    ");
    
    $stmt->execute([$agendamentoId]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agendamento) {
        echo json_encode([
            'success' => false,
            'message' => 'Agendamento não encontrado ou não confirmado'
        ]);
        exit;
    }
    
    // Buscar subscription ativa
    $stmt = $pdo->prepare("
        SELECT 
            id,
            endpoint,
            COALESCE(p256dh, p256dh_key) as p256dh,
            COALESCE(auth, auth_key) as auth
        FROM sind.push_subscriptions 
        WHERE user_card = ? AND is_active = true
        LIMIT 1
    ");
    
    $stmt->execute([$agendamento['numero_cartao']]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription || empty($subscription['p256dh']) || empty($subscription['auth'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário sem notificações ativas'
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
    
    $webPush = new \Minishlink\WebPush\WebPush($auth);
    
    $pushSubscription = \Minishlink\WebPush\Subscription::create([
        'endpoint' => $subscription['endpoint'],
        'keys' => [
            'p256dh' => $subscription['p256dh'],
            'auth' => $subscription['auth']
        ]
    ]);
    
    // Preparar mensagens específicas por tipo
    $dataFormatada = date('d/m/Y \à\s H:i', strtotime($agendamento['data_agendada']));
    
    if ($tipoLembrete === '24h') {
        $titulo = "📅 Lembrete: Agendamento Amanhã";
        $mensagem = "Você tem agendamento amanhã {$dataFormatada}";
        $emoji = "📅";
        $tag = 'lembrete-24h-' . $agendamento['id'];
    } else if ($tipoLembrete === '1h') {
        $titulo = "⏰ Lembrete: Agendamento em 1 hora";
        $mensagem = "Seu agendamento é em 1 hora ({$dataFormatada})";
        $emoji = "⏰";
        $tag = 'lembrete-1h-' . $agendamento['id'];
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Tipo de lembrete inválido. Use 24h ou 1h'
        ]);
        exit;
    }
    
    // Adicionar profissional se disponível
    if (!empty($agendamento['profissional'])) {
        $mensagem .= " com {$agendamento['profissional']}";
    }
    
    // Payload da notificação
    $payload = json_encode([
        'title' => $titulo,
        'body' => $mensagem,
        'icon' => 'https://sas.makecard.com.br/icons/icon-192x192.png',
        'badge' => 'https://sas.makecard.com.br/icons/icon-192x192.png',
        'data' => [
            'tipo' => 'lembrete_agendamento',
            'tipo_lembrete' => $tipoLembrete,
            'agendamento_id' => $agendamento['id'],
            'url' => 'https://sas.makecard.com.br/dashboard',
            'timestamp' => time()
        ],
        'actions' => [
            [
                'action' => 'view',
                'title' => 'Ver Agendamentos',
                'icon' => 'https://sas.makecard.com.br/icons/icon-192x192.png'
            ]
        ],
        'requireInteraction' => true,
        'tag' => $tag
    ]);
    
    // Enviar notificação
    $report = $webPush->sendOneNotification($pushSubscription, $payload);
    
    if ($report->isSuccess()) {
        echo json_encode([
            'success' => true,
            'message' => "Lembrete {$tipoLembrete} enviado com sucesso",
            'agendamento_id' => $agendamento['id'],
            'user_card' => $agendamento['numero_cartao'],
            'nome_associado' => $agendamento['nome_associado'],
            'data_agendada' => $dataFormatada,
            'tipo_lembrete' => $tipoLembrete,
            'titulo_push' => $titulo,
            'mensagem_push' => $mensagem
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Falha ao enviar lembrete',
            'error' => $report->getReason(),
            'http_code' => $report->getResponse()->getStatusCode()
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?> 