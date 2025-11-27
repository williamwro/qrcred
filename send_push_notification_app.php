<?php
/**
 * Enviar Push Notifications
 * Endpoint: https://sas.makecard.com.br/send_push_notification_app.php
 */
require_once 'vapid_config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir conexão com banco
require_once 'Adm/php/banco.php';

// ⚠️ CONFIGURE SUAS CHAVES VAPID AQUI
// Use a CHAVE PRIVADA correspondente à pública que você colocou no frontend
//define('VAPID_PUBLIC_KEY', 'BJJmOHkytqi0v_7sfKNkxjt1ID_w9nGpra4SHpi_Eu_qgdc9W5SDjkTwr7l_fa-TE6D53VfXs_S3cBSeq2OrF4o');
//define('VAPID_PRIVATE_KEY', 'gdc9W5SDjkTwr7l_fa-TE6D53VfXs_S3cBSeq2OrF4o'); // ⚠️ SUBSTITUA PELA SUA CHAVE PRIVADA/
//define('VAPID_SUBJECT', 'mailto:admin@sas.makecard.com.br');

/**
 * Função para log de debug
 */
function debugLog($message) {
    error_log("[SEND_PUSH] " . $message);
}

/**
 * Função para converter base64url
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Função para gerar JWT token para VAPID
 */
function generateJWT($audience, $subject, $publicKey, $privateKey) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'ES256']);
    $payload = json_encode([
        'aud' => $audience,
        'exp' => time() + 43200, // 12 horas
        'sub' => $subject
    ]);
    
    $headerEncoded = base64url_encode($header);
    $payloadEncoded = base64url_encode($payload);
    
    $data = $headerEncoded . '.' . $payloadEncoded;
    
    // Simular assinatura (em produção, use uma biblioteca JWT real)
    $signature = base64url_encode(hash_hmac('sha256', $data, $privateKey, true));
    
    return $data . '.' . $signature;
}

/**
 * Enviar push notification via WebPush
 */
