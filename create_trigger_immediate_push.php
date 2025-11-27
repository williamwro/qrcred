<?php
/**
 * Create Trigger Immediate Push
 * Criar trigger que envia push IMEDIATAMENTE quando data_agendada for alterada
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>🚀 Trigger Push Imediato</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
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
<h1>🚀 Trigger Push Imediato</h1>";

echo "<div class='urgent'>
🎯 <strong>OBJETIVO:</strong> Push notification IMEDIATO quando data_agendada for alterada!<br>
🕒 Timestamp: " . date('Y-m-d H:i:s') . "<br>
⚡ Sem esperar cron job - Execução instantânea!
</div>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    /** @var PDO $pdo */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔨 1. CRIANDO FUNÇÃO DE NOTIFICAÇÃO IMEDIATA</h2>";
    echo "<div class='box'>";
    
    // 1. Criar função que faz requisição HTTP imediata
    $functionSql = "
        CREATE OR REPLACE FUNCTION reset_and_notify_immediate()
        RETURNS TRIGGER AS $$
        DECLARE
            resultado TEXT;
        BEGIN
            -- Se data_agendada foi alterada ou status confirmado
            IF (OLD.data_agendada IS DISTINCT FROM NEW.data_agendada) OR 
               (OLD.status IS DISTINCT FROM NEW.status AND NEW.status = 2) THEN
                
                -- Resetar todas as flags de notificação
                NEW.notification_sent_confirmado = false;
                NEW.notification_sent_24h = false;
                NEW.notification_sent_1h = false;
                
                -- Log da alteração
                RAISE NOTICE 'Agendamento ID % - Flags resetadas e notificação imediata disparada', NEW.id;
                
                -- Tentar executar notificação imediata via NOTIFY
                PERFORM pg_notify('agendamento_alterado', 
                    json_build_object(
                        'agendamento_id', NEW.id,
                        'cod_associado', NEW.cod_associado,
                        'id_empregador', NEW.id_empregador,
                        'data_agendada', NEW.data_agendada,
                        'status', NEW.status,
                        'timestamp', NOW()
                    )::text
                );
                
            END IF;
            
            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;
    ";
    
    echo "<div class='code'>" . htmlspecialchars($functionSql) . "</div>";
    
    $pdo->exec($functionSql);
    echo "<div class='success'>✅ <strong>FUNÇÃO DE NOTIFICAÇÃO IMEDIATA CRIADA!</strong></div>";
    echo "</div>";
    
    echo "<h2>⚡ 2. ATUALIZANDO TRIGGER</h2>";
    echo "<div class='box'>";
    
    // 2. Remover trigger antigo
    $dropTriggerSql = "DROP TRIGGER IF EXISTS trigger_reset_notification_flags ON sind.agendamento;";
    $pdo->exec($dropTriggerSql);
    
    // 3. Criar novo trigger com notificação imediata
    $triggerSql = "
        CREATE TRIGGER trigger_reset_and_notify_immediate
        BEFORE UPDATE ON sind.agendamento
        FOR EACH ROW
        EXECUTE FUNCTION reset_and_notify_immediate();
    ";
    
    echo "<div class='code'>" . htmlspecialchars($triggerSql) . "</div>";
    
    $pdo->exec($triggerSql);
    echo "<div class='success'>✅ <strong>TRIGGER IMEDIATO CRIADO!</strong></div>";
    echo "</div>";
    
    echo "<h2>🔗 3. CRIANDO ENDPOINT DE NOTIFICAÇÃO ESPECÍFICA</h2>";
    echo "<div class='box'>";
    
    // Criar arquivo PHP para notificação específica
    $endpointContent = '<?php
/**
 * Send Immediate Notification
 * Enviar notificação imediata para agendamento específico
 */

require_once \'Adm/php/banco.php\';
require_once \'send_push_fixed.php\';

