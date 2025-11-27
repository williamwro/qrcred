<?php
/**
 * Teste Manual de Notificação Push
 * Envia notificação diretamente para o usuário com subscription ativa
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🧪 Teste Manual - Notificação Push</h1>\n";
echo "<pre>\n";

try {
    require_once 'Adm/php/banco.php';
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Usuário que tem subscription ativa
    $userCard = '8029774802';
    
    echo "=====================================\n";
    echo "1. VERIFICANDO SUBSCRIPTION\n";
    echo "=====================================\n";
    
    // Buscar subscription do usuário (sem filtro is_active por enquanto)
    $stmt = $pdo->prepare("
        SELECT * 
        FROM sind.push_subscriptions 
        WHERE user_card = :user_card 
        LIMIT 1
    ");
    $stmt->execute([':user_card' => $userCard]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription) {
        throw new Exception("Nenhuma subscription ativa encontrada para o usuário {$userCard}");
    }
    
    echo "✅ Subscription encontrada!\n";
    echo "ID: {$subscription['id']}\n";
    echo "Endpoint: " . substr($subscription['endpoint'], 0, 50) . "...\n";
    echo "Criada em: {$subscription['created_at']}\n";
    
    // Verificar configurações (se existir a coluna)
    if (isset($subscription['settings']) && !empty($subscription['settings'])) {
        $settings = json_decode($subscription['settings'], true);
        echo "Configurações:\n";
        echo "- Enabled: " . (isset($settings['enabled']) && $settings['enabled'] ? 'Sim' : 'Não') . "\n";
        echo "- Agendamento Confirmado: " . (isset($settings['agendamentoConfirmado']) && $settings['agendamentoConfirmado'] ? 'Sim' : 'Não') . "\n";
        echo "- Lembrete 24h: " . (isset($settings['lembrete24h']) && $settings['lembrete24h'] ? 'Sim' : 'Não') . "\n";
        echo "- Lembrete 1h: " . (isset($settings['lembrete1h']) && $settings['lembrete1h'] ? 'Sim' : 'Não') . "\n\n";
    } else {
        echo "⚠️ Configurações não disponíveis (coluna 'settings' vazia ou inexistente)\n\n";
    }
    
    echo "=====================================\n";
    echo "2. PREPARANDO NOTIFICAÇÃO DE TESTE\n";
    echo "=====================================\n";
    
    // Dados da notificação de teste
    $notificationData = [
        'user_card' => $userCard,
        'agendamento_id' => 999, // ID de teste
        'tipo_notificacao' => 'agendamento_confirmado',
        'titulo' => '📅 Teste de Notificação!',
        'mensagem' => 'Esta é uma notificação de teste do sistema. Se você recebeu isso, o sistema está funcionando!',
        'data_agendada' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'profissional' => 'Dr. Teste - Sistema',
        'especialidade' => 'Teste de Push Notification',
        'convenio_nome' => 'Convênio Teste'
    ];
    
    echo "Dados da notificação:\n";
    echo "- Título: {$notificationData['titulo']}\n";
    echo "- Mensagem: {$notificationData['mensagem']}\n";
    echo "- Tipo: {$notificationData['tipo_notificacao']}\n\n";
    
    echo "=====================================\n";
    echo "3. ENVIANDO NOTIFICAÇÃO VIA CURL\n";
    echo "=====================================\n";
    
    // URL do script de envio
    $url = 'https://sas.makecard.com.br/send_push_notification_app.php';
    
    // Preparar dados para envio
    $postData = json_encode($notificationData);
    
    // Configurar cURL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    echo "🚀 Enviando requisição para: {$url}\n";
    echo "📦 Payload: " . substr($postData, 0, 100) . "...\n\n";
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "📥 Resposta HTTP: {$httpCode}\n";
    
    if ($error) {
        echo "❌ Erro cURL: {$error}\n";
    } else {
        echo "📋 Resposta do servidor:\n";
        echo $response . "\n\n";
        
        $responseData = json_decode($response, true);
        if ($responseData && $responseData['success']) {
            echo "✅ NOTIFICAÇÃO ENVIADA COM SUCESSO!\n";
            echo "📱 Verifique seu dispositivo para ver a notificação.\n\n";
            
            if (isset($responseData['results'])) {
                $results = $responseData['results'];
                echo "Detalhes:\n";
                echo "- Total subscriptions: {$results['total_subscriptions']}\n";
                echo "- Sucessos: {$results['success_count']}\n";
                echo "- Erros: {$results['error_count']}\n";
            }
        } else {
            echo "❌ FALHA AO ENVIAR NOTIFICAÇÃO\n";
            echo "Erro: " . ($responseData['message'] ?? 'Erro desconhecido') . "\n";
        }
    }
    
    echo "\n=====================================\n";
    echo "4. VERIFICANDO LOGS\n";
    echo "=====================================\n";
    
    // Verificar se foi registrado no banco
    try {
        $logStmt = $pdo->prepare("
            SELECT * FROM sind.notification_log 
            WHERE user_card = :user_card 
            ORDER BY sent_at DESC 
            LIMIT 1
        ");
        $logStmt->execute([':user_card' => $userCard]);
        $log = $logStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($log) {
            echo "✅ Log encontrado no banco:\n";
            foreach ($log as $key => $value) {
                echo "- {$key}: {$value}\n";
            }
        } else {
            echo "⚠️ Nenhum log encontrado no banco.\n";
        }
    } catch (Exception $e) {
        echo "⚠️ Erro ao verificar logs: {$e->getMessage()}\n";
        echo "Isso é normal se a tabela notification_log ainda não foi criada.\n";
    }
    
    echo "\n=====================================\n";
    echo "🎯 RESULTADO DO TESTE\n";
    echo "=====================================\n";
    
    if ($httpCode === 200 && isset($responseData['success']) && $responseData['success']) {
        echo "✅ TESTE PASSOU! Notificação foi enviada.\n";
        echo "📱 Verifique seu dispositivo móvel.\n";
        echo "🔔 Se não recebeu, verifique:\n";
        echo "   - Se as notificações estão habilitadas no navegador\n";
        echo "   - Se o dispositivo está online\n";
        echo "   - Se a chave VAPID privada está configurada\n";
    } else {
        echo "❌ TESTE FALHOU!\n";
        echo "🔧 Verifique os logs acima para detalhes.\n";
    }
    
    echo "\n❌ DELETE este arquivo após usar!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
}

echo "</pre>\n";
?> 