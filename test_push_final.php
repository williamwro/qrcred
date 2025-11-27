<?php
/**
 * Teste Final - Push Notification Fixed
 * Testa send_push_fixed.php com requisição POST adequada
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste Final - Push Notification</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .json { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; }
        h1, h2 { color: #333; }
        hr { margin: 20px 0; }
    </style>
</head>
<body>
<div class='container'>
<h1>🚀 Teste Final - Push Notification Fixed</h1>";

echo "<div class='box info'>
<h2>📊 CONFIGURAÇÃO DO TESTE</h2>
<strong>URL:</strong> https://sas.makecard.com.br/send_push_fixed.php<br>
<strong>Método:</strong> POST<br>
<strong>User Card:</strong> 8029774802<br>
<strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "
</div>";

// Dados para o teste
$data = [
    'user_card' => '8029774802',
    'titulo' => '🎉 TESTE FINAL FUNCIONANDO!',
    'mensagem' => 'PostgreSQL corrigido! Sistema de notificações operacional com biblioteca web-push.',
    'tipo_notificacao' => 'teste_final_corrigido',
    'agendamento_id' => 999
];

echo "<div class='box'>
<h2>📦 DADOS ENVIADOS</h2>
<div class='json'>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</div>
</div>";

echo "<hr><h2>🚀 ENVIANDO NOTIFICAÇÃO...</h2>";

try {
    // Inicializar cURL
    $ch = curl_init();
    
    // Configurar cURL
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://sas.makecard.com.br/send_push_fixed.php',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false, // Para teste
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    // Executar requisição
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    echo "<div class='box'>";
    echo "<h3>📥 RESPOSTA DO SERVIDOR</h3>";
    echo "<strong>Código HTTP:</strong> <span class='" . ($httpCode == 200 ? 'success' : 'error') . "'>{$httpCode}</span><br>";
    
    if ($error) {
        echo "<div class='error'><strong>❌ Erro cURL:</strong> {$error}</div>";
    }
    
    if ($response) {
        echo "<h4>📋 Resposta Bruta:</h4>";
        echo "<div class='json'>{$response}</div>";
        
        // Tentar decodificar JSON
        $jsonResponse = json_decode($response, true);
        
        if ($jsonResponse) {
            echo "<h4>🔍 ANÁLISE DA RESPOSTA:</h4>";
            echo "<div class='box'>";
            
            if (isset($jsonResponse['success'])) {
                $successClass = $jsonResponse['success'] ? 'success' : 'error';
                $successIcon = $jsonResponse['success'] ? '✅' : '❌';
                echo "<strong>Success:</strong> <span class='{$successClass}'>{$successIcon} " . ($jsonResponse['success'] ? 'SIM' : 'NÃO') . "</span><br>";
            }
            
            if (isset($jsonResponse['message'])) {
                echo "<strong>Message:</strong> {$jsonResponse['message']}<br>";
            }
            
            if (isset($jsonResponse['database_type'])) {
                echo "<strong>Database Type:</strong> {$jsonResponse['database_type']}<br>";
            }
            
            if (isset($jsonResponse['results'])) {
                $results = $jsonResponse['results'];
                echo "<br><h4>📊 RESULTADOS DETALHADOS:</h4>";
                echo "<strong>Total Subscriptions:</strong> {$results['total_subscriptions']}<br>";
                echo "<strong>Success Count:</strong> <span class='success'>{$results['success_count']}</span><br>";
                echo "<strong>Error Count:</strong> <span class='error'>{$results['error_count']}</span><br>";
                
                if (isset($results['details']) && !empty($results['details'])) {
                    echo "<br><h4>🔍 DETALHES POR SUBSCRIPTION:</h4>";
                    foreach ($results['details'] as $i => $detail) {
                        $detailClass = $detail['success'] ? 'success' : 'error';
                        $detailIcon = $detail['success'] ? '✅' : '❌';
                        echo "<div class='box {$detailClass}' style='margin: 5px 0; padding: 10px;'>";
                        echo "<strong>Subscription #{$detail['subscription_id']}:</strong> {$detailIcon}<br>";
                        if (isset($detail['http_code'])) {
                            echo "HTTP Code: {$detail['http_code']}<br>";
                        }
                        if (isset($detail['error'])) {
                            echo "Error: {$detail['error']}<br>";
                        }
                        echo "</div>";
                    }
                }
            }
            
            if (isset($jsonResponse['error'])) {
                echo "<div class='error'><strong>❌ Erro:</strong> {$jsonResponse['error']}</div>";
            }
            
            echo "</div>";
        } else {
            echo "<div class='warning'>⚠️ Resposta não é JSON válido</div>";
        }
    } else {
        echo "<div class='error'>❌ Nenhuma resposta recebida</div>";
    }
    
    echo "</div>";
    
    // Resultado final
    echo "<hr><div class='box'>";
    echo "<h2>🎯 RESULTADO FINAL</h2>";
    
    if ($httpCode == 200 && $jsonResponse && isset($jsonResponse['success'])) {
        if ($jsonResponse['success']) {
            echo "<div class='success'>";
            echo "<h3>🎉 SUCESSO TOTAL!</h3>";
            echo "✅ PostgreSQL funcionando<br>";
            echo "✅ Biblioteca web-push operacional<br>";
            echo "✅ Notificação enviada com sucesso<br>";
            echo "✅ Sistema completamente funcional!<br>";
            echo "</div>";
            
            echo "<div class='box info'>";
            echo "<h4>📱 PRÓXIMOS PASSOS:</h4>";
            echo "1. Verificar se a notificação chegou no celular<br>";
            echo "2. Se chegou: substituir arquivo original<br>";
            echo "3. Configurar cron job para automação<br>";
            echo "4. Sistema pronto para produção!<br>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<h3>⚠️ AINDA HÁ PROBLEMAS</h3>";
            echo "❌ Notificação não foi enviada<br>";
            echo "🔧 Verificar logs e detalhes acima<br>";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ FALHA NA COMUNICAÇÃO</h3>";
        echo "🔧 Verificar resposta HTTP e logs<br>";
        echo "</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ ERRO FATAL</h3>";
    echo "<strong>Erro:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr><div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após usar!</strong><br>";
echo "📁 Arquivo: test_push_final.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 