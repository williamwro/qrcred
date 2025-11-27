<?php
/**
 * Verificar Notificações Pendentes de Agendamentos
 * Endpoint: https://sas.makecard.com.br/check_agendamentos_notifications_app.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir conexão com banco
require_once 'Adm/php/banco.php';

/**
 * Função para log de debug
 */
function debugLog($message) {
    error_log("[CHECK_NOTIFICATIONS] " . $message);
}

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    debugLog("Verificando notificações pendentes...");
    
    // Buscar agendamentos com data_agendada preenchida que ainda não tiveram notificação enviada
    $query = "
        SELECT DISTINCT
            a.id as agendamento_id,
            a.cod_associado as user_card,
            a.data_agendada,
            a.profissional,
            a.especialidade,
            c.nome as convenio_nome,
            'agendamento_confirmado' as tipo_notificacao
        FROM sind.agendamento a
        LEFT JOIN sind.convenios c ON c.codigo = a.cod_convenio
        LEFT JOIN notification_log nl ON (
            nl.agendamento_id = a.id 
            AND nl.tipo_notificacao = 'agendamento_confirmado'
            AND nl.status = 'enviado'
        )
        WHERE a.data_agendada IS NOT NULL
          AND a.data_agendada >= CURRENT_TIMESTAMP
          AND nl.id IS NULL
          AND a.status = 1
        ORDER BY a.data_agendada ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    debugLog("Encontradas " . count($notifications) . " notificações pendentes");
    
    // Adicionar lembretes 24h e 1h se necessário
    $allNotifications = [];
    
    foreach ($notifications as $notification) {
        // Notificação de confirmação
        $allNotifications[] = $notification;
        
        $dataAgendada = new DateTime($notification['data_agendada']);
        $agora = new DateTime();
        
        // Verificar se precisa de lembrete 24h
        $diff24h = $agora->diff($dataAgendada);
        $horasRestantes = ($diff24h->days * 24) + $diff24h->h;
        
        if ($horasRestantes <= 24 && $horasRestantes > 1) {
            // Verificar se lembrete 24h já foi enviado
            $checkQuery = "
                SELECT id FROM sind.notification_log 
                WHERE agendamento_id = :agendamento_id 
                  AND tipo_notificacao = 'lembrete_24h'
                  AND status = 'enviado'
            ";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([':agendamento_id' => $notification['agendamento_id']]);
            
            if (!$checkStmt->fetch()) {
                $lembrete24h = $notification;
                $lembrete24h['tipo_notificacao'] = 'lembrete_24h';
                $allNotifications[] = $lembrete24h;
            }
        }
        
        // Verificar se precisa de lembrete 1h
        if ($horasRestantes <= 1 && $horasRestantes > 0) {
            // Verificar se lembrete 1h já foi enviado
            $checkQuery = "
                SELECT id FROM sind.notification_log 
                WHERE agendamento_id = :agendamento_id 
                  AND tipo_notificacao = 'lembrete_1h'
                  AND status = 'enviado'
            ";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([':agendamento_id' => $notification['agendamento_id']]);
            
            if (!$checkStmt->fetch()) {
                $lembrete1h = $notification;
                $lembrete1h['tipo_notificacao'] = 'lembrete_1h';
                $allNotifications[] = $lembrete1h;
            }
        }
    }
    
    // Remover duplicatas baseado em agendamento_id + tipo_notificacao
    $uniqueNotifications = [];
    $seen = [];
    
    foreach ($allNotifications as $notif) {
        $key = $notif['agendamento_id'] . '_' . $notif['tipo_notificacao'];
        if (!isset($seen[$key])) {
            $uniqueNotifications[] = $notif;
            $seen[$key] = true;
        }
    }
    
    debugLog("Total de notificações únicas: " . count($uniqueNotifications));
    
    echo json_encode([
        'success' => true,
        'message' => 'Verificação concluída',
        'notifications' => $uniqueNotifications,
        'count' => count($uniqueNotifications),
        'timestamp' => date('Y-m-d H:i:s')
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