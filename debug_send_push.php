<?php
/**
 * Debug Send Push
 * Identificar problema específico com send_push_fixed.php
 */

require_once 'Adm/php/banco.php';
require_once 'vapid_config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Debug Send Push</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .urgent { background: #dc3545; color: white; padding: 20px; border-radius: 5px; font-weight: bold; }
        .code { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; font-size: 11px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 Debug Send Push</h1>";

echo "<div class='urgent'>
🚨 <strong>PROBLEMA:</strong> send_push_fixed.php retorna erro 400 Invalid JSON<br>
🕒 Timestamp: " . date('Y-m-d H:i:s') . "<br>
🎯 Identificar causa exata do problema
</div>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    /** @var PDO $pdo */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📋 1. VERIFICANDO SUBSCRIPTION ATIVA</h2>";
    echo "<div class='box'>";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_card,
            endpoint,
            COALESCE(p256dh, p256dh_key) as p256dh,
            COALESCE(auth, auth_key) as auth,
            settings,
            is_active
        FROM sind.push_subscriptions 
        WHERE user_card = '8029774802' AND is_active = true
        LIMIT 1
    ");
    
    $stmt->execute();
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription) {
        echo "<div class='success'>✅ Subscription encontrada:</div>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$subscription['id']}</li>";
        echo "<li><strong>User Card:</strong> {$subscription['user_card']}</li>";
        echo "<li><strong>Endpoint:</strong> " . substr($subscription['endpoint'], 0, 50) . "...</li>";
        echo "<li><strong>P256dh:</strong> " . (empty($subscription['p256dh']) ? '❌ VAZIO' : '✅ OK (' . strlen($subscription['p256dh']) . ' chars)') . "</li>";
        echo "<li><strong>Auth:</strong> " . (empty($subscription['auth']) ? '❌ VAZIO' : '✅ OK (' . strlen($subscription['auth']) . ' chars)') . "</li>";
        echo "<li><strong>Active:</strong> " . ($subscription['is_active'] ? '✅ Sim' : '❌ Não') . "</li>";
        echo "</ul>";
        
        if (empty($subscription['p256dh']) || empty($subscription['auth'])) {
            echo "<div class='error'>❌ <strong>PROBLEMA ENCONTRADO:</strong> Chaves p256dh ou auth estão vazias!</div>";
            echo "<div class='warning'>🔧 <strong>SOLUÇÃO:</strong> Usuário precisa reativar notificações no app</div>";
        }
    } else {
        echo "<div class='error'>❌ Nenhuma subscription ativa encontrada</div>";
    }
    
    echo "</div>";
    
    echo "<h2>🧪 2. TESTE DO PAYLOAD JSON</h2>";
    echo "<div class='box'>";
    
    // Testar criação do payload JSON
    $testData = [
        'title' => 'Teste Debug',
        'body' => 'Testando criação do JSON payload',
        'icon' => 'https://sas.makecard.com.br/icons/icon-192x192.png',
        'badge' => 'https://sas.makecard.com.br/icons/icon-192x192.png',
        'data' => [
            'tipo' => 'teste_debug',
            'agendamento_id' => 999,
            'url' => 'https://sas.makecard.com.br/dashboard',
            'timestamp' => time()
        ],
        'actions' => [
            [
                'action' => 'view',
                'title' => 'Ver Detalhes',
                'icon' => 'https://sas.makecard.com.br/icons/icon-192x192.png'
            ]
        ],
        'requireInteraction' => true,
        'tag' => 'agendamento-999'
    ];
    
    $payload = json_encode($testData);
    $jsonError = json_last_error();
    
    echo "<div class='info'>🔍 Testando criação do JSON...</div>";
    echo "<div class='code'>Payload: " . htmlspecialchars($payload) . "</div>";
    
    if ($jsonError === JSON_ERROR_NONE) {
        echo "<div class='success'>✅ JSON criado com sucesso</div>";
        echo "<div class='info'>Tamanho: " . strlen($payload) . " bytes</div>";
    } else {
        echo "<div class='error'>❌ Erro no JSON: " . json_last_error_msg() . "</div>";
    }
    
    echo "</div>";
    
    echo "<h2>🔧 3. TESTE SEM BIBLIOTECA WEB-PUSH</h2>";
    echo "<div class='box'>";
    
    // Testar envio direto sem biblioteca web-push
    echo "<div class='info'>🧪 Testando send_push_fixed.php diretamente...</div>";
    
    $testPostData = [
        'user_card' => '8029774802',
        'titulo' => 'Teste Debug Direto',
        'mensagem' => 'Teste sem biblioteca web-push',
        'tipo_notificacao' => 'teste_debug',
        'agendamento_id' => 999
    ];
    
    echo "<div class='code'>Dados enviados: " . json_encode($testPostData, JSON_PRETTY_PRINT) . "</div>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sas.makecard.com.br/send_push_fixed.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($testPostData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Separar header e body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    echo "<div class='code'>HTTP Code: {$httpCode}
