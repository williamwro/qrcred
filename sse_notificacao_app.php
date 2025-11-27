<?php
/**
 * NOTIFICAÇÃO EM TEMPO REAL PARA APP MOBILE
 * Server-Sent Events específico para monitorar assinatura digital de um usuário
 * 
 * Este endpoint escuta notificações do PostgreSQL LISTEN/NOTIFY
 * e notifica o app quando um usuário específico assinar digitalmente
 */

// Configurar headers para Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
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
    $logMessage = "[$timestamp] SSE_APP: $message\n";
    file_put_contents(__DIR__ . '/sse_app_notifications.log', $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    // Incluir conexão com banco
    require_once __DIR__ . '/Adm/php/banco.php';
    
    // Conectar ao PostgreSQL
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Pegar código do usuário para monitorar (via GET parameter)
    $codigo_usuario = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
    
    if (empty($codigo_usuario)) {
        sendSSE('error', [
            'message' => 'Código do usuário é obrigatório',
            'example' => 'sse_notificacao_app.php?codigo=12345'
        ]);
        exit;
    }
    
    logDebug("Iniciando monitoramento para usuário: $codigo_usuario");
    
    // Configurar LISTEN para os canais de notificação
    $channels = ['new_assinatura_digital', 'update_assinatura_digital'];
    
    foreach ($channels as $channel) {
        $pdo->exec("LISTEN $channel");
        logDebug("Escutando canal: $channel");
    }
    
    // Enviar evento inicial de conexão
    sendSSE('connected', [
        'status' => 'listening',
        'codigo_usuario' => $codigo_usuario,
        'channels' => $channels,
        'timestamp' => time(),
        'server_time' => date('Y-m-d H:i:s')
    ]);
    
    logDebug("SSE iniciado para usuário $codigo_usuario, aguardando notificações...");
    
    // Verificar status inicial do usuário
    $sqlStatus = "SELECT id, codigo, nome, celular, data_hora, autorizado, has_signed, aceitou_termo 
                  FROM sind.associados_sasmais 
                  WHERE codigo = :codigo 
                  LIMIT 1";
    
    $stmtStatus = $pdo->prepare($sqlStatus);
    $stmtStatus->bindParam(':codigo', $codigo_usuario, PDO::PARAM_STR);
    $stmtStatus->execute();
    $statusAtual = $stmtStatus->fetch(PDO::FETCH_ASSOC);
    
    if ($statusAtual) {
        sendSSE('status_inicial', [
            'jaAderiu' => true,
            'dados' => $statusAtual,
            'mensagem' => 'Usuário já aderiu ao Sascred'
        ]);
        logDebug("Status inicial: usuário $codigo_usuario já aderiu");
    } else {
        sendSSE('status_inicial', [
            'jaAderiu' => false,
            'dados' => null,
            'mensagem' => 'Usuário ainda não aderiu ao Sascred'
        ]);
        logDebug("Status inicial: usuário $codigo_usuario ainda não aderiu");
    }
    
    // Loop principal de escuta
    $lastHeartbeat = time();
    $notificationCount = 0;
    
    while (true) {
        // Verificar se cliente ainda está conectado
        if (connection_aborted()) {
            logDebug("Cliente desconectado, encerrando monitoramento para usuário $codigo_usuario");
            break;
        }
        
        // Heartbeat a cada 30 segundos
        if (time() - $lastHeartbeat >= 30) {
            sendSSE('heartbeat', [
                'timestamp' => time(),
                'codigo_usuario' => $codigo_usuario,
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
                
                // Verificar se a notificação é para o usuário monitorado
                $codigoNotificacao = $data['data']['codigo'] ?? '';
                
                if ($codigoNotificacao === $codigo_usuario) {
                    logDebug("Notificação relevante para usuário $codigo_usuario");
                    
                    // Adicionar informações extras
                    $data['channel'] = $channel;
                    $data['server_timestamp'] = time();
                    $data['notification_id'] = uniqid('notif_', true);
                    
                    // Enviar via SSE baseado no tipo de evento
                    switch ($data['event_type']) {
                        case 'new_signature':
                            sendSSE('nova_assinatura', [
                                'codigo' => $codigo_usuario,
                                'nome' => $data['data']['nome'],
                                'mensagem' => 'Assinatura digital realizada com sucesso!',
                                'jaAderiu' => true,
                                'dados' => $data['data'],
                                'timestamp' => time()
                            ]);
                            logDebug("Nova assinatura notificada para usuário $codigo_usuario");
                            break;
                            
                        case 'signature_updated':
                            $changes = $data['data']['changes'] ?? [];
                            
                            // Verificar se houve mudança relevante para mostrar menu
                            if (isset($changes['has_signed']) && $changes['has_signed']['new'] == true) {
                                sendSSE('assinatura_confirmada', [
                                    'codigo' => $codigo_usuario,
                                    'nome' => $data['data']['nome'],
                                    'mensagem' => 'Assinatura confirmada! Menu completo liberado.',
                                    'jaAderiu' => true,
                                    'dados' => $data['data'],
                                    'timestamp' => time()
                                ]);
                                logDebug("Assinatura confirmada para usuário $codigo_usuario");
                            } elseif (isset($changes['autorizado']) && $changes['autorizado']['new'] == true) {
                                sendSSE('usuario_autorizado', [
                                    'codigo' => $codigo_usuario,
                                    'nome' => $data['data']['nome'],
                                    'mensagem' => 'Usuário autorizado! Menu completo liberado.',
                                    'jaAderiu' => true,
                                    'dados' => $data['data'],
                                    'timestamp' => time()
                                ]);
                                logDebug("Usuário autorizado $codigo_usuario");
                            }
                            break;
                            
                        default:
                            sendSSE('notification', $data, $data['notification_id']);
                            logDebug("Notificação genérica enviada para usuário $codigo_usuario");
                    }
                    
                    $notificationCount++;
                } else {
                    logDebug("Notificação ignorada (código diferente): $codigoNotificacao vs $codigo_usuario");
                }
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
    
    logDebug("Conexão SSE encerrada para usuário $codigo_usuario");
}
?> 