<?php
/**
 * Script para reprocessamento automático de documentos pendentes
 * Este script deve ser executado periodicamente (ex: a cada 2 minutos via cron)
 * 
 * CONFIGURAÇÃO DO CRON:
 * A cada 2 minutos: /usr/bin/php /caminho/para/auto_reprocessar.php >/dev/null 2>&1
 * 
 * OU via wget/curl:
 * A cada 2 minutos: wget -q -O /dev/null "https://sas.makecard.com.br/webhook_zapsign.php?reprocessar=1"
 */

// Configurar timeout e headers
set_time_limit(60); // 1 minuto máximo
header('Content-Type: application/json; charset=utf-8');

// Log de execução
$logFile = __DIR__ . '/reprocessamento_auto.log';
$timestamp = date('Y-m-d H:i:s');

function writeAutoLog($message) {
    global $logFile, $timestamp;
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    writeAutoLog("=== INICIANDO REPROCESSAMENTO AUTOMÁTICO ===");
    
    // Chamar o webhook com parâmetro de reprocessamento
    $webhookUrl = 'https://sas.makecard.com.br/webhook_zapsign.php?reprocessar=1';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Auto-Reprocessador ZapSign/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception("Erro cURL: {$curlError}");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP {$httpCode}: {$response}");
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        throw new Exception("Resposta JSON inválida: {$response}");
    }
    
    // Log do resultado
    $status = $result['status'] ?? 'unknown';
    $encontrados = $result['documentos_encontrados'] ?? 0;
    $processados = $result['documentos_processados'] ?? 0;
    $sucessos = $result['sucessos'] ?? 0;
    
    writeAutoLog("RESULTADO: Status={$status}, Encontrados={$encontrados}, Processados={$processados}, Sucessos={$sucessos}");
    
    if ($status === 'sucesso') {
        if ($encontrados > 0) {
            writeAutoLog("SUCESSO: {$sucessos} documentos processados com sucesso de {$encontrados} encontrados");
        } else {
            writeAutoLog("INFO: Nenhum documento pendente para processar");
        }
    } else {
        $mensagem = $result['mensagem'] ?? 'Erro desconhecido';
        writeAutoLog("ERRO: {$mensagem}");
    }
    
    // Resposta para cron/wget
    echo json_encode([
        'status' => 'sucesso',
        'timestamp' => $timestamp,
        'resultado_reprocessamento' => $result
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $error = $e->getMessage();
    writeAutoLog("ERRO FATAL: {$error}");
    
    echo json_encode([
        'status' => 'erro',
        'timestamp' => $timestamp,
        'erro' => $error
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

writeAutoLog("=== FIM DO REPROCESSAMENTO AUTOMÁTICO ===");
?>
