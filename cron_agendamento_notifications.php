#!/usr/bin/env php
<?php
/**
 * Cron Job - Agendamento Notifications
 * Script para executar automaticamente a verificação de agendamentos
 * 
 * Configurar no crontab:
 * Execute a cada 5 minutos
 */

// Verificar se está sendo executado via CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Este script deve ser executado apenas via CLI');
}

// Evitar timeout
set_time_limit(0);
ini_set('memory_limit', '256M');

// Incluir dependências
require_once __DIR__ . '/Adm/php/banco.php';

function logCron($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $pid = getmypid();
    echo "[{$timestamp}] [{$level}] [PID:{$pid}] {$message}\n";
}

function sendCurlRequest($url, $timeout = 30) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SAS-CronJob-AgendamentoNotifications/1.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($result === false) {
        throw new Exception("Erro cURL: {$error}");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP Error: {$httpCode}");
    }
    
    return $result;
}

try {
    logCron("=== INICIANDO CRON JOB - AGENDAMENTO NOTIFICATIONS ===");
    
    // Verificar se há conexão com banco
    try {
        /** @noinspection PhpUndefinedClassInspection */
        $pdo = Banco::conectar_postgres();
        logCron("✅ Conexão com banco estabelecida");
    } catch (Exception $e) {
        logCron("❌ ERRO: Falha na conexão com banco: " . $e->getMessage(), 'ERROR');
        exit(1);
    }
    
    // Verificar quantos agendamentos estão pendentes (para log)
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM sind.agendamento 
            WHERE data_agendada IS NOT NULL 
            AND (notification_sent_confirmado IS NULL OR notification_sent_confirmado = false)
            AND status = 1
        ");
        $pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        logCron("📊 Agendamentos pendentes de notificação: {$pendingCount}");
        
        if ($pendingCount == 0) {
            logCron("ℹ️ Nenhum agendamento pendente. Finalizando cron job.");
            exit(0);
        }
        
    } catch (Exception $e) {
        logCron("⚠️ Aviso: Não foi possível verificar agendamentos pendentes: " . $e->getMessage(), 'WARN');
    }
    
    // Executar verificação de notificações
    logCron("🚀 Executando verificação de notificações...");
    
    $url = 'https://sas.makecard.com.br/check_agendamentos_notifications.php';
    
    $startTime = microtime(true);
    $response = sendCurlRequest($url, 60); // 60 segundos de timeout
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime), 2);
    
    logCron("⏱️ Tempo de execução: {$executionTime}s");
    
    // Processar resposta
    $data = json_decode($response, true);
    
    if ($data === null) {
        throw new Exception("Resposta JSON inválida: " . substr($response, 0, 200));
    }
    
    if (!$data['success']) {
        throw new Exception("Erro na execução: " . ($data['message'] ?? 'Erro desconhecido'));
    }
    
    // Log dos resultados
    $results = $data['results'];
    logCron("✅ Execução bem-sucedida:");
    logCron("   📊 Total processados: {$results['total_processed']}");
    logCron("   📱 Notificações enviadas: {$results['notifications_sent']}");
    logCron("   ❌ Erros: {$results['errors']}");
    
    // Log detalhado dos resultados (se houver erros)
    if ($results['errors'] > 0 && isset($results['details'])) {
        logCron("🔍 Detalhes dos erros:");
        foreach ($results['details'] as $detail) {
            if (!$detail['success']) {
                logCron("   ❌ Agendamento {$detail['agendamento_id']}: {$detail['message']}", 'ERROR');
            }
        }
    }
    
    // Log estatísticas finais
    if ($results['notifications_sent'] > 0) {
        logCron("🎉 {$results['notifications_sent']} notificações enviadas com sucesso!");
    }
    
    // Verificar se há muitos erros consecutivos (opcional)
    if ($results['errors'] > 0 && $results['notifications_sent'] == 0) {
        logCron("⚠️ ALERTA: Nenhuma notificação enviada com sucesso!", 'WARN');
    }
    
    logCron("=== CRON JOB CONCLUÍDO COM SUCESSO ===");
    exit(0);
    
} catch (Exception $e) {
    logCron("❌ ERRO CRÍTICO: " . $e->getMessage(), 'ERROR');
    logCron("Stack trace: " . $e->getTraceAsString(), 'DEBUG');
    
    // Opcional: enviar alerta por email ou webhook
    // sendAlert("Erro no cron job de agendamentos: " . $e->getMessage());
    
    exit(1);
}

/**
 * Função para enviar alertas (implementar conforme necessário)
 */
function sendAlert($message) {
    // Exemplo: enviar email
    // mail('admin@sas.makecard.com.br', 'Erro Cron Job Agendamentos', $message);
    
    // Exemplo: webhook Slack/Discord
    // $webhook = 'https://hooks.slack.com/services/...';
    // file_get_contents($webhook, false, stream_context_create([
    //     'http' => [
    //         'method' => 'POST',
    //         'header' => 'Content-Type: application/json',
    //         'content' => json_encode(['text' => $message])
    //     ]
    // ]));
    
    logCron("🚨 ALERTA: {$message}", 'ALERT');
}
?> 