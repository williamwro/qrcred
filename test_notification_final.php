<?php
/**
 * Teste Final de Notificação Push
 * Execute após configurar a chave VAPID privada
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🚀 Teste Final - Notificação Push</h1>\n";
echo "<pre>\n";

try {
    $userCard = '8029774802';
    
    echo "=====================================\n";
    echo "CONFIGURAÇÕES DAS CHAVES VAPID\n";
    echo "=====================================\n";
    echo "✅ Chave Pública (Frontend): BJJmOHkytqi0v_7sfKNkxjt1ID_w9nGpra4SHpi_Eu_qgdc9W5SDjkTwr7l_fa-TE6D53VfXs_S3cBSeq2OrF4o\n";
    echo "🔒 Chave Privada (Backend): gdc9W5SDjkTwr7l_fa-TE6D53VfXs_S3cBSeq2OrF4o\n";
    echo "⚠️  Certifique-se de que a chave privada está configurada no send_push_notification_app.php\n\n";
    
    echo "=====================================\n";
    echo "ENVIANDO NOTIFICAÇÃO DE TESTE\n";
    echo "=====================================\n";
    
    // Dados da notificação
    $testData = [
        'user_card' => $userCard,
        'agendamento_id' => 999,
        'tipo_notificacao' => 'teste_final',
        'titulo' => '🎉 Sistema Funcionando!',
        'mensagem' => 'Parabéns! Se você recebeu esta notificação, o sistema de push está funcionando perfeitamente!',
        'data_agendada' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'profissional' => 'Sistema de Notificações',
        'especialidade' => 'Teste Final',
        'convenio_nome' => 'SAS App'
    ];
    
    $url = 'https://sas.makecard.com.br/send_push_notification_app.php';
    $postData = json_encode($testData);
    
    echo "🚀 Enviando para: {$url}\n";
    echo "📦 Dados: " . substr($testData['titulo'], 0, 30) . "...\n\n";
    
    // Configurar cURL
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
    
    echo "📥 Resposta HTTP: {$httpCode}\n";
    
    if ($error) {
        echo "❌ Erro cURL: {$error}\n";
    } else {
        echo "📋 Resposta do servidor:\n{$response}\n\n";
        
        $responseData = json_decode($response, true);
        
        if ($httpCode === 200) {
            if (isset($responseData['success']) && $responseData['success']) {
                echo "🎉 SUCESSO TOTAL!\n";
                echo "=====================================\n";
                echo "✅ Notificação enviada com sucesso!\n";
                echo "📱 Verifique seu dispositivo móvel.\n";
                echo "🔔 Você deve receber a notificação agora!\n";
                
                if (isset($responseData['results'])) {
                    $results = $responseData['results'];
                    echo "\n📊 Estatísticas:\n";
                    echo "- Total: {$results['total_subscriptions']}\n";
                    echo "- Sucessos: {$results['success_count']}\n";
                    echo "- Erros: {$results['error_count']}\n";
                }
                
                echo "\n🎯 SISTEMA FUNCIONANDO PERFEITAMENTE!\n";
                echo "=====================================\n";
                
            } else {
                echo "⚠️ Resposta recebida mas com problemas:\n";
                echo "Erro: " . ($responseData['message'] ?? 'Erro desconhecido') . "\n";
                
                if (isset($responseData['results']['details'][0]['http_code'])) {
                    $code = $responseData['results']['details'][0]['http_code'];
                    if ($code == 403) {
                        echo "\n❌ HTTP 403 - CHAVE VAPID PRIVADA NÃO CONFIGURADA!\n";
                        echo "🔧 Edite send_push_notification_app.php linha ~24:\n";
                        echo "define('VAPID_PRIVATE_KEY', 'gdc9W5SDjkTwr7l_fa-TE6D53VfXs_S3cBSeq2OrF4o');\n";
                    }
                }
            }
        } else {
            echo "❌ Erro HTTP {$httpCode}\n";
        }
    }
    
    echo "\n=====================================\n";
    echo "PRÓXIMOS PASSOS\n";
    echo "=====================================\n";
    
    if ($httpCode === 200 && isset($responseData['success']) && $responseData['success']) {
        echo "🎊 TUDO FUNCIONANDO!\n";
        echo "1. Configure o cron job para verificação automática\n";
        echo "2. Teste com agendamentos reais\n";
        echo "3. Sistema pronto para produção!\n";
    } else {
        echo "🔧 AINDA PRECISA CORRIGIR:\n";
        echo "1. ⚠️ Configure a chave VAPID privada no PHP\n";
        echo "2. 📱 Reative notificações no app (Enabled: Sim)\n";
        echo "3. 🔄 Execute este teste novamente\n";
    }
    
    echo "\n❌ DELETE este arquivo após usar!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
}

echo "</pre>\n";
?> 