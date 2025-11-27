<?php
/**
 * Diagnóstico do webhook ZapSign
 * Verifica tabelas, logs e configuração
 */

require_once 'webhook_zapsign_config.php';
require_once 'Adm/php/banco.php';

echo "<h2>🔍 Diagnóstico do Webhook ZapSign</h2>";
echo "<p><em>Verificando configuração e funcionamento</em></p>";

try {
    $pdo = Banco::conectar_postgres();
    echo "<p>✅ <strong>Conexão com banco:</strong> OK</p>";
    
    // 1. VERIFICAR SE TABELA DOCUMENTOS_PENDENTES EXISTE
    echo "<h3>1. 📋 Verificando Tabela de Documentos Pendentes</h3>";
    
    $checkTable = $pdo->query("
        SELECT EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = 'sind' 
            AND table_name = 'documentos_pendentes_zapsign'
        ) as table_exists
    ");
    
    $tableExists = $checkTable->fetch()['table_exists'];
    
    if ($tableExists) {
        echo "<p>✅ <strong>Tabela existe:</strong> sind.documentos_pendentes_zapsign</p>";
        
        // Verificar registros
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM sind.documentos_pendentes_zapsign");
        $total = $countStmt->fetch()['total'];
        echo "<p>📊 <strong>Registros na tabela:</strong> {$total}</p>";
        
        if ($total > 0) {
            // Mostrar últimos registros
            $lastRecords = $pdo->query("
                SELECT doc_token, doc_name, evento_inicial, data_criacao, status_processamento
                FROM sind.documentos_pendentes_zapsign 
                ORDER BY data_criacao DESC 
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>📄 Últimos 5 registros:</h4>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
            echo "<tr style='background:#f8f9fa;'><th>Token</th><th>Nome</th><th>Evento</th><th>Data</th><th>Status</th></tr>";
            
            foreach ($lastRecords as $record) {
                echo "<tr>";
                echo "<td>" . substr($record['doc_token'], 0, 20) . "...</td>";
                echo "<td>" . htmlspecialchars($record['doc_name']) . "</td>";
                echo "<td>{$record['evento_inicial']}</td>";
                echo "<td>" . date('d/m H:i', strtotime($record['data_criacao'])) . "</td>";
                echo "<td>{$record['status_processamento']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p>❌ <strong>Tabela NÃO existe:</strong> sind.documentos_pendentes_zapsign</p>";
        echo "<p>🔧 <strong>Criando tabela...</strong></p>";
        
        // Criar tabela
        $createTableSQL = "
        CREATE TABLE sind.documentos_pendentes_zapsign (
            id SERIAL PRIMARY KEY,
            doc_token VARCHAR(255) UNIQUE NOT NULL,
            doc_name VARCHAR(500),
            evento_inicial VARCHAR(50),
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ultima_tentativa TIMESTAMP,
            numero_tentativas INTEGER DEFAULT 0,
            status_processamento VARCHAR(50) DEFAULT 'pendente',
            dados_obtidos BOOLEAN DEFAULT FALSE,
            cpf_encontrado VARCHAR(11),
            telefone_encontrado VARCHAR(20),
            email_encontrado VARCHAR(255),
            erro_ultima_tentativa TEXT,
            prioridade INTEGER DEFAULT 1,
            processar_apos TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT chk_status CHECK (status_processamento IN ('pendente', 'processando', 'concluido', 'erro_permanente'))
        );
        ";
        
        $pdo->exec($createTableSQL);
        echo "<p>✅ <strong>Tabela criada com sucesso!</strong></p>";
    }
    
    // 2. VERIFICAR LOGS DO WEBHOOK
    echo "<h3>2. 📄 Verificando Logs do Webhook</h3>";
    
    $logFile = LOG_FILE;
    echo "<p><strong>Arquivo de log:</strong> {$logFile}</p>";
    
    if (file_exists($logFile)) {
        $logSize = filesize($logFile);
        echo "<p>✅ <strong>Arquivo existe:</strong> {$logSize} bytes</p>";
        
        // Mostrar últimas 10 linhas
        $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastLogs = array_slice($logs, -10);
        
        echo "<h4>📋 Últimas 10 linhas do log:</h4>";
        echo "<div style='background:#f8f9fa; padding:10px; border:1px solid #dee2e6; font-family:monospace; font-size:12px; max-height:300px; overflow-y:auto;'>";
        foreach ($lastLogs as $log) {
            $style = '';
            if (strpos($log, 'ERRO') !== false) $style = 'color:#dc3545;';
            elseif (strpos($log, 'SUCCESS') !== false) $style = 'color:#28a745;';
            elseif (strpos($log, 'EVENTO RECEBIDO') !== false) $style = 'color:#007bff; font-weight:bold;';
            
            echo "<div style='{$style}'>" . htmlspecialchars($log) . "</div>";
        }
        echo "</div>";
        
        // Verificar se há logs de hoje
        $hoje = date('Y-m-d');
        $logsHoje = array_filter($logs, function($log) use ($hoje) {
            return strpos($log, $hoje) !== false;
        });
        
        echo "<p>📅 <strong>Logs de hoje ({$hoje}):</strong> " . count($logsHoje) . " entradas</p>";
        
    } else {
        echo "<p>❌ <strong>Arquivo de log não existe!</strong></p>";
        echo "<p>🔧 <strong>Criando arquivo de log...</strong></p>";
        
        $initialLog = "[" . date('Y-m-d H:i:s') . "] === LOG CRIADO PARA DIAGNÓSTICO ===\n";
        file_put_contents($logFile, $initialLog);
        
        if (file_exists($logFile)) {
            echo "<p>✅ <strong>Arquivo de log criado!</strong></p>";
        } else {
            echo "<p>❌ <strong>Falha ao criar arquivo de log!</strong></p>";
        }
    }
    
    // 3. VERIFICAR REGISTROS NA TABELA PRINCIPAL
    echo "<h3>3. 🗃️ Verificando Tabela Principal</h3>";
    
    $mainTableStmt = $pdo->query("
        SELECT COUNT(*) as total, 
               COUNT(CASE WHEN data_hora >= CURRENT_DATE THEN 1 END) as hoje,
               COUNT(CASE WHEN event = 'doc_created' THEN 1 END) as doc_created,
               COUNT(CASE WHEN event = 'doc_signed' THEN 1 END) as doc_signed
        FROM sind.associados_sasmais
    ");
    
    $stats = $mainTableStmt->fetch();
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f8f9fa;'><th>Métrica</th><th>Valor</th></tr>";
    echo "<tr><td>Total de registros</td><td>{$stats['total']}</td></tr>";
    echo "<tr><td>Registros de hoje</td><td>{$stats['hoje']}</td></tr>";
    echo "<tr><td>Eventos doc_created</td><td>{$stats['doc_created']}</td></tr>";
    echo "<tr><td>Eventos doc_signed</td><td>{$stats['doc_signed']}</td></tr>";
    echo "</table>";
    
    // Mostrar últimos registros de hoje
    $todayRecords = $pdo->query("
        SELECT id, codigo, nome, event, doc_token, cpf, data_hora
        FROM sind.associados_sasmais 
        WHERE data_hora >= CURRENT_DATE
        ORDER BY data_hora DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($todayRecords) > 0) {
        echo "<h4>📊 Registros de hoje:</h4>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse; width:100%;'>";
        echo "<tr style='background:#f8f9fa;'><th>ID</th><th>Código</th><th>Nome</th><th>Event</th><th>CPF</th><th>Data/Hora</th></tr>";
        
        foreach ($todayRecords as $record) {
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>" . substr($record['codigo'], 0, 25) . "...</td>";
            echo "<td>" . htmlspecialchars($record['nome']) . "</td>";
            echo "<td><span style='color:" . ($record['event'] === 'doc_signed' ? '#28a745' : '#007bff') . ";'>{$record['event']}</span></td>";
            echo "<td>" . ($record['cpf'] ?: '<em>vazio</em>') . "</td>";
            echo "<td>" . date('d/m H:i:s', strtotime($record['data_hora'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ <strong>Nenhum registro criado hoje</strong></p>";
        echo "<p>Isso indica que o webhook pode não ter sido chamado pela ZapSign.</p>";
    }
    
    // 4. TESTE DE CONFIGURAÇÃO
    echo "<h3>4. ⚙️ Verificando Configuração</h3>";
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f8f9fa;'><th>Configuração</th><th>Valor</th><th>Status</th></tr>";
    
    $configs = [
        'ENABLE_DEBUG_LOGS' => [ENABLE_DEBUG_LOGS ? 'true' : 'false', ENABLE_DEBUG_LOGS ? '✅' : '❌'],
        'LOG_FILE' => [LOG_FILE, file_exists(LOG_FILE) ? '✅' : '❌'],
        'TABLE_NAME' => [TABLE_NAME, '✅'],
        'ZAPSIGN_API_TOKEN' => [defined('ZAPSIGN_API_TOKEN') && !empty(ZAPSIGN_API_TOKEN) ? 'Configurado' : 'Não configurado', defined('ZAPSIGN_API_TOKEN') && !empty(ZAPSIGN_API_TOKEN) ? '✅' : '❌'],
        'ZAPSIGN_API_BASE_URL' => [ZAPSIGN_API_BASE_URL ?? 'Não definido', defined('ZAPSIGN_API_BASE_URL') ? '✅' : '❌']
    ];
    
    foreach ($configs as $nome => $info) {
        echo "<tr>";
        echo "<td><strong>{$nome}</strong></td>";
        echo "<td>" . htmlspecialchars($info[0]) . "</td>";
        echo "<td>{$info[1]}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. DIAGNÓSTICO E RECOMENDAÇÕES
    echo "<h3>5. 🎯 Diagnóstico e Próximos Passos</h3>";
    
    if (!$tableExists) {
        echo "<div style='background:#fff3cd; color:#856404; padding:10px; margin:10px 0; border:1px solid #ffeaa7; border-radius:5px;'>";
        echo "⚠️ <strong>Tabela documentos_pendentes_zapsign foi criada agora.</strong><br>";
        echo "Isso explica por que não havia registros anteriores.";
        echo "</div>";
    }
    
    if (count($logsHoje ?? []) === 0) {
        echo "<div style='background:#f8d7da; color:#721c24; padding:10px; margin:10px 0; border:1px solid #f5c6cb; border-radius:5px;'>";
        echo "❌ <strong>Nenhum log de hoje encontrado.</strong><br>";
        echo "Isso indica que o webhook não foi chamado pela ZapSign.<br><br>";
        echo "<strong>Possíveis causas:</strong><br>";
        echo "• Webhook não configurado na ZapSign<br>";
        echo "• URL incorreta na configuração<br>";
        echo "• Evento errado selecionado<br>";
        echo "• Documento com nome diferente";
        echo "</div>";
    }
    
    echo "<h4>🔧 Ações Recomendadas:</h4>";
    echo "<ol>";
    echo "<li><strong>Verificar configuração na ZapSign:</strong>";
    echo "<ul>";
    echo "<li>URL: <code>https://sas.makecard.com.br/webhook_zapsign.php</code></li>";
    echo "<li>Evento: <code>doc_created</code></li>";
    echo "<li>Status: Ativo</li>";
    echo "</ul></li>";
    
    echo "<li><strong>Testar webhook manualmente:</strong>";
    echo "<ul>";
    echo "<li><a href='webhook_zapsign.php?status' target='_blank'>Ver status do webhook</a></li>";
    echo "<li><a href='webhook_zapsign.php?debug=1' target='_blank'>Ver logs detalhados</a></li>";
    echo "</ul></li>";
    
    echo "<li><strong>Criar documento de teste:</strong>";
    echo "<ul>";
    echo "<li>Nome exato: <code>TERMO DE ADESÃO DO CARTÃO CONVÊNIO</code></li>";
    echo "<li>Verificar se webhook é chamado na criação</li>";
    echo "</ul></li>";
    echo "</ol>";
    
    echo "<h4>🧪 Teste Rápido:</h4>";
    echo "<p>Para testar se o webhook está funcionando, você pode fazer uma requisição manual:</p>";
    echo "<textarea style='width:100%; height:100px; font-family:monospace; font-size:12px;' readonly>";
    echo 'curl -X POST "https://sas.makecard.com.br/webhook_zapsign.php" \\' . "\n";
    echo '  -H "Content-Type: application/json" \\' . "\n";
    echo '  -d \'{"event_type":"doc_created","token":"teste-manual-' . time() . '","name":"TERMO DE ADESÃO DO CARTÃO CONVÊNIO","signers":[{"name":"Teste Manual","email":"","cpf":"","token":"signer-teste"}]}\'';
    echo "</textarea>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; color:#721c24; padding:10px; margin:10px 0; border:1px solid #f5c6cb; border-radius:5px;'>";
    echo "❌ <strong>Erro:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
