<?php
/**
 * Enviar Lembretes de Agendamentos
 * Script para rodar via cron job - envia push 24h e 1h antes
 */

require_once 'Adm/php/banco.php';
require_once 'vapid_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[LEMBRETES_AGENDAMENTOS] [{$timestamp}] {$message}");
    
    if (php_sapi_name() === 'cli') {
        echo "[{$timestamp}] {$message}\n";
    }
}

function enviarLembrete($agendamento, $tipo) {
    $agendamentoId = $agendamento['id'];
    
    logMessage("Enviando lembrete {$tipo} para agendamento {$agendamentoId}");
    
    // Usar webhook que funciona
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sas.makecard.com.br/webhook_lembrete.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'agendamento_id' => $agendamentoId,
        'tipo_lembrete' => $tipo
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return $result['success'] ?? false;
    }
    
    return false;
}

try {
    logMessage("=== INICIANDO VERIFICAÇÃO DE LEMBRETES ===");
    
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $agora = new DateTime();
    $em24h = clone $agora;
    $em24h->add(new DateInterval('P1D')); // +24 horas
    
    $em1h = clone $agora;
    $em1h->add(new DateInterval('PT1H')); // +1 hora
    
    logMessage("Verificando lembretes para:");
    logMessage("- 24h: entre {$agora->format('Y-m-d H:i')} e {$em24h->format('Y-m-d H:i')}");
    logMessage("- 1h: entre {$agora->format('Y-m-d H:i')} e {$em1h->format('Y-m-d H:i')}");
    
    // === VERIFICAR LEMBRETES 24H ===
    $sql24h = "
        SELECT 
            a.id,
            a.data_agendada,
            a.profissional,
            c.cod_verificacao as numero_cartao,
            s.nome as nome_associado
        FROM sind.agendamento a
        INNER JOIN sind.associado s ON (a.cod_associado = s.codigo AND a.id_empregador = s.empregador)
        INNER JOIN sind.c_cartaoassociado c ON (s.codigo = c.cod_associado AND s.empregador = c.empregador)
        WHERE 
            a.status = 2 
            AND a.data_agendada IS NOT NULL
            AND a.data_agendada BETWEEN ? AND ?
            AND (a.notification_sent_24h IS NULL OR a.notification_sent_24h = false)
            AND c.cod_situacaocartao = 1
    ";
    
    $stmt24h = $pdo->prepare($sql24h);
    $stmt24h->execute([$agora->format('Y-m-d H:i:s'), $em24h->format('Y-m-d H:i:s')]);
    $lembretes24h = $stmt24h->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Encontrados " . count($lembretes24h) . " lembretes 24h");
    
    // === VERIFICAR LEMBRETES 1H ===
    $sql1h = "
        SELECT 
            a.id,
            a.data_agendada,
            a.profissional,
            c.cod_verificacao as numero_cartao,
            s.nome as nome_associado
        FROM sind.agendamento a
        INNER JOIN sind.associado s ON (a.cod_associado = s.codigo AND a.id_empregador = s.empregador)
        INNER JOIN sind.c_cartaoassociado c ON (s.codigo = c.cod_associado AND s.empregador = c.empregador)
        WHERE 
            a.status = 2 
            AND a.data_agendada IS NOT NULL
            AND a.data_agendada BETWEEN ? AND ?
            AND (a.notification_sent_1h IS NULL OR a.notification_sent_1h = false)
            AND c.cod_situacaocartao = 1
    ";
    
    $stmt1h = $pdo->prepare($sql1h);
    $stmt1h->execute([$agora->format('Y-m-d H:i:s'), $em1h->format('Y-m-d H:i:s')]);
    $lembretes1h = $stmt1h->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Encontrados " . count($lembretes1h) . " lembretes 1h");
    
    $results = [
        'lembretes_24h' => [
            'total' => count($lembretes24h),
            'enviados' => 0,
            'erros' => 0
        ],
        'lembretes_1h' => [
            'total' => count($lembretes1h),
            'enviados' => 0,
            'erros' => 0
        ]
    ];
    
    // === PROCESSAR LEMBRETES 24H ===
    foreach ($lembretes24h as $agendamento) {
        if (enviarLembrete($agendamento, '24h')) {
            // Marcar como enviado
            $stmt = $pdo->prepare("UPDATE sind.agendamento SET notification_sent_24h = true WHERE id = ?");
            $stmt->execute([$agendamento['id']]);
            $results['lembretes_24h']['enviados']++;
            logMessage("✅ Lembrete 24h enviado: ID {$agendamento['id']}");
        } else {
            $results['lembretes_24h']['erros']++;
            logMessage("❌ Erro lembrete 24h: ID {$agendamento['id']}");
        }
    }
    
    // === PROCESSAR LEMBRETES 1H ===
    foreach ($lembretes1h as $agendamento) {
        if (enviarLembrete($agendamento, '1h')) {
            // Marcar como enviado
            $stmt = $pdo->prepare("UPDATE sind.agendamento SET notification_sent_1h = true WHERE id = ?");
            $stmt->execute([$agendamento['id']]);
            $results['lembretes_1h']['enviados']++;
            logMessage("✅ Lembrete 1h enviado: ID {$agendamento['id']}");
        } else {
            $results['lembretes_1h']['erros']++;
            logMessage("❌ Erro lembrete 1h: ID {$agendamento['id']}");
        }
    }
    
    logMessage("=== PROCESSAMENTO CONCLUÍDO ===");
    logMessage("Lembretes 24h: {$results['lembretes_24h']['enviados']}/{$results['lembretes_24h']['total']}");
    logMessage("Lembretes 1h: {$results['lembretes_1h']['enviados']}/{$results['lembretes_1h']['total']}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Processamento de lembretes concluído',
        'timestamp' => date('Y-m-d H:i:s'),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    logMessage("ERRO CRÍTICO: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar lembretes',
        'error' => $e->getMessage()
    ]);
}
?> 