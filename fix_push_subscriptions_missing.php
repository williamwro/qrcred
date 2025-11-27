<?php
/**
 * Fix Push Subscriptions Missing
 * Corrigir problema da tabela push_subscriptions que não existe
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔧 Fix Push Subscriptions Missing</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
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
<h1>🔧 Fix Push Subscriptions Missing</h1>";

echo "<div class='urgent'>
🚨 <strong>PROBLEMA IDENTIFICADO:</strong> Tabela push_subscriptions não existe!<br>
🕒 Timestamp: " . date('Y-m-d H:i:s') . "<br>
🎯 Solução: Recriar tabela de subscriptions
</div>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    // Conectar ao banco PostgreSQL
    /** @var PDO $pdo */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔍 1. VERIFICANDO TABELAS EXISTENTES</h2>";
    echo "<div class='box'>";
    
    // Verificar quais tabelas relacionadas a push existem
    $stmt = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
          AND table_name LIKE '%push%' 
           OR table_name LIKE '%notification%'
        ORDER BY table_name
    ");
    
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if ($tabelas) {
        echo "<div class='info'>📋 <strong>TABELAS RELACIONADAS ENCONTRADAS:</strong></div>";
        echo "<ul>";
        foreach ($tabelas as $tabela) {
            echo "<li>{$tabela}</li>";
        }
        echo "</ul>";
    } else {
        echo "<div class='warning'>⚠️ <strong>NENHUMA TABELA RELACIONADA ENCONTRADA</strong></div>";
    }
    
    echo "</div>";
    
    echo "<h2>🔨 2. CRIANDO TABELA PUSH_SUBSCRIPTIONS</h2>";
    echo "<div class='box'>";
    
    // SQL para criar a tabela push_subscriptions
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id SERIAL PRIMARY KEY,
            user_card VARCHAR(20) NOT NULL,
            endpoint TEXT NOT NULL,
            p256dh_key VARCHAR(255),
            auth_key VARCHAR(255) NOT NULL,
            settings JSON DEFAULT '{}',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            p256dh TEXT,
            auth TEXT,
            is_active BOOLEAN DEFAULT true
        );
    ";
    
    echo "<div class='code'>" . htmlspecialchars($createTableSql) . "</div>";
    
    $pdo->exec($createTableSql);
    echo "<div class='success'>✅ <strong>TABELA PUSH_SUBSCRIPTIONS CRIADA!</strong></div>";
    
    // Criar índices
    $indexSql = "
        CREATE INDEX IF NOT EXISTS idx_push_subscriptions_user_card ON push_subscriptions(user_card);
        CREATE INDEX IF NOT EXISTS idx_push_subscriptions_active ON push_subscriptions(is_active);
    ";
    
    $pdo->exec($indexSql);
    echo "<div class='success'>✅ <strong>ÍNDICES CRIADOS!</strong></div>";
    
    echo "</div>";
    
    echo "<h2>🔨 3. CRIANDO TABELA NOTIFICATION_LOG</h2>";
    echo "<div class='box'>";
    
    // SQL para criar a tabela notification_log
    $createLogSql = "
        CREATE TABLE IF NOT EXISTS notification_log (
            id SERIAL PRIMARY KEY,
            user_card VARCHAR(20) NOT NULL,
            subscription_id INTEGER,
            notification_type VARCHAR(50) NOT NULL,
            payload JSON,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'sent',
            error_message TEXT,
            agendamento_id INTEGER
        );
    ";
    
    echo "<div class='code'>" . htmlspecialchars($createLogSql) . "</div>";
    
    $pdo->exec($createLogSql);
    echo "<div class='success'>✅ <strong>TABELA NOTIFICATION_LOG CRIADA!</strong></div>";
    
    // Criar índices para notification_log
    $logIndexSql = "
        CREATE INDEX IF NOT EXISTS idx_notification_log_user_card ON notification_log(user_card);
        CREATE INDEX IF NOT EXISTS idx_notification_log_sent_at ON notification_log(sent_at);
        CREATE INDEX IF NOT EXISTS idx_notification_log_agendamento ON notification_log(agendamento_id);
    ";
    
    $pdo->exec($logIndexSql);
    echo "<div class='success'>✅ <strong>ÍNDICES DO LOG CRIADOS!</strong></div>";
    
    echo "</div>";
    
    echo "<h2>📊 4. VERIFICAÇÃO FINAL</h2>";
    echo "<div class='box'>";
    
    // Verificar se as tabelas foram criadas
    $stmt = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
          AND (table_name = 'push_subscriptions' OR table_name = 'notification_log')
        ORDER BY table_name
    ");
    
    $tabelasCriadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tabelasCriadas) >= 2) {
        echo "<div class='success'>🎉 <strong>TODAS AS TABELAS CRIADAS COM SUCESSO!</strong></div>";
        echo "<ul>";
        foreach ($tabelasCriadas as $tabela) {
            echo "<li>✅ {$tabela}</li>";
        }
        echo "</ul>";
        
        // Verificar se existem subscriptions
        $stmt = $pdo->query("SELECT COUNT(*) FROM sind.push_subscriptions WHERE is_active = true");
        $subscriptionsAtivas = $stmt->fetchColumn();
        
        echo "<div class='info'>📱 <strong>SUBSCRIPTIONS ATIVAS:</strong> {$subscriptionsAtivas}</div>";
        
        if ($subscriptionsAtivas == 0) {
            echo "<div class='warning'>⚠️ <strong>NENHUMA SUBSCRIPTION ATIVA!</strong></div>";
            echo "<div class='info'>📋 <strong>PRÓXIMO PASSO:</strong> Usuário precisa reativar notificações no app</div>";
        }
        
    } else {
        echo "<div class='error'>❌ <strong>ERRO AO CRIAR TABELAS</strong></div>";
    }
    
    echo "</div>";
    
    echo "<h2>🚀 5. TESTE COMPLETO AGORA</h2>";
    echo "<div class='box'>";
    
    echo "<div class='urgent' style='background: #28a745;'>";
    echo "<h3>🎯 AGORA SIGA ESTES PASSOS:</h3>";
    echo "<ol>";
    echo "<li>📱 <strong>Vá no app SAS e REATIVE as notificações</strong></li>";
    echo "<li>🔄 <strong>Execute o debug novamente</strong> para verificar subscriptions</li>";
    echo "<li>🧪 <strong>Teste o sistema</strong> alterando um agendamento no admin</li>";
    echo "<li>📲 <strong>Verifique se o push chegou</strong> no celular</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔗 LINKS PARA TESTE:</h3>";
    echo "<a href='debug_trigger_funcionamento.php' target='_blank' style='background: #17a2b8; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🔍 Debug Completo</a>";
    echo "<a href='check_agendamentos_notifications_final.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🚀 Executar Sistema</a>";
    echo "<a href='reset_agendamento_para_teste.php' target='_blank' style='background: #ffc107; color: black; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🧪 Reset Para Teste</a>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERRO</h2>";
    echo "Erro: " . $e->getMessage();
    echo "</div>";
}

echo "<div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após usar!</strong><br>";
echo "📁 Arquivo: fix_push_subscriptions_missing.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 