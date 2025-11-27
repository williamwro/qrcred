<?php
header('Content-Type: text/html; charset=UTF-8');

// Conexão com banco PostgreSQL
require_once 'Adm/php/banco.php';
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h1>🔍 Diagnóstico do Sistema de Push Notifications</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    .section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// ==========================================
// 1. VERIFICAR TABELAS DO SISTEMA
// ==========================================
echo "<div class='section'>";
echo "<h2>📊 1. Verificação das Tabelas</h2>";

try {
    // Verificar se as tabelas existem
    $tabelas = ['sind.push_subscriptions', 'sind.notification_log', 'sind.push_notification_log'];
    foreach ($tabelas as $tabela) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $tabela");
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        echo "<p class='success'>✅ Tabela '$tabela': $count registros</p>";
    }
    
    // Verificar campos na tabela agendamento
    $stmt = $pdo->prepare("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'sind' 
        AND table_name = 'agendamento' 
        AND column_name LIKE 'notification_%'
    ");
    $stmt->execute();
    $campos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($campos) >= 3) {
        echo "<p class='success'>✅ Campos de notificação na tabela agendamento: " . implode(', ', $campos) . "</p>";
    } else {
        echo "<p class='error'>❌ Campos de notificação não encontrados na tabela agendamento</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro ao verificar tabelas: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ==========================================
// 2. VERIFICAR SUBSCRIPTIONS ATIVAS
// ==========================================
echo "<div class='section'>";
echo "<h2>📱 2. Push Subscriptions Ativas</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT 
            user_card,
            SUBSTRING(endpoint, 1, 50) || '...' as endpoint_preview,
            settings,
            created_at,
            updated_at
        FROM sind.push_subscriptions 
        ORDER BY updated_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($subscriptions) > 0) {
        echo "<p class='success'>✅ " . count($subscriptions) . " subscriptions encontradas</p>";
        echo "<table>";
        echo "<tr><th>User Card</th><th>Endpoint</th><th>Settings</th><th>Criado em</th></tr>";
        foreach ($subscriptions as $sub) {
            $settings = json_decode($sub['settings'], true);
            $settingsText = $settings ? 
                "Enabled: " . ($settings['enabled'] ? 'Sim' : 'Não') .
                ", Confirmado: " . ($settings['agendamentoConfirmado'] ? 'Sim' : 'Não') : 
                'Vazio';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($sub['user_card']) . "</td>";
            echo "<td>" . htmlspecialchars($sub['endpoint_preview']) . "</td>";
            echo "<td>" . htmlspecialchars($settingsText) . "</td>";
            echo "<td>" . $sub['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ NENHUMA subscription encontrada! Usuários precisam ativar notificações no app.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro ao verificar subscriptions: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ==========================================
// 3. VERIFICAR AGENDAMENTOS COM DATA_AGENDADA
// ==========================================
echo "<div class='section'>";
echo "<h2>📅 3. Agendamentos com Data Agendada</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.cod_associado,
            a.data_agendada,
            a.profissional,
            a.especialidade,
            a.convenio_nome,
            a.notification_sent_confirmado,
            a.notification_sent_24h,
            a.notification_sent_1h,
            CASE 
                WHEN a.data_agendada > NOW() THEN 'Futuro'
                WHEN a.data_agendada < NOW() THEN 'Passado'
                ELSE 'Agora'
            END as status_tempo
        FROM sind.agendamento a
        WHERE a.data_agendada IS NOT NULL 
        ORDER BY a.data_agendada DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($agendamentos) > 0) {
        echo "<p class='success'>✅ " . count($agendamentos) . " agendamentos com data_agendada encontrados</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Usuário</th><th>Data Agendada</th><th>Profissional</th><th>Notif. Enviadas</th><th>Status</th></tr>";
        foreach ($agendamentos as $ag) {
            $notifStatus = [];
            if ($ag['notification_sent_confirmado']) $notifStatus[] = 'Confirmado';
            if ($ag['notification_sent_24h']) $notifStatus[] = '24h';
            if ($ag['notification_sent_1h']) $notifStatus[] = '1h';
            $notifText = count($notifStatus) > 0 ? implode(', ', $notifStatus) : 'Nenhuma';
            
            echo "<tr>";
            echo "<td>" . $ag['id'] . "</td>";
            echo "<td>" . htmlspecialchars($ag['cod_associado']) . "</td>";
            echo "<td>" . $ag['data_agendada'] . "</td>";
            echo "<td>" . htmlspecialchars($ag['profissional'] ?: 'N/A') . "</td>";
            echo "<td>" . $notifText . "</td>";
            echo "<td class='" . ($ag['status_tempo'] == 'Futuro' ? 'success' : ($ag['status_tempo'] == 'Passado' ? 'warning' : 'info')) . "'>" . $ag['status_tempo'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ NENHUM agendamento com data_agendada encontrado!</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro ao verificar agendamentos: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ==========================================
// 4. VERIFICAR LOGS DE NOTIFICAÇÕES
// ==========================================
echo "<div class='section'>";
echo "<h2>📨 4. Logs de Notificações</h2>";

try {
    // Log de notificações processadas
    $stmt = $pdo->prepare("
        SELECT 
            agendamento_id,
            user_card,
            tipo_notificacao,
            status,
            enviado_em
        FROM sind.notification_log 
        ORDER BY enviado_em DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($logs) > 0) {
        echo "<p class='success'>✅ " . count($logs) . " logs de notificação encontrados</p>";
        echo "<table>";
        echo "<tr><th>Agendamento ID</th><th>Usuário</th><th>Tipo</th><th>Status</th><th>Enviado em</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>" . $log['agendamento_id'] . "</td>";
            echo "<td>" . htmlspecialchars($log['user_card']) . "</td>";
            echo "<td>" . $log['tipo_notificacao'] . "</td>";
            echo "<td class='" . ($log['status'] == 'sent' ? 'success' : 'error') . "'>" . $log['status'] . "</td>";
            echo "<td>" . $log['enviado_em'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Nenhum log de notificação encontrado. Sistema pode não estar executando.</p>";
    }
    
    // Log de push notifications enviadas
    $stmt = $pdo->prepare("
        SELECT 
            SUBSTRING(endpoint, 1, 30) || '...' as endpoint_preview,
            title,
            status,
            sent_at,
            response_data
        FROM sind.push_notification_log 
        ORDER BY sent_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $pushLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pushLogs) > 0) {
        echo "<h3>Push Notifications Enviadas:</h3>";
        echo "<table>";
        echo "<tr><th>Endpoint</th><th>Título</th><th>Status</th><th>Enviado em</th><th>Resposta</th></tr>";
        foreach ($pushLogs as $plog) {
            $responseData = json_decode($plog['response_data'], true);
            $responseText = $responseData ? 
                "Status: " . ($responseData['statusCode'] ?? 'N/A') : 
                'Sem dados';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($plog['endpoint_preview']) . "</td>";
            echo "<td>" . htmlspecialchars($plog['title']) . "</td>";
            echo "<td class='" . ($plog['status'] == 'sent' ? 'success' : 'error') . "'>" . $plog['status'] . "</td>";
            echo "<td>" . $plog['sent_at'] . "</td>";
            echo "<td>" . htmlspecialchars($responseText) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Nenhum push notification foi enviado ainda.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro ao verificar logs: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ==========================================
// 5. TESTE MANUAL DE VERIFICAÇÃO
// ==========================================
echo "<div class='section'>";
echo "<h2>🧪 5. Teste Manual do Sistema</h2>";

if (isset($_GET['test_check'])) {
    echo "<h3>Executando teste de verificação...</h3>";
    
    try {
        // Simular chamada para check_agendamentos_notifications_app.php
        $testUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/check_agendamentos_notifications_app.php';
        
        $postData = http_build_query(['action' => 'check_pending_notifications']);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData
            ]
        ]);
        
        $response = file_get_contents($testUrl, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data) {
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<p class='error'>❌ Resposta não é JSON válido:</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        } else {
            echo "<p class='error'>❌ Não foi possível conectar ao script de verificação</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro no teste: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p><a href='?test_check=1' style='background: blue; color: white; padding: 10px; text-decoration: none;'>🧪 Executar Teste de Verificação</a></p>";
}

echo "</div>";

// ==========================================
// 6. VERIFICAÇÕES DE CONFIGURAÇÃO
// ==========================================
echo "<div class='section'>";
echo "<h2>⚙️ 6. Verificações de Configuração</h2>";

// Verificar se os scripts existem
$scripts = [
    'manage_push_subscriptions_app.php',
    'check_agendamentos_notifications_app.php', 
    'send_push_notification_app.php'
];

foreach ($scripts as $script) {
    if (file_exists($script)) {
        echo "<p class='success'>✅ Script '$script' existe</p>";
    } else {
        echo "<p class='error'>❌ Script '$script' NÃO encontrado</p>";
    }
}

// Verificar composer
if (file_exists('vendor/autoload.php')) {
    echo "<p class='success'>✅ Composer vendor/autoload.php existe</p>";
    
    if (file_exists('vendor/minishlink/web-push/')) {
        echo "<p class='success'>✅ Biblioteca minishlink/web-push instalada</p>";
    } else {
        echo "<p class='error'>❌ Biblioteca minishlink/web-push NÃO instalada</p>";
    }
} else {
    echo "<p class='error'>❌ Composer não instalado (vendor/autoload.php não encontrado)</p>";
}

echo "</div>";

// ==========================================
// 7. INSTRUÇÕES DE CORREÇÃO
// ==========================================
echo "<div class='section'>";
echo "<h2>🔧 7. Próximos Passos</h2>";

echo "<h3>Para corrigir problemas encontrados:</h3>";
echo "<ol>";
echo "<li><strong>Se não há subscriptions:</strong> Usuários precisam ativar notificações no app (botão no dashboard)</li>";
echo "<li><strong>Se não há agendamentos com data_agendada:</strong> Criar/atualizar agendamentos no sistema</li>";
echo "<li><strong>Se não há logs:</strong> Configurar cron job para executar verificação automática</li>";
echo "<li><strong>Se scripts não existem:</strong> Fazer upload dos 3 scripts PHP</li>";
echo "<li><strong>Se composer não instalado:</strong> Executar 'composer install' no servidor</li>";
echo "</ol>";

echo "<h3>Comando para cron job:</h3>";
echo "<pre>*/5 * * * * curl -X POST -d \"action=check_pending_notifications\" http://seusite.com/check_agendamentos_notifications_app.php</pre>";

echo "<h3>Para testar manualmente:</h3>";
echo "<pre>curl -X POST -d \"action=check_pending_notifications\" http://seusite.com/check_agendamentos_notifications_app.php</pre>";

echo "</div>";

echo "<p style='margin-top: 30px; padding: 10px; background: #e8f4fd; border-left: 4px solid #2196F3;'>";
echo "🔄 <strong>Atualize esta página</strong> após fazer correções para verificar novamente.";
echo "</p>";
?>