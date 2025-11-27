<?php
/**
 * Verify Push System
 * Verificação completa do sistema de push notifications
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Verificação Sistema Push</title>
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
<h1>🔍 Verificação Sistema Push</h1>";

echo "<div class='urgent'>
🚨 <strong>DIAGNÓSTICO COMPLETO:</strong> Verificar todos os componentes do sistema<br>
🕒 Timestamp: " . date('Y-m-d H:i:s') . "<br>
🎯 Encontrar e corrigir problemas
</div>";

$problemas = [];
$sucessos = [];

try {
    /** @noinspection PhpUndefinedClassInspection */
    /** @var PDO $pdo */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sucessos[] = "Conexão banco PostgreSQL OK";
    
    echo "<h2>📂 1. VERIFICAÇÃO DE ARQUIVOS</h2>";
    echo "<div class='box'>";
    
    $arquivos = [
        'vapid_config.php' => 'Configuração VAPID',
        'send_push_fixed.php' => 'Script de envio de push',
        'webhook_agendamento_imediato.php' => 'Webhook imediato',
        'vendor/autoload.php' => 'Biblioteca web-push (Composer)',
        'Adm/php/banco.php' => 'Conexão banco de dados'
    ];
    
    foreach ($arquivos as $arquivo => $descricao) {
        if (file_exists($arquivo)) {
            echo "<div class='success'>✅ {$descricao}: {$arquivo}</div>";
            $sucessos[] = "Arquivo {$arquivo} existe";
        } else {
            echo "<div class='error'>❌ {$descricao}: {$arquivo} - AUSENTE</div>";
            $problemas[] = "Arquivo {$arquivo} não encontrado";
        }
    }
    
    echo "</div>";
    
    echo "<h2>🔑 2. VERIFICAÇÃO VAPID KEYS</h2>";
    echo "<div class='box'>";
    
    if (file_exists('vapid_config.php')) {
        require_once 'vapid_config.php';
        
        if (defined('VAPID_PUBLIC_KEY')) {
            echo "<div class='success'>✅ VAPID_PUBLIC_KEY definida</div>";
            echo "<div class='info'>Chave: " . substr(VAPID_PUBLIC_KEY, 0, 20) . "...</div>";
            $sucessos[] = "VAPID_PUBLIC_KEY configurada";
        } else {
            echo "<div class='error'>❌ VAPID_PUBLIC_KEY não definida</div>";
            $problemas[] = "VAPID_PUBLIC_KEY ausente";
        }
        
        if (defined('VAPID_PRIVATE_KEY')) {
            echo "<div class='success'>✅ VAPID_PRIVATE_KEY definida</div>";
            echo "<div class='info'>Chave: " . substr(VAPID_PRIVATE_KEY, 0, 10) . "...</div>";
            $sucessos[] = "VAPID_PRIVATE_KEY configurada";
        } else {
            echo "<div class='error'>❌ VAPID_PRIVATE_KEY não definida</div>";
            $problemas[] = "VAPID_PRIVATE_KEY ausente";
        }
        
        if (defined('VAPID_SUBJECT')) {
            echo "<div class='success'>✅ VAPID_SUBJECT definida: " . VAPID_SUBJECT . "</div>";
            $sucessos[] = "VAPID_SUBJECT configurada";
        } else {
            echo "<div class='error'>❌ VAPID_SUBJECT não definida</div>";
            $problemas[] = "VAPID_SUBJECT ausente";
        }
    }
    
    echo "</div>";
    
    echo "<h2>📊 3. VERIFICAÇÃO TABELAS</h2>";
    echo "<div class='box'>";
    
    $tabelas = ['sind.push_subscriptions', 'sind.notification_log', 'sind.agendamento'];
    
    foreach ($tabelas as $tabela) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM {$tabela}");
            $count = $stmt->fetchColumn();
            echo "<div class='success'>✅ Tabela {$tabela}: {$count} registros</div>";
            $sucessos[] = "Tabela {$tabela} existe";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Tabela {$tabela}: " . $e->getMessage() . "</div>";
            $problemas[] = "Tabela {$tabela} com problema";
        }
    }
    
    echo "</div>";
    
    echo "<h2>📱 4. VERIFICAÇÃO SUBSCRIPTIONS</h2>";
    echo "<div class='box'>";
    
    try {
        $stmt = $pdo->query("
            SELECT 
                user_card,
                COUNT(*) as total,
                SUM(CASE WHEN is_active THEN 1 ELSE 0 END) as ativas
            FROM sind.push_subscriptions 
            GROUP BY user_card
        ");
        
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($subscriptions) {
            echo "<div class='success'>✅ Subscriptions encontradas:</div>";
            foreach ($subscriptions as $sub) {
                echo "<div class='info'>Cartão {$sub['user_card']}: {$sub['ativas']}/{$sub['total']} ativas</div>";
            }
            $sucessos[] = "Subscriptions encontradas";
        } else {
            echo "<div class='warning'>⚠️ Nenhuma subscription encontrada</div>";
            $problemas[] = "Usuário precisa ativar notificações no app";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro ao verificar subscriptions: " . $e->getMessage() . "</div>";
        $problemas[] = "Erro na tabela push_subscriptions";
    }
    
    echo "</div>";
    
    echo "<h2>🔧 5. TESTE DO SISTEMA</h2>";
    echo "<div class='box'>";
    
    // Testar send_push_fixed.php diretamente
    if (file_exists('send_push_fixed.php')) {
        echo "<div class='info'>🧪 Testando send_push_fixed.php...</div>";
        
        $testData = [
            'user_card' => '8029774802',
            'titulo' => 'Teste Sistema',
            'mensagem' => 'Verificação do sistema de push',
            'tipo_notificacao' => 'teste_verificacao',
            'agendamento_id' => 999
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://sas.makecard.com.br/send_push_fixed.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($testData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "<div class='code'>HTTP Code: {$httpCode}\nResponse: {$response}\nError: {$error}</div>";
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && $result['success']) {
                echo "<div class='success'>✅ Sistema funcionando!</div>";
                $sucessos[] = "send_push_fixed.php funcionando";
            } else {
                echo "<div class='warning'>⚠️ Sistema executou mas com falha</div>";
                $problemas[] = "Push não foi enviado: " . ($result['error'] ?? 'erro desconhecido');
            }
        } else {
            echo "<div class='error'>❌ Erro HTTP {$httpCode}</div>";
            $problemas[] = "send_push_fixed.php retornou erro {$httpCode}";
        }
    }
    
    echo "</div>";
    
    echo "<h2>📋 6. RESUMO E SOLUÇÕES</h2>";
    echo "<div class='box'>";
    
    if (count($sucessos) > 0) {
        echo "<div class='success'><h3>✅ Sucessos ({count($sucessos)}):</h3></div>";
        echo "<ul>";
        foreach ($sucessos as $sucesso) {
            echo "<li>{$sucesso}</li>";
        }
        echo "</ul>";
    }
    
    if (count($problemas) > 0) {
        echo "<div class='error'><h3>❌ Problemas ({count($problemas)}):</h3></div>";
        echo "<ul>";
        foreach ($problemas as $problema) {
            echo "<li>{$problema}</li>";
        }
        echo "</ul>";
        
        echo "<div class='urgent' style='background: #ffc107; color: black;'>";
        echo "<h3>🔧 SOLUÇÕES:</h3>";
        
        if (in_array('Arquivo vendor/autoload.php não encontrado', $problemas)) {
            echo "<div>1️⃣ <strong>Instalar biblioteca web-push:</strong></div>";
            echo "<div class='code'>composer require minishlink/web-push</div>";
        }
        
        if (in_array('Usuário precisa ativar notificações no app', $problemas)) {
            echo "<div>2️⃣ <strong>Ativar notificações no app:</strong></div>";
            echo "<div>📱 Abra o app SAS → Dashboard → Ativar Notificações</div>";
        }
        
        if (strpos(implode(' ', $problemas), 'Tabela') !== false) {
            echo "<div>3️⃣ <strong>Criar tabelas:</strong></div>";
            echo "<div><a href='fix_push_subscriptions_missing.php' target='_blank'>🔧 Executar fix_push_subscriptions_missing.php</a></div>";
        }
        
        echo "</div>";
    }
    
    if (count($problemas) === 0) {
        echo "<div class='success'>";
        echo "<h3>🎉 SISTEMA 100% FUNCIONAL!</h3>";
        echo "<div>Teste o webhook:</div>";
        echo "<div><a href='webhook_agendamento_imediato.php?agendamento_id=65' target='_blank'>⚡ webhook_agendamento_imediato.php?agendamento_id=65</a></div>";
        echo "</div>";
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
echo "📁 Arquivo: verify_push_system.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 