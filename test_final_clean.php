<?php
/**
 * Teste Final Limpo - Notificação Push
 * Execute após configurar VAPID e limpar subscriptions
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🎯 Teste Final - Push Notifications</h1>\n";
echo "<pre>\n";

$userCard = '8029774802';
$testData = [
    'user_card' => $userCard,
    'agendamento_id' => 999,
    'tipo_notificacao' => 'teste_final_limpo',
    'titulo' => '🎉 TESTE FINAL!',
    'mensagem' => 'Se você recebeu esta notificação, PARABÉNS! O sistema está funcionando perfeitamente! 🚀',
    'profissional' => 'Sistema Push Notifications',
    'especialidade' => 'Teste Completo',
    'convenio_nome' => 'SAS App'
];

echo "=====================================\n";
echo "ENVIANDO NOTIFICAÇÃO DE TESTE\n";
echo "=====================================\n";
echo "👤 Usuário: {$userCard}\n";
echo "📱 Título: {$testData['titulo']}\n";
echo "💬 Mensagem: " . substr($testData['mensagem'], 0, 50) . "...\n\n";

$url = 'https://sas.makecard.com.br/send_push_notification_app.php';
$postData = json_encode($testData);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📡 Resposta HTTP: {$httpCode}\n";

if ($error) {
    echo "❌ Erro cURL: {$error}\n";
} else {
    echo "📋 Resposta do servidor:\n";
    echo $response . "\n\n";
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === 200 && isset($responseData['success']) && $responseData['success']) {
        echo "🎊 SUCESSO COMPLETO!\n";
        echo "=====================================\n";
        echo "✅ Sistema de Push Notifications FUNCIONANDO!\n";
        echo "📱 Verifique seu dispositivo móvel AGORA!\n";
        echo "🔔 A notificação deve aparecer em alguns segundos!\n\n";
        
        if (isset($responseData['results'])) {
            $results = $responseData['results'];
            echo "📊 Estatísticas:\n";
            echo "- 📱 Total subscriptions: {$results['total_subscriptions']}\n";
            echo "- ✅ Sucessos: {$results['success_count']}\n";
            echo "- ❌ Erros: {$results['error_count']}\n\n";
        }
        
        echo "🎯 SISTEMA PRONTO PARA PRODUÇÃO!\n";
        echo "=====================================\n";
        echo "✅ Próximos passos:\n";
        echo "1. Configure cron job para verificação automática\n";
        echo "2. Teste com agendamentos reais\n";
        echo "3. Monitore logs de notificações\n";
        echo "4. Sistema está funcionando perfeitamente!\n";
        
    } else {
        echo "❌ AINDA HÁ PROBLEMAS\n";
        echo "=====================================\n";
        
        if (isset($responseData['results']['details'][0]['http_code'])) {
            $code = $responseData['results']['details'][0]['http_code'];
            switch ($code) {
                case 403:
                    echo "🔑 HTTP 403: Chave VAPID privada não configurada!\n";
                    echo "Configure no send_push_notification_app.php:\n";
                    echo "define('VAPID_PRIVATE_KEY', 'gdc9W5SDjkTwr7l_fa-TE6D53VfXs_S3cBSeq2OrF4o');\n";
                    break;
                case 410:
                    echo "📱 HTTP 410: Subscription expirada, reative notificações no app\n";
                    break;
                default:
                    echo "🔧 HTTP {$code}: Erro desconhecido\n";
                    break;
            }
        }
        
        echo "\n🔧 Soluções:\n";
        echo "1. Verifique se a chave VAPID privada está configurada\n";
        echo "2. Certifique-se que as notificações estão ativas no app\n";
        echo "3. Execute: fix_subscription_structure.php para diagnóstico\n";
    }
}

echo "\n❌ DELETE este arquivo após usar!\n";
echo "</pre>\n";
?> 