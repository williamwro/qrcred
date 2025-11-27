<?php
/**
 * Debug Trigger Funcionamento
 * Verificar se trigger está funcionando e por que push não chegou
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Debug Trigger Funcionamento</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .urgent { background: #dc3545; color: white; padding: 20px; border-radius: 5px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .code { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; font-size: 11px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 Debug Trigger Funcionamento</h1>";

echo "<div class='urgent'>
🚨 <strong>SITUAÇÃO:</strong> Push não chegou após alteração no sistema administrativo<br>
🕒 Timestamp: " . date('Y-m-d H:i:s') . "<br>
🔍 Investigando todas as etapas do processo
</div>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    // Conectar ao banco PostgreSQL
    /** @var PDO $pdo */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔨 1. VERIFICAÇÃO DO TRIGGER</h2>";
    echo "<div class='box'>";
    
    // Verificar se trigger existe
    $stmt = $pdo->query("
        SELECT 
            trigger_name, 
            event_manipulation, 
            action_timing,
            event_object_table
        FROM information_schema.triggers 
        WHERE trigger_name = 'trigger_reset_notification_flags'
          AND event_object_table = 'agendamento'
    ");
    
    $trigger = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($trigger) {
        echo "<div class='success'>✅ <strong>TRIGGER EXISTE</strong></div>";
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        foreach ($trigger as $key => $value) {
            echo "<tr><td>{$key}</td><td>{$value}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ <strong>TRIGGER NÃO ENCONTRADO!</strong></div>";
        echo "<div class='warning'>⚠️ Execute create_trigger_reset_notifications.php primeiro!</div>";
    }
    
    echo "</div>";
    
    echo "<h2>📋 2. ÚLTIMOS AGENDAMENTOS ALTERADOS</h2>";
    echo "<div class='box'>";
    
    // Buscar agendamentos com data_agendada recente e status = 2
    $stmt = $pdo->query("
        SELECT 
            a.id,
            a.cod_associado,
            a.id_empregador,
            a.data_agendada,
            a.status,
            a.profissional,
            a.notification_sent_confirmado,
            a.notification_sent_24h,
            a.notification_sent_1h,
            s.nome as nome_associado,
            c.cod_verificacao as numero_cartao
        FROM sind.agendamento a
        INNER JOIN sind.associado s ON (a.cod_associado = s.codigo AND a.id_empregador = s.empregador)
        INNER JOIN sind.c_cartaoassociado c ON (s.codigo = c.cod_associado AND s.empregador = c.empregador)
        WHERE a.data_agendada IS NOT NULL 
          AND a.status = 2
        ORDER BY a.id DESC
        LIMIT 5
    ");
    
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($agendamentos) {
        echo "<div class='success'>✅ <strong>AGENDAMENTOS CONFIRMADOS ENCONTRADOS:</strong></div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Cartão</th><th>Data Agendada</th><th>Status</th><th>Confirmado</th><th>24h</th><th>1h</th></tr>";
        
        foreach ($agendamentos as $ag) {
            $status = $ag['status'] == 2 ? "✅ Confirmado" : "❌ Não Confirmado";
            $confirmado = $ag['notification_sent_confirmado'] ? "✅ Sim" : "❌ Não";
            $h24 = $ag['notification_sent_24h'] ? "✅ Sim" : "❌ Não";
            $h1 = $ag['notification_sent_1h'] ? "✅ Sim" : "❌ Não";
            
            echo "<tr>";
            echo "<td>{$ag['id']}</td>";
            echo "<td>{$ag['nome_associado']}</td>";
            echo "<td>{$ag['numero_cartao']}</td>";
            echo "<td>{$ag['data_agendada']}</td>";
            echo "<td>{$status}</td>";
            echo "<td>{$confirmado}</td>";
            echo "<td>{$h24}</td>";
            echo "<td>{$h1}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Identificar quais precisam de notificação
        $pendentes = array_filter($agendamentos, function($ag) {
            return !$ag['notification_sent_confirmado'];
        });
        
        if ($pendentes) {
            echo "<div class='warning'>⚠️ <strong>AGENDAMENTOS PENDENTES DE NOTIFICAÇÃO:</strong> " . count($pendentes) . "</div>";
            echo "<ul>";
            foreach ($pendentes as $ag) {
                echo "<li>ID {$ag['id']} - {$ag['nome_associado']} (Cartão: {$ag['numero_cartao']})</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class='info'>ℹ️ Todos os agendamentos já foram notificados</div>";
        }
        
    } else {
        echo "<div class='warning'>⚠️ <strong>NENHUM AGENDAMENTO CONFIRMADO ENCONTRADO</strong></div>";
    }
    
    echo "</div>";
    
    echo "<h2>🧪 3. TESTANDO O SISTEMA DE NOTIFICAÇÕES</h2>";
    echo "<div class='box'>";
    
    // Executar o sistema de verificação
    echo "<div class='info'>🔄 Executando check_agendamentos_notifications_final.php...</div>";
    
    $url = 'https://sas.makecard.com.br/check_agendamentos_notifications_final.php';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<div class='code'>HTTP Code: {$httpCode}\nResposta: {$response}</div>";
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data) {
            echo "<div class='success'>✅ <strong>SISTEMA EXECUTADO COM SUCESSO</strong></div>";
            echo "<ul>";
            echo "<li><strong>Success:</strong> " . ($data['success'] ? 'Sim' : 'Não') . "</li>";
            echo "<li><strong>Message:</strong> {$data['message']}</li>";
            echo "<li><strong>Total Processed:</strong> {$data['total_processed']}</li>";
            echo "<li><strong>Notifications Sent:</strong> {$data['notifications_sent']}</li>";
            echo "<li><strong>Errors:</strong> {$data['errors']}</li>";
            echo "</ul>";
            
            if ($data['total_processed'] == 0) {
                echo "<div class='warning'>⚠️ <strong>NENHUM AGENDAMENTO FOI PROCESSADO!</strong></div>";
                echo "<div class='info'>Possíveis causas:</div>";
                echo "<ul>";
                echo "<li>🚫 Flags de notificação não foram resetadas pelo trigger</li>";
                echo "<li>📅 Data_agendada não foi alterada corretamente</li>";
                echo "<li>✅ Status não é 2 (confirmado)</li>";
                echo "<li>🔍 Problema na query de busca</li>";
                echo "</ul>";
            }
        } else {
            echo "<div class='error'>❌ Resposta não é JSON válido</div>";
        }
    } else {
        echo "<div class='error'>❌ <strong>ERRO HTTP {$httpCode}</strong></div>";
    }
    
    echo "</div>";
    
    echo "<h2>📱 4. VERIFICAÇÃO DE SUBSCRIPTIONS</h2>";
    echo "<div class='box'>";
    
    // Verificar subscriptions ativas
    $stmt = $pdo->query("
        SELECT 
            user_card,
            is_active,
            settings,
            created_at
        FROM push_subscriptions 
        WHERE is_active = true
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($subscriptions) {
        echo "<div class='success'>✅ <strong>SUBSCRIPTIONS ATIVAS ENCONTRADAS:</strong></div>";
        echo "<table>";
        echo "<tr><th>Cartão</th><th>Ativo</th><th>Settings</th><th>Criado</th></tr>";
        
        foreach ($subscriptions as $sub) {
            $settings = json_decode($sub['settings'], true);
            $settingsText = $settings ? 
                "Enabled: " . ($settings['enabled'] ? 'Sim' : 'Não') . 
                ", Confirmado: " . ($settings['agendamentoConfirmado'] ? 'Sim' : 'Não') : 
                "Inválido";
            
            echo "<tr>";
            echo "<td>{$sub['user_card']}</td>";
            echo "<td>" . ($sub['is_active'] ? '✅ Sim' : '❌ Não') . "</td>";
            echo "<td>{$settingsText}</td>";
            echo "<td>{$sub['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ <strong>NENHUMA SUBSCRIPTION ATIVA ENCONTRADA!</strong></div>";
        echo "<div class='warning'>⚠️ Usuário precisa ativar notificações no app!</div>";
    }
    
    echo "</div>";
    
    echo "<h2>🔧 5. TESTE MANUAL DO TRIGGER</h2>";
    echo "<div class='box'>";
    
    if ($agendamentos) {
        $primeiroAg = $agendamentos[0];
        $agendamentoId = $primeiroAg['id'];
        
        echo "<div class='info'>🧪 Testando trigger no agendamento ID {$agendamentoId}...</div>";
        
        // Primeiro marcar como notificado
        $stmt = $pdo->prepare("UPDATE sind.agendamento SET notification_sent_confirmado = true WHERE id = ?");
        $stmt->execute([$agendamentoId]);
        echo "<div class='warning'>1️⃣ Marcando como notificado...</div>";
        
        // Verificar se foi marcado
        $stmt = $pdo->prepare("SELECT notification_sent_confirmado FROM sind.agendamento WHERE id = ?");
        $stmt->execute([$agendamentoId]);
        $antes = $stmt->fetchColumn();
        echo "<div class='info'>📋 Status ANTES: " . ($antes ? 'true' : 'false') . "</div>";
        
        // Alterar data_agendada (deve resetar via trigger)
        $novaData = date('Y-m-d H:i:s', strtotime('+3 days 16:30'));
        $stmt = $pdo->prepare("UPDATE sind.agendamento SET data_agendada = ? WHERE id = ?");
        $stmt->execute([$novaData, $agendamentoId]);
        echo "<div class='warning'>2️⃣ Alterando data_agendada para: {$novaData}</div>";
        
        // Verificar se foi resetado
        $stmt = $pdo->prepare("SELECT notification_sent_confirmado FROM sind.agendamento WHERE id = ?");
        $stmt->execute([$agendamentoId]);
        $depois = $stmt->fetchColumn();
        echo "<div class='info'>📋 Status DEPOIS: " . ($depois ? 'true' : 'false') . "</div>";
        
        if (!$depois && $antes) {
            echo "<div class='success'>🎉 <strong>TRIGGER FUNCIONANDO!</strong></div>";
            
            // Agora testar o sistema de notificações novamente
            echo "<div class='info'>🔄 Testando sistema de notificações novamente...</div>";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response2 = curl_exec($ch);
            $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "<div class='code'>Teste após reset:\nHTTP: {$httpCode2}\nResposta: {$response2}</div>";
            
            $data2 = json_decode($response2, true);
            if ($data2 && $data2['notifications_sent'] > 0) {
                echo "<div class='success'>🎉 <strong>PUSH ENVIADO COM SUCESSO!</strong></div>";
            } else {
                echo "<div class='warning'>⚠️ Push não foi enviado. Verificar subscriptions e configurações.</div>";
            }
            
        } else {
            echo "<div class='error'>❌ <strong>TRIGGER NÃO FUNCIONOU!</strong></div>";
            echo "<div class='warning'>Verificar se função reset_notification_flags() existe</div>";
        }
    }
    
    echo "</div>";
    
    echo "<h2>🚀 6. AÇÕES SUGERIDAS</h2>";
    echo "<div class='box'>";
    
    echo "<div class='urgent' style='background: #17a2b8;'>";
    echo "<h3>🎯 PRÓXIMOS PASSOS:</h3>";
    echo "<ol>";
    echo "<li>🔄 <strong>Se trigger não funciona:</strong> Execute create_trigger_reset_notifications.php</li>";
    echo "<li>📱 <strong>Se sem subscriptions:</strong> Reative notificações no app</li>";
    echo "<li>🕒 <strong>Se sistema não encontra agendamentos:</strong> Verificar se flags foram resetadas</li>";
    echo "<li>🔧 <strong>Se push não chega:</strong> Verificar send_push_fixed.php</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔗 LINKS ÚTEIS:</h3>";
    echo "<a href='create_trigger_reset_notifications.php' target='_blank' style='background: #dc3545; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🔧 Recriar Trigger</a>";
    echo "<a href='check_agendamentos_notifications_final.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🔄 Executar Sistema</a>";
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
echo "📁 Arquivo: debug_trigger_funcionamento.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 