function sendWebPush($endpoint, $payload, $p256dh, $auth) {
    try {
        debugLog("Enviando push para endpoint: " . substr($endpoint, 0, 50) . "...");
        
        // Parse do endpoint para obter audience
        $urlParts = parse_url($endpoint);
        $audience = $urlParts['scheme'] . '://' . $urlParts['host'];
        
        // Gerar JWT token
        $jwt = generateJWT($audience, VAPID_SUBJECT, VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY);
        
        // Headers para requisição
        $headers = [
            'Authorization: vapid t=' . $jwt . ', k=' . VAPID_PUBLIC_KEY,
            'Content-Type: application/octet-stream',
            'TTL: 2419200', // 4 semanas
            'Content-Encoding: aes128gcm'
        ];
        
        // Configurar cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        debugLog("Resposta HTTP: {$httpCode}");
        
        if ($error) {
            throw new Exception("Erro cURL: {$error}");
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            debugLog("Push enviado com sucesso");
            return ['success' => true, 'http_code' => $httpCode, 'response' => $response];
        } else {
            debugLog("Falha ao enviar push: HTTP {$httpCode}");
            return ['success' => false, 'http_code' => $httpCode, 'response' => $response];
        }
        
    } catch (Exception $e) {
        debugLog("Erro ao enviar push: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Registrar log de notificação no banco
 */
function logNotification($pdo, $userCard, $agendamentoId, $subscriptionId, $tipoNotificacao, $titulo, $mensagem, $dataAgendada, $profissional, $especialidade, $convenioNome, $status, $responseData, $errorMessage = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notification_log 
            (user_card, agendamento_id, subscription_id, tipo_notificacao, titulo, mensagem, data_agendada, profissional, especialidade, convenio_nome, status, response_data, error_message, sent_at, created_at)
            VALUES 
            (:user_card, :agendamento_id, :subscription_id, :tipo_notificacao, :titulo, :mensagem, :data_agendada, :profissional, :especialidade, :convenio_nome, :status, :response_data, :error_message, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            ':user_card' => $userCard,
            ':agendamento_id' => $agendamentoId,
            ':subscription_id' => $subscriptionId,
            ':tipo_notificacao' => $tipoNotificacao,
            ':titulo' => $titulo,
            ':mensagem' => $mensagem,
            ':data_agendada' => $dataAgendada,
            ':profissional' => $profissional,
            ':especialidade' => $especialidade,
            ':convenio_nome' => $convenioNome,
            ':status' => $status,
            ':response_data' => $responseData ? json_encode($responseData) : null,
            ':error_message' => $errorMessage
        ]);
        
        debugLog("Log de notificação registrado no banco");
        
    } catch (Exception $e) {
        debugLog("Erro ao registrar log: " . $e->getMessage());
    }
}

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obter dados da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('JSON inválido na requisição');
    }
    
    $userCard = $data['user_card'] ?? null;
    $agendamentoId = $data['agendamento_id'] ?? null;
    $tipoNotificacao = $data['tipo_notificacao'] ?? null;
    $titulo = $data['titulo'] ?? null;
    $mensagem = $data['mensagem'] ?? null;
    $dataAgendada = $data['data_agendada'] ?? null;
    $profissional = $data['profissional'] ?? null;
    $especialidade = $data['especialidade'] ?? null;
    $convenioNome = $data['convenio_nome'] ?? null;
    
    if (!$userCard || !$titulo || !$mensagem) {
        throw new Exception('Parâmetros obrigatórios faltando');
    }
    
    debugLog("Processando notificação para usuário: {$userCard}");
    
    // Buscar subscriptions ativas do usuário
    $stmt = $pdo->prepare("
        SELECT id, endpoint, p256dh, p256dh_key, auth, auth_key, settings 
        FROM sind.push_subscriptions 
        WHERE user_card = :user_card AND is_active = true
    ");
    $stmt->execute([':user_card' => $userCard]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        throw new Exception('Nenhuma subscription ativa encontrada para este usuário');
    }
    
    debugLog("Encontradas " . count($subscriptions) . " subscriptions ativas");
    
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($subscriptions as $subscription) {
        $subscriptionId = $subscription['id'];
        $settings = json_decode($subscription['settings'], true);
        
        // Verificar se o tipo de notificação está habilitado
        $sendNotification = true;
        if ($tipoNotificacao === 'agendamento_confirmado' && !($settings['agendamentoConfirmado'] ?? true)) {
            $sendNotification = false;
        } elseif ($tipoNotificacao === 'lembrete_24h' && !($settings['lembrete24h'] ?? true)) {
            $sendNotification = false;
        } elseif ($tipoNotificacao === 'lembrete_1h' && !($settings['lembrete1h'] ?? true)) {
            $sendNotification = false;
        }
        
        if (!$sendNotification) {
            debugLog("Notificação {$tipoNotificacao} desabilitada para subscription {$subscriptionId}");
            continue;
        }
        
        // Preparar payload da notificação
        $payload = json_encode([
            'title' => $titulo,
            'body' => $mensagem,
            'icon' => '/icons/icon-192x192.png',
            'badge' => '/icons/icon-192x192.png',
            'data' => [
                'url' => '/dashboard/agendamentos',
                'agendamento_id' => $agendamentoId,
                'tipo' => $tipoNotificacao
            ]
        ]);
        
        // Enviar push notification
        $result = sendWebPush(
            $subscription['endpoint'],
            $payload,
            $subscription['p256dh_key'],
            $subscription['auth_key']
        );
        
        // Registrar resultado
        if ($result['success']) {
            $successCount++;
            logNotification($pdo, $userCard, $agendamentoId, $subscriptionId, $tipoNotificacao, $titulo, $mensagem, $dataAgendada, $profissional, $especialidade, $convenioNome, 'enviado', $result);
        } else {
            $errorCount++;
            logNotification($pdo, $userCard, $agendamentoId, $subscriptionId, $tipoNotificacao, $titulo, $mensagem, $dataAgendada, $profissional, $especialidade, $convenioNome, 'erro', $result, $result['error'] ?? 'Erro desconhecido');
        }
        
        $results[] = [
            'subscription_id' => $subscriptionId,
            'success' => $result['success'],
            'http_code' => $result['http_code'] ?? null,
            'error' => $result['error'] ?? null
        ];
    }
    
    debugLog("Push notifications enviados: {$successCount} sucessos, {$errorCount} erros");
    
    echo json_encode([
        'success' => $successCount > 0,
        'message' => "Processadas " . count($subscriptions) . " subscriptions",
        'results' => [
            'total_subscriptions' => count($subscriptions),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'details' => $results
        ]
    ]);
    
} catch (PDOException $e) {
    debugLog("Erro de banco: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de conexão com banco de dados',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    debugLog("Erro geral: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 