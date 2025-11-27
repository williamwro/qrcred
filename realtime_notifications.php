<?php
/**
 * SISTEMA DE NOTIFICAÇÕES EM TEMPO REAL
 * Server-Sent Events (SSE) + PostgreSQL LISTEN/NOTIFY
 * 
 * Este script escuta notificações do PostgreSQL e repassa para o frontend
 * via Server-Sent Events, permitindo atualizações em tempo real sem polling.
 */

// Configurar headers para Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Nginx fix

// Evitar timeout do PHP
set_time_limit(0);
ini_set('max_execution_time', 0);

// Função para enviar evento SSE
function sendSSE($event, $data, $id = null) {
    if ($id !== null) {
        echo "id: $id\n";
    }
    echo "event: $event\n";
    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Forçar envio imediato
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
}

// Função para log de debug
function logDebug($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] REALTIME: $message\n";
    file_put_contents(__DIR__ . '/realtime_notifications.log', $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    // Incluir conexão com banco
    if (!file_exists(__DIR__ . '/Adm/php/banco.php')) {
        throw new Exception('Arquivo de conexão com banco não encontrado');
    }
    
    require_once __DIR__ . '/Adm/php/banco.php';
    
    // Conectar ao PostgreSQL
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logDebug("Conexão estabelecida, iniciando LISTEN...");
    
    // Configurar LISTEN para os canais de notificação
    $channels = ['new_assinatura_digital', 'update_assinatura_digital'];
    
    foreach ($channels as $channel) {
        $pdo->exec("LISTEN $channel");
        logDebug("Escutando canal: $channel");
    }
    
    // Enviar evento inicial de conexão
    sendSSE('connected', [
        'status' => 'listening',
        'channels' => $channels,
        'timestamp' => time(),
        'server_time' => date('Y-m-d H:i:s')
    ]);
    
    logDebug("SSE iniciado, aguardando notificações...");
    
    // Loop principal de escuta
    $lastHeartbeat = time();
    $notificationCount = 0;
    
    while (true) {
        // Verificar se cliente ainda está conectado
        if (connection_aborted()) {
            logDebug("Cliente desconectado, encerrando...");
            break;
        }
        
        // Heartbeat a cada 30 segundos
        if (time() - $lastHeartbeat >= 30) {
            sendSSE('heartbeat', [
                'timestamp' => time(),
                'notifications_received' => $notificationCount,
                'uptime_seconds' => time() - ($_SERVER['REQUEST_TIME'] ?? time())
            ]);
            $lastHeartbeat = time();
        }
        
        // Verificar notificações do PostgreSQL
        $result = $pdo->query("SELECT pg_notification_name, pg_notification_payload FROM pg_notifies() WHERE pg_notification_name IN ('" . implode("','", $channels) . "')");
        
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $channel = $row['pg_notification_name'];
                $payload = $row['pg_notification_payload'];
                
                logDebug("Notificação recebida no canal '$channel': $payload");
                
                // Decodificar payload JSON
                $data = json_decode($payload, true);
                if ($data === null) {
                    logDebug("Erro ao decodificar JSON: $payload");
                    continue;
                }
                
                // Adicionar informações extras
                $data['channel'] = $channel;
                $data['server_timestamp'] = time();
                $data['notification_id'] = uniqid('notif_', true);
                
                // Enviar via SSE baseado no tipo de evento
                switch ($data['event_type']) {
                    case 'new_signature':
                        sendSSE('new_signature', $data, $data['notification_id']);
                        logDebug("Nova assinatura enviada via SSE: ID " . $data['data']['id']);
                        break;
                        
                    case 'signature_updated':
                        sendSSE('signature_updated', $data, $data['notification_id']);
                        logDebug("Atualização de assinatura enviada via SSE: ID " . $data['data']['id']);
                        break;
                        
                    default:
                        sendSSE('notification', $data, $data['notification_id']);
                        logDebug("Notificação genérica enviada: " . $data['event_type']);
                }
                
                $notificationCount++;
            }
        }
        
        // Pequena pausa para não sobrecarregar CPU
        usleep(100000); // 0.1 segundo
    }
    
} catch (Exception $e) {
    logDebug("ERRO: " . $e->getMessage());
    
    // Enviar erro para cliente
    sendSSE('error', [
        'message' => 'Erro interno do servidor',
        'timestamp' => time(),
        'details' => $e->getMessage()
    ]);
    
} finally {
    // Cleanup
    if (isset($pdo)) {
        foreach ($channels as $channel) {
            try {
                $pdo->exec("UNLISTEN $channel");
            } catch (Exception $e) {
                logDebug("Erro ao fazer UNLISTEN $channel: " . $e->getMessage());
            }
        }
    }
    
    logDebug("Conexão SSE encerrada");
}
?> 