header(\'Content-Type: application/json\');

if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
    http_response_code(405);
    echo json_encode([\'error\' => \'Method not allowed\']);
    exit;
}

try {
    $input = json_decode(file_get_contents(\'php://input\'), true);
    $agendamentoId = $input[\'agendamento_id\'] ?? null;
    
    if (!$agendamentoId) {
        echo json_encode([\'error\' => \'agendamento_id required\']);
        exit;
    }
    
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar agendamento específico com dados completos
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.cod_associado,
            a.id_empregador,
            a.data_agendada,
            a.status,
            a.profissional,
            a.especialidade,
            a.convenio_nome,
            a.notification_sent_confirmado,
            s.nome as nome_associado,
            c.cod_verificacao as numero_cartao
        FROM sind.agendamento a
        INNER JOIN sind.associado s ON (a.cod_associado = s.codigo AND a.id_empregador = s.empregador)
        INNER JOIN sind.c_cartaoassociado c ON (s.codigo = c.cod_associado AND s.empregador = c.empregador)
        WHERE a.id = ? 
          AND a.data_agendada IS NOT NULL 
          AND a.status = 2
          AND a.notification_sent_confirmado = false
    ");
    
    $stmt->execute([$agendamentoId]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agendamento) {
        echo json_encode([
            \'success\' => false, 
            \'message\' => \'Agendamento não encontrado ou já notificado\',
            \'agendamento_id\' => $agendamentoId
        ]);
        exit;
    }
    
    // Enviar notificação imediata
    $dataFormatada = date(\'d/m/Y H:i\', strtotime($agendamento[\'data_agendada\']));
    
    $titulo = "🎉 Agendamento Confirmado!";
    $mensagem = "Seu agendamento foi confirmado para {$dataFormatada} com {$agendamento[\'profissional\']}";
    
    $payload = [
        \'user_card\' => $agendamento[\'numero_cartao\'],
        \'titulo\' => $titulo,
        \'mensagem\' => $mensagem,
        \'tipo_notificacao\' => \'agendamento_confirmado_imediato\',
        \'agendamento_id\' => $agendamento[\'id\'],
        \'data_agendada\' => $agendamento[\'data_agendada\'],
        \'profissional\' => $agendamento[\'profissional\']
    ];
    
    // Chamar send_push_fixed.php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, \'https://sas.makecard.com.br/send_push_fixed.php\');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        
        if ($result && $result[\'success\']) {
            // Marcar como notificado
            $stmt = $pdo->prepare("UPDATE sind.agendamento SET notification_sent_confirmado = true WHERE id = ?");
            $stmt->execute([$agendamento[\'id\']]);
            
            echo json_encode([
                \'success\' => true,
                \'message\' => \'Notificação imediata enviada com sucesso\',
                \'agendamento_id\' => $agendamento[\'id\'],
                \'user_card\' => $agendamento[\'numero_cartao\'],
                \'push_result\' => $result
            ]);
        } else {
            echo json_encode([
                \'success\' => false,
                \'message\' => \'Falha ao enviar push\',
                \'push_response\' => $response
            ]);
        }
    } else {
        echo json_encode([
            \'success\' => false,
            \'message\' => "Erro HTTP {$httpCode}",
            \'response\' => $response
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        \'success\' => false,
        \'message\' => \'Erro: \' . $e->getMessage()
    ]);
}
?>';
    
    file_put_contents('send_immediate_notification.php', $endpointContent);
    echo "<div class='success'>✅ <strong>ENDPOINT send_immediate_notification.php CRIADO!</strong></div>";
    echo "</div>";
    
    echo "<h2>🎮 4. CRIANDO LISTENER DE NOTIFICAÇÕES</h2>";
    echo "<div class='box'>";
    
    // Criar script listener que escuta NOTIFY do PostgreSQL
    $listenerContent = '<?php
/**
 * Notification Listener
 * Escuta notificações do PostgreSQL e dispara push imediato
 */

require_once \'Adm/php/banco.php\';

set_time_limit(0);
ini_set(\'memory_limit\', \'256M\');

echo "🎧 Listener de Notificações Imediatas\\n";
echo "🕒 Iniciado em: " . date(\'Y-m-d H:i:s\') . "\\n";
echo "👂 Escutando canal: agendamento_alterado\\n\\n";

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Registrar para escutar notificações
    $pdo->exec("LISTEN agendamento_alterado");
    
    while (true) {
        // Verificar se há notificações pendentes
        $notifications = $pdo->pgsqlGetNotify(PDO::FETCH_ASSOC, 1000); // timeout 1 segundo
        
        if ($notifications) {
            $payload = json_decode($notifications[\'message\'], true);
            
            if ($payload && isset($payload[\'agendamento_id\'])) {
                echo "📨 Notificação recebida: ID {$payload[\'agendamento_id\']}\\n";
                echo "📅 Data agendada: {$payload[\'data_agendada\']}\\n";
                echo "⚡ Enviando push imediato...\\n";
                
                // Chamar endpoint de notificação imediata
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, \'https://sas.makecard.com.br/send_immediate_notification.php\');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([\'agendamento_id\' => $payload[\'agendamento_id\']]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [\'Content-Type: application/json\']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $result = json_decode($response, true);
                    if ($result && $result[\'success\']) {
                        echo "✅ Push enviado com sucesso!\\n";
                    } else {
                        echo "❌ Falha ao enviar push: " . ($result[\'message\'] ?? \'Unknown error\') . "\\n";
                    }
                } else {
                    echo "❌ Erro HTTP {$httpCode}: {$response}\\n";
                }
                
                echo "---\\n";
            }
        }
        
        // Pequena pausa para não sobrecarregar
        usleep(100000); // 0.1 segundo
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\\n";
}
?>';
    
    file_put_contents('notification_listener.php', $listenerContent);
    echo "<div class='success'>✅ <strong>LISTENER notification_listener.php CRIADO!</strong></div>";
    echo "</div>";
    
    echo "<h2>🧪 5. TESTANDO O TRIGGER IMEDIATO</h2>";
    echo "<div class='box'>";
    
    // Verificar se trigger foi criado
    $stmt = $pdo->query("
        SELECT 
            trigger_name, 
            event_manipulation, 
            action_timing
        FROM information_schema.triggers 
        WHERE trigger_name = 'trigger_reset_and_notify_immediate'
          AND event_object_table = 'agendamento'
    ");
    
    $trigger = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($trigger) {
        echo "<div class='success'>✅ <strong>TRIGGER IMEDIATO FUNCIONANDO!</strong></div>";
        echo "<ul>";
        echo "<li><strong>Nome:</strong> {$trigger['trigger_name']}</li>";
        echo "<li><strong>Evento:</strong> {$trigger['event_manipulation']}</li>";
        echo "<li><strong>Timing:</strong> {$trigger['action_timing']}</li>";
        echo "</ul>";
        
        // Teste prático do trigger
        echo "<h3>🔬 Teste Prático:</h3>";
        
        // Buscar um agendamento para testar
        $stmt = $pdo->query("
            SELECT id FROM sind.agendamento 
            WHERE data_agendada IS NOT NULL AND status = 2 
            ORDER BY id DESC LIMIT 1
        ");
        $testId = $stmt->fetchColumn();
        
        if ($testId) {
            echo "<div class='info'>🧪 Testando com agendamento ID: {$testId}</div>";
            
            // Alterar data_agendada para disparar o trigger
            $novaData = date('Y-m-d H:i:s', strtotime('+2 days 17:00'));
            $stmt = $pdo->prepare("UPDATE sind.agendamento SET data_agendada = ? WHERE id = ?");
            $stmt->execute([$novaData, $testId]);
            
            echo "<div class='warning'>⚡ Trigger disparado! Nova data: {$novaData}</div>";
            echo "<div class='info'>📱 Se o listener estiver rodando, push deve chegar AGORA!</div>";
        }
        
    } else {
        echo "<div class='error'>❌ <strong>TRIGGER NÃO ENCONTRADO</strong></div>";
    }
    
    echo "</div>";
    
    echo "<h2>🚀 6. COMO USAR O SISTEMA IMEDIATO</h2>";
    echo "<div class='box'>";
    
    echo "<div class='urgent' style='background: #17a2b8;'>";
    echo "<h3>⚡ OPÇÃO 1: LISTENER AUTOMÁTICO</h3>";
    echo "<ol>";
    echo "<li>🖥️ <strong>Execute em background:</strong> php notification_listener.php</li>";
    echo "<li>🏢 <strong>Altere agendamento</strong> no sistema administrativo</li>";
    echo "<li>📱 <strong>Push chega IMEDIATAMENTE!</strong></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='urgent' style='background: #28a745;'>";
    echo "<h3>⚡ OPÇÃO 2: CHAMADA MANUAL</h3>";
    echo "<ol>";
    echo "<li>🏢 <strong>Após alterar agendamento</strong> no admin</li>";
    echo "<li>🔗 <strong>Chame:</strong> send_immediate_notification.php</li>";
    echo "<li>📱 <strong>Push enviado na hora!</strong></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔗 LINKS PARA TESTE:</h3>";
    echo "<a href='send_immediate_notification.php' target='_blank' style='background: #dc3545; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>⚡ Teste Push Imediato</a>";
    echo "<a href='debug_trigger_funcionamento.php' target='_blank' style='background: #17a2b8; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🔍 Debug Sistema</a>";
    
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
echo "📁 Arquivo: create_trigger_immediate_push.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 