<?php
/**
 * Teste do Sistema de Notificações Automáticas de Agendamento
 * Simula um agendamento e testa o envio automático
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste Notificações Agendamento</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .code { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; font-size: 12px; }
        .button { background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .button:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class='container'>
<h1>🧪 Teste Sistema de Notificações Automáticas</h1>";

echo "<div class='box info'>
<h2>📊 TESTE COMPLETO DO SISTEMA</h2>
<strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "
</div>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. VERIFICAR ESTRUTURA DA TABELA
    echo "<h2>🔍 1. VERIFICAÇÃO DA ESTRUTURA</h2>";
    echo "<div class='box'>";
    
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable 
        FROM information_schema.columns 
        WHERE table_schema = 'sind' AND table_name = 'agendamento' 
        AND column_name IN ('notification_sent_confirmado', 'notification_sent_24h', 'notification_sent_1h', 'updated_at')
        ORDER BY column_name
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Colunas de notificação:</strong><br>";
    if (empty($columns)) {
        echo "<div class='warning'>⚠️ Colunas de notificação não encontradas! Vamos criá-las...</div>";
        
        // Adicionar colunas se não existirem
        $alterQueries = [
            "ALTER TABLE sind.agendamento ADD COLUMN IF NOT EXISTS notification_sent_confirmado BOOLEAN DEFAULT FALSE;",
            "ALTER TABLE sind.agendamento ADD COLUMN IF NOT EXISTS notification_sent_24h BOOLEAN DEFAULT FALSE;", 
            "ALTER TABLE sind.agendamento ADD COLUMN IF NOT EXISTS notification_sent_1h BOOLEAN DEFAULT FALSE;",
            "ALTER TABLE sind.agendamento ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;"
        ];
        
        foreach ($alterQueries as $query) {
            try {
                $pdo->exec($query);
                echo "✅ " . substr($query, 0, 50) . "...<br>";
            } catch (Exception $e) {
                echo "❌ Erro: " . $e->getMessage() . "<br>";
            }
        }
    } else {
        foreach ($columns as $col) {
            echo "✅ {$col['column_name']} ({$col['data_type']})<br>";
        }
    }
    echo "</div>";
    
    // 2. VERIFICAR AGENDAMENTOS EXISTENTES
    echo "<h2>📋 2. AGENDAMENTOS ATUAIS</h2>";
    echo "<div class='box'>";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN data_agendada IS NOT NULL THEN 1 END) as com_data_agendada,
            COUNT(CASE WHEN notification_sent_confirmado = true THEN 1 END) as ja_notificados,
            COUNT(CASE WHEN data_agendada IS NOT NULL AND (notification_sent_confirmado IS NULL OR notification_sent_confirmado = false) THEN 1 END) as pendentes_notificacao
        FROM sind.agendamento
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<strong>Estatísticas:</strong><br>";
    echo "📊 Total de agendamentos: {$stats['total']}<br>";
    echo "📅 Com data_agendada: {$stats['com_data_agendada']}<br>";
    echo "✅ Já notificados: {$stats['ja_notificados']}<br>";
    echo "⏳ Pendentes de notificação: {$stats['pendentes_notificacao']}<br>";
    echo "</div>";
    
    // 3. CRIAR AGENDAMENTO DE TESTE (se necessário)
    echo "<h2>🧪 3. CRIAR AGENDAMENTO DE TESTE</h2>";
    echo "<div class='box'>";
    
    if ($stats['pendentes_notificacao'] == 0) {
        echo "<div class='warning'>⚠️ Nenhum agendamento pendente. Criando um para teste...</div>";
        
        // Criar agendamento de teste
        $data_agendada = date('Y-m-d H:i:s', strtotime('+1 day'));
        $insertSql = "
            INSERT INTO sind.agendamento (
                cod_associado, id_empregador, cod_convenio, 
                data_solicitacao, status, data_agendada,
                profissional, especialidade, convenio_nome,
                notification_sent_confirmado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $pdo->prepare($insertSql);
        $result = $stmt->execute([
            '8029774802', // Usar seu user_card de teste
            1,
            '1',
            date('Y-m-d H:i:s'),
            1,
            $data_agendada,
            'Centro de Teste',
            'Consulta Teste',
            'Convênio Teste',
            false
        ]);
        
        if ($result) {
            $agendamento_id = $pdo->lastInsertId();
            echo "✅ Agendamento de teste criado com ID: {$agendamento_id}<br>";
            echo "📅 Data agendada: {$data_agendada}<br>";
        } else {
            echo "❌ Erro ao criar agendamento de teste<br>";
        }
    } else {
        echo "✅ Existem {$stats['pendentes_notificacao']} agendamentos pendentes de notificação";
    }
    echo "</div>";
    
    // 4. EXECUTAR VERIFICAÇÃO DE NOTIFICAÇÕES
    echo "<h2>🚀 4. EXECUTAR SISTEMA DE NOTIFICAÇÕES</h2>";
    echo "<div class='box'>";
    
    echo "<a href='check_agendamentos_notifications.php' class='button' target='_blank'>
          🔄 Executar Verificação de Notificações
          </a><br><br>";
    
    // Fazer chamada HTTP para o script
    $url = 'https://sas.makecard.com.br/check_agendamentos_notifications.php';
    $result = file_get_contents($url);
    
    if ($result !== false) {
        $response = json_decode($result, true);
        echo "<strong>Resultado da execução:</strong><br>";
        echo "<div class='code'>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</div>";
        
        if ($response['success']) {
            echo "<div class='success'>✅ Sistema executado com sucesso!</div>";
            if ($response['results']['notifications_sent'] > 0) {
                echo "<div class='success'>📱 {$response['results']['notifications_sent']} notificações enviadas!</div>";
            }
        } else {
            echo "<div class='error'>❌ Erro na execução: {$response['message']}</div>";
        }
    } else {
        echo "<div class='error'>❌ Erro ao chamar o script de notificações</div>";
    }
    
    echo "</div>";
    
    // 5. VERIFICAR RESULTADOS
    echo "<h2>📊 5. VERIFICAR RESULTADOS</h2>";
    echo "<div class='box'>";
    
    $stmt = $pdo->query("
        SELECT 
            id, cod_associado, data_agendada, profissional, especialidade,
            notification_sent_confirmado, updated_at
        FROM sind.agendamento 
        WHERE data_agendada IS NOT NULL
        ORDER BY id DESC 
        LIMIT 5
    ");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Últimos agendamentos com data_agendada:</strong><br>";
    echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>User</th><th>Data Agendada</th><th>Profissional</th><th>Notificado</th></tr>";
    
    foreach ($recent as $row) {
        $notificado = $row['notification_sent_confirmado'] ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['cod_associado']}</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['data_agendada'])) . "</td>";
        echo "<td>{$row['profissional']}</td>";
        echo "<td>{$notificado}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 6. CONFIGURAR AUTOMAÇÃO
    echo "<h2>⚙️ 6. CONFIGURAR AUTOMAÇÃO</h2>";
    echo "<div class='box'>";
    echo "<strong>Para automação completa, configure um cron job:</strong><br>";
    echo "<div class='code'>";
    echo "# Executar a cada 5 minutos\n";
    echo "*/5 * * * * curl -s https://sas.makecard.com.br/check_agendamentos_notifications.php > /dev/null 2>&1\n\n";
    echo "# Ou executar via PHP CLI\n";
    echo "*/5 * * * * /usr/bin/php /path/to/your/site/check_agendamentos_notifications.php > /dev/null 2>&1\n";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERRO</h2>";
    echo "Erro: " . $e->getMessage();
    echo "</div>";
}

echo "<div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após configurar o sistema!</strong><br>";
echo "📁 Arquivo: test_agendamento_notifications.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 