Headers: {$header}
Body: {$body}
cURL Error: {$error}</div>";
    
    echo "</div>";
    
    echo "<h2>🔬 4. TESTE MANUAL DA BIBLIOTECA</h2>";
    echo "<div class='box'>";
    
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        
        if (class_exists('Minishlink\WebPush\WebPush')) {
            
            echo "<div class='success'>✅ Biblioteca web-push carregada</div>";
            
            try {
                // Configurar WebPush
                $auth = [
                    'VAPID' => [
                        'subject' => VAPID_SUBJECT,
                        'publicKey' => VAPID_PUBLIC_KEY,
                        'privateKey' => VAPID_PRIVATE_KEY,
                    ],
                ];
                
                $webPush = new \Minishlink\WebPush\WebPush($auth);
                echo "<div class='success'>✅ WebPush configurado</div>";
                
                if ($subscription && !empty($subscription['p256dh']) && !empty($subscription['auth'])) {
                    // Criar objeto Subscription
                    $pushSubscription = \Minishlink\WebPush\Subscription::create([
                        'endpoint' => $subscription['endpoint'],
                        'keys' => [
                            'p256dh' => $subscription['p256dh'],
                            'auth' => $subscription['auth']
                        ]
                    ]);
                    
                    echo "<div class='success'>✅ Subscription object criado</div>";
                    
                    // Tentar enviar notificação simples
                    $simplePayload = json_encode([
                        'title' => 'Teste Manual',
                        'body' => 'Teste da biblioteca web-push'
                    ]);
                    
                    $report = $webPush->sendOneNotification($pushSubscription, $simplePayload);
                    
                    if ($report->isSuccess()) {
                        echo "<div class='success'>🎉 Notificação enviada com sucesso!</div>";
                    } else {
                        echo "<div class='error'>❌ Falha ao enviar: " . $report->getReason() . "</div>";
                        echo "<div class='info'>HTTP Code: " . $report->getResponse()->getStatusCode() . "</div>";
                    }
                } else {
                    echo "<div class='warning'>⚠️ Subscription incompleta - não é possível testar envio</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erro na biblioteca: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ Biblioteca web-push não encontrada</div>";
    }
    
    echo "</div>";
    
    echo "<h2>🎯 5. DIAGNÓSTICO FINAL</h2>";
    echo "<div class='box'>";
    
    if ($subscription && !empty($subscription['p256dh']) && !empty($subscription['auth'])) {
        echo "<div class='success'>✅ Subscription válida encontrada</div>";
        echo "<div class='info'>🔧 <strong>PRÓXIMO PASSO:</strong> Verificar se o problema está no send_push_fixed.php</div>";
        
        echo "<h3>🔗 Teste Direto:</h3>";
        echo "<a href='send_push_fixed.php' target='_blank' style='background: #dc3545; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🧪 Testar send_push_fixed.php (GET)</a>";
        
    } else {
        echo "<div class='error'>❌ Subscription incompleta ou inválida</div>";
        echo "<div class='warning'>🔧 <strong>SOLUÇÃO:</strong></div>";
        echo "<ol>";
        echo "<li>📱 Vá no app SAS → Dashboard</li>";
        echo "<li>🔄 Desative e reative as notificações</li>";
        echo "<li>✅ Certifique-se que aparece 'Notificações Ativadas'</li>";
        echo "<li>🧪 Execute este debug novamente</li>";
        echo "</ol>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERRO CRÍTICO</h2>";
    echo "Erro: " . $e->getMessage();
    echo "</div>";
}

echo "<div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após usar!</strong><br>";
echo "📁 Arquivo: debug_send_push.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 