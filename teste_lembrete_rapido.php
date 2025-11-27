<?php
/**
 * Teste Lembrete Rápido
 * Usa webhook_push_working.php para testar lembretes
 */

require_once 'Adm/php/banco.php';
require_once 'vapid_config.php';

header('Content-Type: application/json');

// Aceitar tanto GET quanto POST
$agendamentoId = $_GET['agendamento_id'] ?? $_POST['agendamento_id'] ?? null;
$tipoLembrete = $_GET['tipo_lembrete'] ?? $_POST['tipo_lembrete'] ?? '24h';

if (!$agendamentoId) {
    echo json_encode([
        'success' => false,
        'error' => 'agendamento_id é obrigatório',
        'exemplo' => 'teste_lembrete_rapido.php?agendamento_id=65&tipo_lembrete=24h'
    ]);
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
            'message' => 'Usuário sem notificações ativas',
            'user_card' => $agendamento['numero_cartao'],
            'agendamento_id' => $agendamento['id']
        ]);
        exit;
    }
    
    // Configurar WebPush (igual ao que funciona)
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
    
    // Preparar mensagem de lembrete específica
    $dataFormatada = date('d/m/Y \à\s H:i', strtotime($agendamento['data_agendada']));
    
    if ($tipoLembrete === '24h') {
        $titulo = "📅 Lembrete: Agendamento Amanhã";
        $mensagem = "Você tem agendamento amanhã {$dataFormatada}";
        $tag = 'lembrete-24h-' . $agendamento['id'];
    } else if ($tipoLembrete === '1h') {
        $titulo = "⏰ Lembrete: Agendamento em 1 hora";
        $mensagem = "Seu agendamento é em 1 hora ({$dataFormatada})";
        $tag = 'lembrete-1h-' . $agendamento['id'];
    } else {
        $titulo = "🔔 Teste de Lembrete";
        $mensagem = "Testando sistema de lembretes para {$dataFormatada}";
        $tag = 'teste-lembrete-' . $agendamento['id'];
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
            'tipo' => 'lembrete_teste',
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
    
    // Enviar notificação de lembrete
    $report = $webPush->sendOneNotification($pushSubscription, $payload);
    
    if ($report->isSuccess()) {
        // Marcar flag apropriada se for teste real
        if ($tipoLembrete === '24h') {
            $stmt = $pdo->prepare("UPDATE sind.agendamento SET notification_sent_24h = true WHERE id = ?");
            $stmt->execute([$agendamento['id']]);
        } else if ($tipoLembrete === '1h') {
            $stmt = $pdo->prepare("UPDATE sind.agendamento SET notification_sent_1h = true WHERE id = ?");
            $stmt->execute([$agendamento['id']]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Lembrete {$tipoLembrete} enviado com sucesso!",
            'agendamento_id' => $agendamento['id'],
            'user_card' => $agendamento['numero_cartao'],
            'nome_associado' => $agendamento['nome_associado'],
            'data_agendada' => $dataFormatada,
            'tipo_lembrete' => $tipoLembrete,
            'titulo_push' => $titulo,
            'mensagem_push' => $mensagem,
            'timestamp' => date('Y-m-d H:i:s')
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
        'message' => 'Erro: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 