<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$debug = ['step' => 'inicio', 'timestamp' => date('Y-m-d H:i:s')];

try {
    // Carregar configurações
    $debug['step'] = 'carregando_vapid';
    require_once 'vapid_config.php';
    $debug['vapid_loaded'] = true;
    
    // Carregar banco
    $debug['step'] = 'carregando_banco';
    require_once 'Adm/php/banco.php';
    $debug['banco_loaded'] = true;
    
    // Conectar banco
    $debug['step'] = 'conectando_banco';
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $debug['pdo_connected'] = true;
    
    // Usar dados de teste se GET, ou ler input se POST
    $debug['step'] = 'obtendo_dados';
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = [
            'user_card' => '6338507346',
            'agendamento_id' => 93,
            'tipo_notificacao' => 'agendamento_confirmado',
            'titulo' => 'Teste via GET',
            'mensagem' => 'Notificação de teste automática'
        ];
        $debug['data_source'] = 'GET_TEST';
    } else {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $debug['data_source'] = 'POST_JSON';
        $debug['input_length'] = strlen($input);
    }
    
    $debug['data_received'] = $data ? array_keys($data) : null;
    
    if (!$data || !isset($data['user_card'])) {
        throw new Exception('user_card não fornecido');
    }
    
    $userCard = $data['user_card'];
    $debug['user_card'] = $userCard;
    
    // Buscar subscriptions
    $debug['step'] = 'buscando_subscriptions';
    $stmt = $pdo->prepare("
        SELECT id, endpoint, p256dh_key, auth_key, settings 
        FROM sind.push_subscriptions 
        WHERE user_card = :user_card AND is_active = true
    ");
    $stmt->execute([':user_card' => $userCard]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug['subscriptions_found'] = count($subscriptions);
    
    if (empty($subscriptions)) {
        throw new Exception('Nenhuma subscription ativa encontrada');
    }
    
    // Preparar payload
    $debug['step'] = 'preparando_payload';
    $payload = json_encode([
        'title' => $data['titulo'] ?? 'Notificação',
        'body' => $data['mensagem'] ?? 'Você tem uma nova notificação',
        'icon' => '/icons/icon-192x192.png',
        'data' => [
            'url' => '/dashboard/agendamentos',
            'agendamento_id' => $data['agendamento_id'] ?? null
        ]
    ]);
    $debug['payload_size'] = strlen($payload);
    $debug['endpoint_sample'] = substr($subscriptions[0]['endpoint'], 0, 60) . '...';
    
    // Verificar VAPID
    $debug['step'] = 'verificando_vapid';
    $debug['vapid_public_key'] = substr(VAPID_PUBLIC_KEY, 0, 20) . '...';
    $debug['vapid_private_key'] = substr(VAPID_PRIVATE_KEY, 0, 20) . '...';
    $debug['vapid_subject'] = VAPID_SUBJECT;
    
    // SUCESSO
    echo json_encode([
        'success' => true,
        'message' => 'Debug completo - tudo pronto para enviar',
        'debug' => $debug,
        'subscriptions_count' => count($subscriptions),
        'next_step' => 'Implementar envio real com biblioteca WebPush'
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    $debug['error_type'] = 'PDO';
    $debug['error_message'] = $e->getMessage();
    echo json_encode(['success' => false, 'debug' => $debug], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $debug['error_type'] = 'Exception';
    $debug['error_message'] = $e->getMessage();
    echo json_encode(['success' => false, 'debug' => $debug], JSON_PRETTY_PRINT);
}
?>