<?php
/**
 * Teste Frontend <-> Backend Communication
 * Testa se a API Next.js consegue se comunicar com o PHP
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste Frontend-Backend Communication</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .code { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; }
        h1, h2 { color: #333; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 Teste Frontend-Backend Communication</h1>";

echo "<div class='box info'>
<h2>📊 CONFIGURAÇÃO DO TESTE</h2>
<strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "<br>
<strong>User Card:</strong> 8029774802<br>
<strong>Objetivo:</strong> Testar se Next.js consegue comunicar com PHP
</div>";

// 1. Verificar se o arquivo manage_push_subscriptions_app.php existe
echo "<h2>📁 1. VERIFICAÇÃO DE ARQUIVOS</h2>";

$phpFile = 'manage_push_subscriptions_app.php';
$fileExists = file_exists($phpFile);

echo "<div class='box'>";
echo "<h4>Arquivo PHP:</h4>";
echo "<strong>Arquivo:</strong> {$phpFile}<br>";
echo "<strong>Existe:</strong> " . ($fileExists ? '✅ SIM' : '❌ NÃO') . "<br>";

if ($fileExists) {
    echo "<strong>Tamanho:</strong> " . number_format(filesize($phpFile)) . " bytes<br>";
    echo "<strong>Modificado:</strong> " . date('Y-m-d H:i:s', filemtime($phpFile)) . "<br>";
} else {
    echo "<div class='error'>❌ Arquivo manage_push_subscriptions_app.php não encontrado!</div>";
}
echo "</div>";

// 2. Testar acesso direto ao PHP
echo "<h2>🌐 2. TESTE DE ACESSO DIRETO</h2>";

$testUrl = 'https://sas.makecard.com.br/manage_push_subscriptions_app.php';

echo "<div class='box'>";
echo "<h4>Testando URL:</h4>";
echo "<strong>URL:</strong> {$testUrl}<br>";

// Teste básico com cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $testUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => 'action=invalid_test',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<strong>HTTP Code:</strong> ";
if ($httpCode == 200) {
    echo "<span class='success'>200 ✅</span><br>";
} else {
    echo "<span class='error'>{$httpCode} ❌</span><br>";
}

if ($error) {
    echo "<strong>Erro cURL:</strong> <span class='error'>{$error}</span><br>";
}

if ($response) {
    echo "<h5>Resposta do PHP:</h5>";
    echo "<div class='code'>" . htmlspecialchars($response) . "</div>";
    
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse && isset($jsonResponse['error'])) {
        echo "<div class='info'>✅ PHP está respondendo com JSON</div>";
    }
} else {
    echo "<div class='error'>❌ Nenhuma resposta recebida do PHP</div>";
}

echo "</div>";

// 3. Simular requisição completa
echo "<h2>🧪 3. SIMULAÇÃO DE REGISTRO</h2>";

echo "<div class='box'>";
echo "<h4>Testando registro de subscription:</h4>";

$testData = [
    'action' => 'register',
    'user_card' => '8029774802',
    'endpoint' => 'https://fcm.googleapis.com/fcm/send/test123',
    'p256dh_key' => 'BM1OkhzG6aL+vz1oQJpF3B5ZG8ZPQVfbQmPvQeIkCrQ=',
    'auth_key' => 'YBFkF3B5ZG8ZPQVfbQm=',
    'settings' => '{"enabled": true, "lembrete1h": true, "lembrete24h": true, "agendamentoConfirmado": true}'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $testUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($testData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<strong>Dados enviados:</strong><br>";
echo "<div class='code'>" . json_encode($testData, JSON_PRETTY_PRINT) . "</div>";

echo "<strong>HTTP Code:</strong> ";
if ($httpCode == 200) {
    echo "<span class='success'>200 ✅</span><br>";
} else {
    echo "<span class='error'>{$httpCode} ❌</span><br>";
}

if ($error) {
    echo "<strong>Erro cURL:</strong> <span class='error'>{$error}</span><br>";
}

if ($response) {
    echo "<h5>Resposta do PHP:</h5>";
    echo "<div class='code'>" . htmlspecialchars($response) . "</div>";
    
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse) {
        echo "<h5>📊 Análise da Resposta:</h5>";
        if (isset($jsonResponse['success'])) {
            $successClass = $jsonResponse['success'] ? 'success' : 'error';
            $successIcon = $jsonResponse['success'] ? '✅' : '❌';
            echo "<strong>Success:</strong> <span class='{$successClass}'>{$successIcon}</span><br>";
        }
        if (isset($jsonResponse['message'])) {
            echo "<strong>Message:</strong> {$jsonResponse['message']}<br>";
        }
        if (isset($jsonResponse['subscription_id'])) {
            echo "<strong>Subscription ID:</strong> {$jsonResponse['subscription_id']}<br>";
        }
        if (isset($jsonResponse['error'])) {
            echo "<strong>Error:</strong> <span class='error'>{$jsonResponse['error']}</span><br>";
        }
    }
} else {
    echo "<div class='error'>❌ Nenhuma resposta recebida</div>";
}

echo "</div>";

// 4. Verificar logs de erro
echo "<h2>📋 4. VERIFICAR LOGS DE ERRO</h2>";

echo "<div class='box'>";
echo "<h4>Logs recentes do PHP:</h4>";

// Tentar ler log de erro do PHP
$errorLogFiles = [
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    'error_log',
    '../error_log',
    '/home/makecard/public_html/error_log'
];

$logFound = false;
foreach ($errorLogFiles as $logFile) {
    if (file_exists($logFile) && is_readable($logFile)) {
        echo "<strong>Log encontrado:</strong> {$logFile}<br>";
        $lastLines = array_slice(file($logFile), -20);
        echo "<div class='code'>" . htmlspecialchars(implode('', $lastLines)) . "</div>";
        $logFound = true;
        break;
    }
}

if (!$logFound) {
    echo "<div class='warning'>⚠️ Nenhum log de erro acessível encontrado</div>";
    echo "<p>Logs possíveis: " . implode(', ', $errorLogFiles) . "</p>";
}

echo "</div>";

// 5. Próximos passos
echo "<h2>🎯 5. PRÓXIMOS PASSOS</h2>";

echo "<div class='box info'>";

if ($httpCode == 200 && $response) {
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse && isset($jsonResponse['success']) && $jsonResponse['success']) {
        echo "<h4>✅ BACKEND FUNCIONANDO:</h4>";
        echo "1. PHP está respondendo corretamente<br>";
        echo "2. Dados sendo gravados no banco<br>";
        echo "3. Problema pode estar no frontend Next.js<br>";
        echo "4. Verificar console do navegador<br>";
    } else {
        echo "<h4>⚠️ BACKEND COM PROBLEMAS:</h4>";
        echo "1. PHP responde mas com erro<br>";
        echo "2. Verificar mensagem de erro acima<br>";
        echo "3. Possivelmente problema de banco de dados<br>";
    }
} else {
    echo "<h4>❌ BACKEND NÃO ACESSÍVEL:</h4>";
    echo "1. Arquivo PHP pode não existir<br>";
    echo "2. Erro de servidor web<br>";
    echo "3. Problemas de permissão<br>";
    echo "4. Verificar URL e configuração<br>";
}

echo "<br><h4>🔧 DEBUG SUGERIDO:</h4>";
echo "1. Verificar console do navegador quando ativar notificações<br>";
echo "2. Verificar Network tab para requisições falhadas<br>";
echo "3. Testar URL diretamente: <a href='{$testUrl}' target='_blank'>{$testUrl}</a><br>";
echo "4. Verificar logs de erro do servidor<br>";

echo "</div>";

echo "<hr><div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após usar!</strong><br>";
echo "📁 Arquivo: test_frontend_backend.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 