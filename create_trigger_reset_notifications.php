<?php
/**
 * Create Trigger Reset Notifications
 * Criar trigger para resetar flags de notificação quando data_agendada for alterada
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔧 Criar Trigger Reset Notifications</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .urgent { background: #28a745; color: white; padding: 20px; border-radius: 5px; font-weight: bold; }
        .code { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; font-size: 11px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Criar Trigger Reset Notifications</h1>";

echo "<div class='urgent'>
🎯 <strong>OBJETIVO:</strong> Trigger automático para resetar flags quando data_agendada for alterada<br>
🕒 Timestamp: " . date('Y-m-d H:i:s') . "<br>
🔄 Solução para problema do sistema administrativo
</div>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔨 1. CRIANDO FUNÇÃO DO TRIGGER</h2>";
    echo "<div class='box'>";
    
    // 1. Criar função do trigger
    $functionSql = "
        CREATE OR REPLACE FUNCTION reset_notification_flags()
        RETURNS TRIGGER AS $$
        BEGIN
            -- Se data_agendada foi alterada (não era NULL e agora é diferente, ou era NULL e agora não é)
            IF (OLD.data_agendada IS DISTINCT FROM NEW.data_agendada) OR 
               (OLD.status IS DISTINCT FROM NEW.status AND NEW.status = 2) THEN
                
                -- Resetar todas as flags de notificação
                NEW.notification_sent_confirmado = false;
                NEW.notification_sent_24h = false;
                NEW.notification_sent_1h = false;
                
                -- Log da alteração (opcional)
                RAISE NOTICE 'Agendamento ID % - Flags de notificação resetadas devido a alteração na data_agendada ou confirmação do status', NEW.id;
            END IF;
            
            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;
    ";
    
    echo "<div class='code'>" . htmlspecialchars($functionSql) . "</div>";
    
    $pdo->exec($functionSql);
    echo "<div class='success'>✅ <strong>FUNÇÃO CRIADA COM SUCESSO!</strong></div>";
    echo "</div>";
    
    echo "<h2>⚡ 2. CRIANDO TRIGGER</h2>";
    echo "<div class='box'>";
    
    // 2. Remover trigger existente (se houver)
    $dropTriggerSql = "DROP TRIGGER IF EXISTS trigger_reset_notification_flags ON sind.agendamento;";
    $pdo->exec($dropTriggerSql);
    
    // 3. Criar trigger
    $triggerSql = "
        CREATE TRIGGER trigger_reset_notification_flags
        BEFORE UPDATE ON sind.agendamento
        FOR EACH ROW
        EXECUTE FUNCTION reset_notification_flags();
    ";
    
    echo "<div class='code'>" . htmlspecialchars($triggerSql) . "</div>";
    
    $pdo->exec($triggerSql);
    echo "<div class='success'>✅ <strong>TRIGGER CRIADO COM SUCESSO!</strong></div>";
    echo "</div>";
    
    echo "<h2>🧪 3. TESTANDO O TRIGGER</h2>";
    echo "<div class='box'>";
    
    // Verificar se trigger foi criado
    $stmt = $pdo->query("
        SELECT 
            trigger_name, 
            event_manipulation, 
            action_timing,
            action_statement
        FROM information_schema.triggers 
        WHERE trigger_name = 'trigger_reset_notification_flags'
          AND event_object_table = 'agendamento'
    ");
    
    $trigger = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($trigger) {
        echo "<div class='success'>✅ <strong>TRIGGER ENCONTRADO NO BANCO:</strong></div>";
        echo "<ul>";
        echo "<li><strong>Nome:</strong> {$trigger['trigger_name']}</li>";
        echo "<li><strong>Evento:</strong> {$trigger['event_manipulation']}</li>";
        echo "<li><strong>Timing:</strong> {$trigger['action_timing']}</li>";
        echo "</ul>";
        
        // Teste prático
        echo "<h3>🔬 Teste Prático:</h3>";
        
        // Primeiro, marcar como enviado
        $testSql1 = "UPDATE sind.agendamento SET notification_sent_confirmado = true WHERE id = 65";
        $pdo->exec($testSql1);
        echo "<div class='info'>1️⃣ Marcando agendamento 65 como notificado...</div>";
        
        // Verificar status antes
        $stmt = $pdo->query("SELECT notification_sent_confirmado FROM sind.agendamento WHERE id = 65");
        $antes = $stmt->fetchColumn();
        echo "<div class='warning'>📋 Status ANTES: notification_sent_confirmado = " . ($antes ? 'true' : 'false') . "</div>";
        
        // Agora alterar data_agendada (isso deve resetar as flags via trigger)
        $novaData = date('Y-m-d H:i:s', strtotime('+2 days 15:00'));
        $testSql2 = "UPDATE sind.agendamento SET data_agendada = ? WHERE id = 65";
        $stmt = $pdo->prepare($testSql2);
        $stmt->execute([$novaData]);
        echo "<div class='info'>2️⃣ Alterando data_agendada para: {$novaData}</div>";
        
        // Verificar status depois
        $stmt = $pdo->query("SELECT notification_sent_confirmado FROM sind.agendamento WHERE id = 65");
        $depois = $stmt->fetchColumn();
        echo "<div class='success'>📋 Status DEPOIS: notification_sent_confirmado = " . ($depois ? 'true' : 'false') . "</div>";
        
        if (!$depois && $antes) {
            echo "<div class='success'>🎉 <strong>TRIGGER FUNCIONANDO PERFEITAMENTE!</strong><br>Flag foi resetada automaticamente!</div>";
        } else {
            echo "<div class='error'>❌ <strong>TRIGGER NÃO FUNCIONOU</strong><br>Flag não foi resetada.</div>";
        }
        
    } else {
        echo "<div class='error'>❌ <strong>TRIGGER NÃO ENCONTRADO</strong></div>";
    }
    
    echo "</div>";
    
    echo "<h2>📖 4. COMO FUNCIONA AGORA</h2>";
    echo "<div class='box'>";
    echo "<h3>🎯 Comportamento Automático:</h3>";
    echo "<ol>";
    echo "<li>🏢 <strong>Usuário no sistema administrativo</strong> altera data_agendada</li>";
    echo "<li>🔄 <strong>Trigger detecta</strong> a alteração automaticamente</li>";
    echo "<li>🚫 <strong>Flags são resetadas</strong> (notification_sent_* = false)</li>";
    echo "<li>⏰ <strong>Cron job executa</strong> (a cada 5 minutos)</li>";
    echo "<li>📱 <strong>Push é enviado</strong> para o usuário</li>";
    echo "</ol>";
    
    echo "<h3>🔧 Condições para Reset:</h3>";
    echo "<ul>";
    echo "<li>📅 <strong>data_agendada</strong> foi alterada</li>";
    echo "<li>✅ <strong>status</strong> foi alterado para 2 (confirmado)</li>";
    echo "</ul>";
    
    echo "<h3>⚡ Teste Agora:</h3>";
    echo "<ol>";
    echo "<li>🖥️ Vá no sistema administrativo</li>";
    echo "<li>📅 Altere data_agendada de qualquer agendamento</li>";
    echo "<li>✅ Confirme status = 2</li>";
    echo "<li>⏱️ Aguarde até 5 minutos</li>";
    echo "<li>📱 Push deve chegar automaticamente!</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h2>🚀 5. TESTE MANUAL</h2>";
    echo "<div class='box'>";
    echo "<a href='check_agendamentos_notifications_final.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🔄 Executar Sistema Agora</a>";
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
echo "📁 Arquivo: create_trigger_reset_notifications.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 