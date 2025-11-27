<?php
/**
 * Teste da Versão Corrigida - send_push_fixed.php
 * Usando biblioteca web-push em vez de implementação manual
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🧪 Teste - Versão Corrigida</h1>\n";
echo "<pre>\n";

echo "=====================================\n";
echo "TESTANDO VERSÃO CORRIGIDA\n";
echo "=====================================\n";

// Dados de teste
$testData = [
    'user_card' => '8029774802',
    'titulo' => '🎉 Teste Versão Corrigida!',
    'mensagem' => 'Esta notificação usa a biblioteca web-push oficial. Se chegou até você, o problema foi resolvido!',
    'tipo_notificacao' => 'teste_corrigido',
    'agendamento_id' => 999
];

echo "📦 Dados de teste:\n";
echo "  User: {$testData['user_card']}\n";
echo "  Título: {$testData['titulo']}\n";
echo "  Tipo: {$testData['tipo_notificacao']}\n";

// URL da versão corrigida
$url = 'https://sas.makecard.com.br/send_push_fixed.php';
$postData = json_encode($testData);

echo "\n🚀 Enviando para versão corrigida...\n";
echo "URL: {$url}\n";

// Fazer requisição
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "\n📥 RESULTADO:\n";
echo "Código HTTP: {$httpCode}\n";

if ($error) {
    echo "❌ Erro cURL: {$error}\n";
} else {
    echo "📋 Resposta bruta:\n";
    echo $response . "\n";
    
    // Tentar decodificar JSON
    $responseData = json_decode($response, true);
    
    if ($responseData) {
        echo "\n📊 ANÁLISE DA RESPOSTA:\n";
        echo "Success: " . ($responseData['success'] ? 'SIM' : 'NÃO') . "\n";
        echo "Message: " . ($responseData['message'] ?? 'N/A') . "\n";
        
        if (isset($responseData['results'])) {
            $results = $responseData['results'];
            echo "Total subscriptions: " . ($results['total_subscriptions'] ?? 0) . "\n";
            echo "Sucessos: " . ($results['success_count'] ?? 0) . "\n";
            echo "Erros: " . ($results['error_count'] ?? 0) . "\n";
            
            if (isset($results['details'])) {
                echo "\n🔍 DETALHES:\n";
                foreach ($results['details'] as $detail) {
                    $status = $detail['success'] ? '✅' : '❌';
                    $httpCode = $detail['http_code'] ?? 'N/A';
                    $error = $detail['error'] ?? '';
                    
                    echo "  {$status} Subscription {$detail['subscription_id']}: HTTP {$httpCode}";
                    if ($error) {
                        echo " - {$error}";
                    }
                    echo "\n";
                }
            }
        }
        
        echo "\n🎯 RESULTADO FINAL:\n";
        if ($responseData['success'] && isset($responseData['results']['success_count']) && $responseData['results']['success_count'] > 0) {
            echo "🎉 SUCESSO! A versão corrigida funcionou!\n";
            echo "✅ Push notification enviada com sucesso!\n";
            echo "📱 Verifique seu dispositivo móvel!\n";
        } else {
            echo "⚠️ Ainda há problemas a resolver\n";
            if (isset($responseData['results']['details'][0]['http_code'])) {
                $code = $responseData['results']['details'][0]['http_code'];
                if ($code == 403) {
                    echo "❌ Ainda HTTP 403 - problema persiste\n";
                } else {
                    echo "🔄 Código diferente de 403: {$code}\n";
                }
            }
        }
        
    } else {
        echo "❌ Resposta não é JSON válido\n";
    }
}

echo "\n=====================================\n";
echo "COMPARAÇÃO DE VERSÕES\n";
echo "=====================================\n";

echo "📊 DIFERENÇAS:\n";
echo "• Versão original: Implementação JWT manual\n";
echo "• Versão corrigida: Biblioteca web-push oficial\n";
echo "• Constantes: Arquivo vapid_config.php separado\n";
echo "• Logs: Melhor tratamento de erros\n";

echo "\n💡 PRÓXIMOS PASSOS:\n";
echo "1. Se esta versão funcionar: substituir arquivo original\n";
echo "2. Se ainda falhar: verificar biblioteca web-push\n";
echo "3. Configurar cron job para notificações automáticas\n";

echo "\n❌ DELETE este arquivo após usar!\n";
echo "</pre>\n";
?> 