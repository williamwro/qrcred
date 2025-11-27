<?php
/**
 * Teste Simples do Sistema de Notificações
 * Execute este script para diagnosticar problemas específicos
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔍 Teste Simples - Sistema de Notificações</h1>\n";
echo "<pre>\n";

echo "=====================================\n";
echo "TESTE 1: CONEXÃO COM BANCO\n";
echo "=====================================\n";

try {
    require_once 'Adm/php/banco.php';
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexão com PostgreSQL: OK\n";
    
    // Testar query simples
    $stmt = $pdo->query("SELECT NOW() as timestamp");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Query teste: {$result['timestamp']}\n";
    
} catch (Exception $e) {
    echo "❌ Erro de conexão: {$e->getMessage()}\n";
    exit;
}

echo "\n=====================================\n";
echo "TESTE 2: VERIFICAR TABELAS\n";
echo "=====================================\n";

$tabelas = [
    'sind.push_subscriptions',
    'sind.notification_log', 
    'sind.notification_queue',
    'sind.agendamento',
    'sind.convenio'
];

foreach ($tabelas as $tabela) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$tabela}");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "✅ Tabela '{$tabela}': {$count} registros\n";
    } catch (Exception $e) {
        echo "❌ Tabela '{$tabela}': ERRO - {$e->getMessage()}\n";
    }
}

echo "\n=====================================\n";
echo "TESTE 3: ESTRUTURA DA TABELA sind.notification_log\n";
echo "=====================================\n";

try {
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable 
        FROM information_schema.columns 
        WHERE table_name = 'notification_log' 
        ORDER BY ordinal_position
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "❌ Tabela notification_log não existe ou não tem colunas\n";
    } else {
        echo "✅ Colunas da tabela notification_log:\n";
        foreach ($columns as $col) {
            echo "   - {$col['column_name']} ({$col['data_type']}) - Nullable: {$col['is_nullable']}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar estrutura: {$e->getMessage()}\n";
}

echo "\n=====================================\n";
echo "TESTE 4: QUERY DO check_agendamentos_notifications_app.php\n";
echo "=====================================\n";

try {
    // Query simplificada similar à do check_agendamentos_notifications_app.php
    $query = "
        SELECT 
            a.id as agendamento_id,
            a.cod_associado as user_card,
            a.data_agendada,
            a.profissional,
            a.especialidade
        FROM sind.agendamento a
        WHERE a.data_agendada IS NOT NULL
          AND a.status = 1
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query de agendamentos funcionou!\n";
    echo "   Encontrados: " . count($agendamentos) . " agendamentos com data_agendada\n";
    
    if (!empty($agendamentos)) {
        echo "   Exemplo:\n";
        $exemplo = $agendamentos[0];
        foreach ($exemplo as $key => $value) {
            echo "   - {$key}: {$value}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro na query de agendamentos: {$e->getMessage()}\n";
}

echo "\n=====================================\n";
echo "TESTE 5: QUERY COM JOIN (ORIGINAL)\n";
echo "=====================================\n";

try {
    // Query original com JOIN
    $query = "
        SELECT DISTINCT
            a.id as agendamento_id,
            a.cod_associado as user_card,
            a.data_agendada,
            a.profissional,
            a.especialidade,
            c.razaosocial as convenio_nome
        FROM sind.agendamento a
        LEFT JOIN sind.convenio c ON c.codigo = a.cod_convenio
        WHERE a.data_agendada IS NOT NULL
          AND a.status = 1
        LIMIT 3
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query com JOIN funcionou!\n";
    echo "   Encontrados: " . count($agendamentos) . " agendamentos\n";
    
    if (!empty($agendamentos)) {
        echo "   Exemplo com convênio:\n";
        $exemplo = $agendamentos[0];
        foreach ($exemplo as $key => $value) {
            echo "   - {$key}: " . ($value ?? 'NULL') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro na query com JOIN: {$e->getMessage()}\n";
}

echo "\n=====================================\n";
echo "TESTE 6: QUERY COMPLETA COM notification_log\n";
echo "=====================================\n";

try {
    // Query completa original
    $query = "
        SELECT DISTINCT
            a.id as agendamento_id,
            a.cod_associado as user_card,
            a.data_agendada,
            a.profissional,
            a.especialidade,
            c.razaosocial as convenio_nome,
            'agendamento_confirmado' as tipo_notificacao
        FROM sind.agendamento a
        LEFT JOIN sind.convenio c ON c.codigo = a.cod_convenio
        LEFT JOIN sind.notification_log nl ON (
            nl.agendamento_id = a.id 
            AND nl.tipo_notificacao = 'agendamento_confirmado'
            AND nl.status = 'enviado'
        )
        WHERE a.data_agendada IS NOT NULL
          AND a.data_agendada >= CURRENT_TIMESTAMP
          AND nl.id IS NULL
          AND a.status = 1
        LIMIT 3
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query completa funcionou!\n";
    echo "   Notificações pendentes: " . count($notifications) . "\n";
    
    if (!empty($notifications)) {
        echo "   Exemplo de notificação pendente:\n";
        $exemplo = $notifications[0];
        foreach ($exemplo as $key => $value) {
            echo "   - {$key}: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "   ℹ️ Nenhuma notificação pendente (normal se não houver agendamentos futuros)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro na query completa: {$e->getMessage()}\n";
    echo "   ESTE É PROVAVELMENTE O PROBLEMA!\n";
}

echo "\n=====================================\n";
echo "TESTE 7: LOGS PHP\n";
echo "=====================================\n";

$logFiles = [
    '/var/log/php_errors.log',
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    'error_log'
];

$foundErrors = false;
foreach ($logFiles as $logFile) {
    if (file_exists($logFile) && is_readable($logFile)) {
        echo "📋 Últimas 5 linhas de: {$logFile}\n";
        echo "-----------------------------------\n";
        $lines = file($logFile);
        $lastLines = array_slice($lines, -5);
        foreach ($lastLines as $line) {
            if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
                echo "🔴 " . htmlspecialchars(trim($line)) . "\n";
                $foundErrors = true;
            }
        }
        echo "-----------------------------------\n\n";
        break;
    }
}

if (!$foundErrors) {
    echo "ℹ️ Nenhum erro recente encontrado nos logs\n";
}

echo "\n=====================================\n";
echo "DIAGNÓSTICO CONCLUÍDO\n";
echo "=====================================\n";

echo "🎯 PRÓXIMOS PASSOS:\n";
echo "1. Se algum teste falhou, esse é o problema a resolver\n";
echo "2. Se sind.notification_log não existe, execute setup_notification_tables.php\n";
echo "3. Se a query completa falhou, verifique se as tabelas sind.agendamento e sind.convenio existem\n";
echo "4. Depois teste novamente o debug_push_notifications.php\n";

echo "</pre>\n";
?> 